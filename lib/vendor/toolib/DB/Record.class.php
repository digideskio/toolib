<?php
/*
 *  This file is part of PHPLibs <http://phplibs.kmfa.net/>.
 *  
 *  Copyright (c) 2010 < squarious at gmail dot com > .
 *  
 *  PHPLibs is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *  
 *  PHPLibs is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License
 *  along with PHPLibs.  If not, see <http://www.gnu.org/licenses/>.
 *  
 */

namespace toolib\DB;

require_once __DIR__ . '/Connection.class.php';
require_once __DIR__ . '/Model.class.php';
require_once __DIR__ . '/ModelQuery.class.php';
require_once __DIR__ . '/Record/RelationshipMany.class.php';
require_once __DIR__ . '/Record/RelationshipBridge.class.php';

use \toolib\EventDispatcher;
use \toolib\DB\ModelQuery;

/**
 * Base class for declaring models and managing records of model.
 */
class Record
{
	/**
	 * Array with dynamic relationships
	 * @var array
	 */ 
	static protected $dynamic_relationships = array();

	/**
	 * Array with events dispatchers of DB Records
	 * @var array
	 */ 
	static protected $event_dispatchers = array();
	
	/**
	 * Initialize model based on the structure of derived class
	 */
	static private function initModel($model_name)
	{
		// Open model if it exists
		if (($md = Model::open($model_name)) !== null)
			return $md;

		$fields = property_exists($model_name, 'fields')?$model_name::$fields:array();
		$table = property_exists($model_name, 'table')?$model_name::$table:$model_name;
		$rels = isset($model_name::$relationships)
			?$model_name::$relationships
			:array();

		if (isset(self::$dynamic_relationships[$model_name]))
		    $rels = array_merge($rels, self::$dynamic_relationships[$model_name]);

		$model = Model::create($model_name, $table, $fields, $rels);
		if (method_exists($model_name, 'configure')) {
			$model_name::configure($model);
		}
		return $model;
	}
	 
	/**
	 * Perform arbitary query on model and get raw sql results
	 * 
	 * Get a raw query object for this model, whose results will
	 * be in the form of raw data structured in arrays.
	 * @return \toolib\DB\ModelQuery A complete query interface for this model.
     */
	static public function rawQuery($model_name = NULL)
	{
	    if ($model_name === NULL)
			$model_name = get_called_class();
		
		$model = self::initModel($model_name);
		
		return new ModelQuery($model);
	}
	
	/**
	 * Request a query interface that on execution will return Records.
	 * @return \toolib\DB\ModelQuery Query interface for the caller model.
	 */
	static public function openQuery($model_name = NULL)
	{
	    if ($model_name === NULL)
			$model_name = get_called_class();
		
		$model = self::initModel($model_name);
		
		$query = new ModelQuery($model, function($sql_data, $model){ 
			$records = array();
			$model_name = $model->getName();
			foreach($sql_data as $key => $rec)
				$records[] =  new $model_name($model, $rec);
			return $records;
		});
		return $query->select($model->getFields());
	}

	/**
	 * Get the model information object.
	 * @return \toolib\DB\Model informational object.
	 */
	static public function getModel($model_name = NULL)
	{	
		if ($model_name === NULL)
			$model_name = get_called_class();

		return self::initModel($model_name);
	}

	/**
	 * Get the model event handler
	 * 
	 * Events are announced through an EventDispatcher object per model.
	 * The following events are valid:
	 *  - @b pre-open: Filter before execution of open().
	 *  - @b post-open: Notify after execution of open().
	 *  - @b pre-create: Filter before execution of create().
	 *  - @b post-create: Notify after execution of create().
	 *  - @b pre-delete: Filter before execution of delete().
	 *  - @b post-delete: Notify after execution of delete().
	 *  - @b pre-update: Filter before execution of update().
	 *  - @b post-update: Notify after executeion of update().
	 * .
	 * @return \toolib\EventDispatcher Dispatcher for this model.
	 */
    static public function events($model_name = NULL)
    {
        if ($model_name === NULL)
            $model_name = get_called_class();

        if (!isset(self::$event_dispatchers[$model_name]))
            self::$event_dispatchers[$model_name] = new EventDispatcher(
                array(
                    'post-open',
                    'post-create',
                    'post-delete',
                    'post-update',
                    'pre-open',
                    'pre-create',
                    'pre-delete',
                    'pre-update'
                )
            );

        return self::$event_dispatchers[$model_name];
    }

    //! Notify an event listener
    static private function notifyEvent($model_name, $event_name, $args)
    {
        if (!isset(self::$event_dispatchers[$model_name]))
            return false;
        return self::$event_dispatchers[$model_name]->notify($event_name, $args);
    }

    //! Filter through an event listener
    static private function filterEvent($model_name, $event_name, & $value, $args)
    {
        if (!isset(self::$event_dispatchers[$model_name]))
            return false;
        return self::$event_dispatchers[$model_name]->filter($event_name, $value, $args);
    }

	/**
	 * Declare 1-to-many relationship
	 */ 
	static public function oneToMany($many_model_name, $one_rel_name, $many_rel_name)
	{
	    $model_name = get_called_class();
	    self::$dynamic_relationships[$model_name][$many_rel_name] = 
	        array('type' => 'many', 'foreign_model' => $many_model_name);


	    self::$dynamic_relationships[$many_model_name][$one_rel_name] =
	        array('type' => 'one', 'foreign_model' => $model_name);
	}

	//! Declare 1-to-many relationship
	static public function manyToMany($foreign_model_name, $bridge_model_name, $foreign_rel_name, $local_rel_name)
	{
	    $model_name = get_called_class();
	    self::$dynamic_relationships[$model_name][$local_rel_name] = array(
	        'type' => 'bridge',
	        'foreign_model' => $foreign_model_name,
	        'bridge_model' => $bridge_model_name
	    );

	    self::$dynamic_relationships[$foreign_model_name][$foreign_rel_name] = array(
	        'type' => 'bridge',
	        'foreign_model' => $model_name,
	        'bridge_model' => $bridge_model_name
	    );
	}
	
	//! Open the record based on its primary key
	/**
	 * 
	 * It will query database table for a record with the supplied primary key. It will
	 * read the data and return an Record object for this record.
	 * 
	 * @param $primary_keys It can be a string or associative array
	 * 	- @b string The value of PK column if the PK is single-column.
	 *  - @b array The values of all PK columns in associative array if the PK is multi-column.
	 *  .
	 * @param $called_class This parameter must be @b ALWAYS NULL. It would be better
	 * 	if you never used it all, as it is a reserved one for internal use to simulate
	 * 	"Late static binding" on PHP version earlier than PHP5.3
	 * @return  \toolib\DB\Record
	 * 	- @b NULL If the record could not be found.
	 * 	- A Record derived class instance specialized for this record.
	 * 	.
	 * 
	 * @code
	 * // Example reading a news from database with id 14
	 * $n = News::open(14);
	 * @endcode
	*/
	public static function open($primary_keys, $model_name = NULL)
	{
		if ($model_name === NULL)
			$model_name = get_called_class();

		// Initialize model
		$model = self::initModel($model_name);

        // Event notification
        self::filterEvent(
            $model_name,
            'pre-open',
            $primary_keys,
            array('model' => $model_name));
        if ($primary_keys === false)
            return false;
            
		// Check parameters
		$pkFields = $model->getPkFields(false);

		// 1 value to array
		if (!is_array($primary_keys))
			$primary_keys = array($pkFields[0] => $primary_keys);
				
		// Check for given quantity
		if (count($pkFields) != count($primary_keys))
			return false;

		// Execute query and check return value
		$q = self::openQuery($model_name);
		$select_args = array();
		foreach($pkFields as $pk_name) {
			$q->where('? = p.' .$pk_name);
			$select_args[] = $primary_keys[$pk_name];
		}

		// Check return value
		if (count($records = call_user_func_array(array($q, 'execute'), $select_args)) !== 1)
			return false;

        // Event notification
        self::notifyEvent(
            $model_name,
            'post-open',
            array('records' => $records, 'model' => $model_name));
        
		return $records[0];
	}
	
	//! Open all records of this table
	/**
	 * It will query database table and return all the records of the table.
	 * 
	 * @param string $called_class This parameter must be @b ALWAYS NULL. It would be better
	 * 	if you never used it all, as it is a reserved one for internal use to emulate
	 * 	"Late static binding" on PHP version earlier than PHP5.3
	 * @return array 
	 * 	- @b false If any error occurs
	 * 	.
	 * @code
	 * // Example reading a news from database with id 14
	 * $all_news = News::openAll();
	 * @endcode
	 */
	public static function openAll($model_name = NULL)
	{
	    if ($model_name === NULL)
			$model_name = get_called_class();

		// Initialize model
		$model = self::initModel($model_name);
		
		// Execute query and check return value
		$records = self::openQuery($model_name)
			->execute();

        // Event notification
        self::notifyEvent(
            $model_name,
            'post-open',
            array('records' => $records, 'model' => $model_name));

        return $records;
	}
	
	/**
	 * Count records of model
	 * @return The total records.
	 */
	static public function count($model_name = NULL)
	{
	    if ($model_name === NULL)
			$model_name = get_called_class();

		// Initialize model
		$model = self::initModel($model_name);
		
		// Execute query and check return value
		$res = self::rawQuery($model_name)
			->select(array('count(*)'))
			->execute();
		
		// Return results from database
		return $res[0][0];
	}	
	
	/**
	 * Insert a new record in database and get the reference objetc.
	 * @param $args Associative array with new records parameters. Key is the
	 *  is the field name and value the desired value. Any missing field is
	 *  set the "default" value that was defined on the module otherwise is not defined.
	 * @return \toolib\DB\Record - @b Object of the new model record.
	 *  - @b false on any kind of error.
	 */
	static public function create($args = array(), $model_name = NULL)
	{
	    if ($model_name === NULL)
			$model_name = get_called_class();

	    // Initialize model
		$model = self::initModel($model_name);

		// Event notification
        self::filterEvent(
            $model_name,
            'pre-create',
            $args,
            array('model' => $model_name));
        if ($args === false)
            return false;

		// Prepare values
		$insert_args = array();
		$values = array();
		foreach($model->getFields(true) as $field_name => $field) {	
			if (isset($args[$field_name]))
				$values[$field_name] = $model->packFieldData($field_name, $args[$field_name]);
		    else if ($field['ai'])
				continue;	// There is no default values for AI fields
			else if ($field['default'] != false)
				$values[$field_name] = $model->packFieldData($field_name, $field['default']);
			else if ($field['pk'])
				throw new RuntimeException("You cannot create a {$model_name} object  without defining ". 
					"non auto increment primary key '{$field['name']}'");
			else
				continue;	// No user input and no default values
				
			$insert_args[] = $values[$field_name]; 
		}
		
		// Prepare query
		$q = self::rawQuery($model_name)
			->insert(array_keys($values))
			->valuesArray($insert_args);
		
		if (($ret = $q->execute()) === false)
			return false;
	
		// Fill autoincrement fields
		if (count($model->getAiFields()) > 0) {
		    $ai = $model->getAiFields(false);
			$values[$ai[0]] = Connection::getLastInsertId();
		}
		
		// If we have all the attributes of model, directly create object,
		// otherwise open object from database.
		if (count($values) === count($model->getFields())) {
			// Translate data to sql based key
			$sql_fields = array();
			foreach($values as $field_name => $value)
				$sql_fields[$model->getFieldInfo($field_name, 'sqlfield')] = $value;			

			$new_object = new $model_name($model, $sql_fields);
		} else {
		    // Open data based on primary key.
		    foreach($model->getPkFields() as $pk_name)
			    $pk_values[$pk_name] = $values[$pk_name];
			    
            $new_object = $model_name::open($pk_values);
        }

        // Event notification
        self::notifyEvent(
            $model_name,
            'post-create',
            array('record' => $new_object, 'model' => $model_name));

        return $new_object;
	}
	
	/**
	 * Data values of this instance
	 * @var array
	 */
	protected $fields_data = array();
	
	/**
	 * Cache used for cachings casts
	 * @var array
	 */
	protected $data_cast_cache = array();
	
	/**
	 * Track dirty fields for delta updates
	 * @var array
	 */
	protected $dirty_fields = array();
	
	/**
	 * Model meta data pointer
	 * @var \toolib\DB\Model
	 */
	protected $model = NULL;
	
	/**
	 * Constructor is declared final to prohibit direct instantiantion
	 * of this class.
	 * @remarks
	 * You DON'T use @b new to create objects manually instead use create()
	 * and open() functions that will create objects for you.
	 * 
	 * @param $model_meta The meta data of the model that the instance is build from.
	 * @param $sql_data Data to fill the $fields_data given in assoc array using @i sqlfield as key
	 */
	final public function __construct(& $model, $sql_data = NULL)
	{
	    $this->model = & $model;
	
		// Populate fields data
		foreach($model->getFields(true) as $field_name => $field) {
		    $this->fields_data[$field_name] = (isset($sql_data[$field['sqlfield']]))?$sql_data[$field['sqlfield']]:NULL;
			$this->data_cast_cache[$field_name] = NULL;			
		}
	}
	
	/**
	 * Dump all changes of this object in the database.
	 * 
	 * Only the dirty fields will be updated.
	 * @return - @b true If the object had dirty fields and the database
	 *      was updated succesfully.
	 *  - @b false If no update in database was performed.
	 */
	public function update()
	{	
		if(count($this->dirty_fields) === 0)
			return false;	// No changes

		// Event notification
		$cancel = false;
        self::filterEvent(
            $this->model->getName(),
            'pre-update',
            $cancel,
            array('model' => $this->model->getName(), 'record' => $this, 'old_values' => $this->dirty_fields));
        if ($cancel)
            return false;            

		// Create update query
		$update_args = array();
		$q = self::rawQuery($this->model->getName())
			->update()
			->limit(1);

		// Add delta fields
		foreach($this->dirty_fields as $field_name => $old_value) {
		    $q->set($field_name);
			$update_args[] = $this->fields_data[$field_name];
		}

		// Add Where clause based on primary keys.
		// Note: We must use old values if pk are changed 
		// otherwise we will write over a wrong record.
		foreach($this->model->getPkFields() as $field_name => $pk) {
		    $q->where("{$pk} = ?");
		    if (isset($this->dirty_fields[$pk]))
		        $update_args[] = $this->dirty_fields[$pk];
		    else
    			$update_args[] = $this->fields_data[$pk];
		}

		// Execute query
		$res = call_user_func_array(array($q, 'execute'), $update_args);
		if ((!$res) || ($res->affected_rows !== 1))
            return false;

        // Clear dirty fields
        $this->dirty_fields = array();

        // Event notification
        self::notifyEvent(
            $this->model->getName(),
            'post-update',
            array('record' => $this, 'model' => $this->model->getName()));
            
		return true;
	}
	 
	/**
	 * Request to delete this record from database.
	 * 
	 * It will delete the record from database. However the object
	 * will not be destroyed so be carefull to dump it after deletion.
     * @return - @b true If the record was succesfully deleted.
     *  - @b false On any kind of error.
	 */
	public function delete()
	{	
        // Event notification
		$cancel = false;
        self::filterEvent(
            $this->model->getName(),
            'pre-delete',
            $cancel,
            array('model' => $this->model->getName(), 'record' => $this)
        );
        if ($cancel)
            return false;
            
		// Create delete query
		$delete_args = array();
		$q = self::rawQuery($this->model->getName())
			->delete()
			->limit(1);
		
		// Add Where clause based on primary keys
		foreach($this->getKeyValues(true) as $pk => $value) {
			$q->where("{$pk} = ?");
			$delete_args[] = $value;
		}
		
		// Execute query
		$res = call_user_func_array(array($q, 'execute'), $delete_args);
		if ((!$res) || ($res->affected_rows !== 1))
		    return false;

        // Post-Event notification
        self::notifyEvent(
            $this->model->getName(),
            'post-delete',
            array('record' => $this, 'model' => $this->model->getName()));

		return true;
	}

	/**
	 * Get the key values of this record
	 */
	public function getKeyValues()
	{	
		return array_intersect_key(
			$this->fields_data, 
			$this->model->getPkFields(true));
	}
	
	/**
	 * Get all the field data to an array.
	 * @return array Associative array with all data.
	 */
	public function getArray()
	{
		return $this->fields_data;
	}
	
	/**
	 * Get the value of a field.
	 * 
	 * It will return data of any field that you request. Data will be 
	 * converted from sql format to user format before returned. This means
	 * that fields of type "datetime" will be converted to php native DateTime object,
	 * "serialized" fields will be unserialized before returned to user.
	 * 
	 * @param $name
	 * @return 
	 * 	- The data of the field converted in user format.
	 * 	- @b NULL if there is no field with that name. In that case a php error will be triggered too.
	 *	.
	 *
	 * @note __get() and __set() are php magic methods and can be declare to overload the 
	 *  standard procedure of accesing object properties. It is @b not not nessecary to
	 *  use them as function @code echo $record->__get('myfield'); @endcode but use them as
	 *  object properties @code echo $record->myfield; @endcode
	 * 
	 * @see __set()
	 */
	public function __get($name)
	{
		if ($this->model->hasField($name)) {
			// Check for data
			return $this->model->unpackFieldData(
				$name,
				$this->fields_data[$name]
			);
		}
		
		if ($this->model->hasRelationship($name)) {
			$rel = $this->model->getRelationshipInfo($name);
			
			if ($rel['type'] === 'one') {
				return self::open(
					$this->__get($this->model->getFkFieldFor($rel['foreign_model'])),
					$rel['foreign_model']
				);
			}
			if ($rel['type'] === 'many') {	
				$pk = current($this->getKeyValues());
				return new Record\RelationshipMany(
			        $this->model,
					$rel['foreign_model'],
					$pk);
			}

			if ($rel['type'] === 'bridge') { 
				$pk = current($this->getKeyValues());
			    return new Record\RelationshipBridge(
			        $this->model,
			        $rel['bridge_model'],
			        $rel['foreign_model'],
			        $pk
			    );
			}
			
			throw new \RuntimeException("Unknown Record relation type '{$rel['type']}'");
		}
		
		// Oops!
		$trace = debug_backtrace();
		throw new \InvalidArgumentException("{$this->model->getName()}(Record)->{$name}" . 
			" is not valid field of model {$this->model->getName()}, requested at {$trace[0]['file']} ".
			" on line {$trace[0]['line']}");
	}
	
	/**
	 * Set the value of a field
	 */
	public function __set($name, $value)
	{
		if ($this->model->hasField($name)) {
			// Mark it as dirty and save old value
			$this->dirty_fields[$name] = $this->fields_data[$name];
			
			// Set data
			return $this->fields_data[$name] = 
				$this->model->packFieldData(
					$name,
					$value
				);
		}
		
		if ($this->model->hasRelationship($name)) {
			$rel = $this->model->getRelationshipInfo($name);
			
			if ($rel['type'] == 'one') {
				if (is_object($value)) {
					$fm = Model::open($rel['foreign_model']);
					$pks = $fm->getPkFields();
					$this->__set(
					    $this->model->getFkFieldFor($rel['foreign_model']),
					    $value->__get($pks[0]));
				} else {
					$this->__set(
					    $this->model->getFkFieldFor($rel['foreign_model']),
					    $value
					);
				}
				return $value;
			}
			
			if ($rel['type'] == 'many')
				return false;
			
			throw new \RuntimeException("Unknown Record relation type '{$rel['type']}'");
		}
		
		// Oops!
	    $trace = debug_backtrace();
		throw new \InvalidArgumentException("{$this->model->getName()}(Record)->{$name}" . 
			" is not valid field of model {$this->model->getName()}, requested at {$trace[0]['file']} ".
			" on line {$trace[0]['line']}");
	}

	/**
	 * Check if a field or relationship exists.
	 * @param string $name The name of field or relationship
	 */
	public function __isset($name)
	{   
		if (($this->model->hasField($name))
		    ||  ($this->model->hasRelationship($name)))
		    return true;
		return false;
    }
	
	
	/**
	 * Serialization implementation	
	 */
	public function __sleep()
	{
	    return array('fields_data', 'dirty_fields');
	}
	
	/**
	 * Unserilization implementation
	 */
	public function __wakeup()
	{	
		// Initialize static
		$this->model = self::initModel(get_class($this));
	}
}
