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

/**
 * Repository for model information
 */
class Model
{
	//! An array with all models
	static private $models = array();
	
	//! The model cache
	static private $model_cache = NULL;
	
	//! Database time zone
	static public $database_time_zone = 'UTC';
	
	//! Open a model from models pool
	/**
	 * @return \toolib\DB\Model
	 *  - @b Object with model information
	 *  - @b NULL if model was not found.
	 */
	static public function open($model_name)
	{
		// Check in session cache
		if (self::exists($model_name))
			return self::$models[$model_name];
			
		// If model caching is disabled, quit
		if (self::$model_cache === NULL)
			return NULL;
		
		// Check cache
		$md = self::$model_cache->get('model-' . $model_name, $succ);
		if ($succ) {
			self::$models[$model_name] = $md;
			return $md;
		}
		
		// Otherwise return false
		return NULL;
	}
	
	//! Create a new model
	/**
	 * This is used from Record on first usage.
	 * @param string $model_name
	 * @param string $table
	 * @param array $fields
	 * @param array $relationships
	 * @return toolib\DB\Model Object of the model.
	 */
	static public function create($model_name, $table, $fields, $relationships)
	{	
		// Return error if already existign
		if (self::exists($model_name))
			return false;
			
		$md = new self($model_name, $table, $fields, $relationships);
		self::$models[$model_name] = $md;
		
		// Save in model cache
		if (self::$model_cache !== NULL)
			self::$model_cache->set('model-' . $model_name, $md);
		
		return $md;
	}
	
	//! Check if a model exists
	/**
	 * @return boolean
	 *  - @b true if model exists
	 *  - @b if model does not exists.
	 */
	static public function exists($model_name)
	{
		return isset(self::$models[$model_name]);
	}
	
	//! Define the model cache storage
	/** 
	 * @param Cache $cache Instance of a Cache repository
	 */
	static public function setModelCache($cache)
	{
		self::$model_cache = $cache;
	}
	
	//! Get the model cache
	/**
	 * @return \toolib\Cache
	 *  - Instance of the cache object.
	 *  - @b null if cache is disabled.
	 */
	static public function getModelCache()
	{
	    return self::$model_cache;
    }
	
	//! The actual meta data
	private $meta_data = NULL;
		
	//! Create a Model object
	final private function __construct($model_name, $table, $fields, $relationships)
	{
		$info = array('pk' => array(), 'ai' => array(), 'fk' => array());
		
		// Validate and copy all fields
		$filtered_fields = array();
		foreach($fields as $field_name => $field) {
				
		    // Check if it was given as number entry or associative entry
			if (is_numeric($field_name) && is_string($field)) {
				$field_name = $field; 
				$field = array();
			}

			// Setup default values of fields
			$default_field_options = array(
				'name' => $field_name,
				'sqlfield' => $field_name,	
				'type' => 'generic',
				'pk' => false,
				'ai' => false,
				'default' => NULL,
				'unique' => false,
				'fk' => false
			);
			$filtered_field = array_merge($default_field_options, $field);
			
			// Find key(s)
			if ($filtered_field['pk']) {
				$filtered_field['unique'] = true;
				$info['pk'][$filtered_field['name']] = $filtered_field;
				if ($filtered_field['ai'])
					$info['ai'][$filtered_field['name']] = $filtered_field;
			} else if ($filtered_field['ai']) {
				$filtered_field['ai'] = false;
			}

			if ($filtered_field['fk'] != false)
			    $info['fk'][$filtered_field['name']] = $filtered_field;
			
			$filtered_fields[$field_name] = $filtered_field;
		}
		
		// Store data in meta database
		$this->meta_data = array(
			'fields' => $filtered_fields, 
			'table' => $table,
			'relationships' => $relationships,
			'model' => $model_name,
			'pk' => $info['pk'],
			'ai' => $info['ai'],
			'fk' => $info['fk']
		);
		
		// Add more statistical data
		$this->meta_data['field_names'] = array_keys($this->meta_data['fields']);
	}
	
	//! The name of the model
	/**
	 * @return string The name of this model.
	 */
	public function name()
	{
	    return $this->meta_data['model'];
    }
	
	//! Name of table associated with the model
	/**
	 * @return string The actual database table name.
	 */
	public function table()
	{
	    return $this->meta_data['table'];
    }
	
	//! Get all fields of this model
	/**
	 * @param boolean $fields_info
	 *  - @b true To get all fields information.
	 *  - @b false To get only the name of the fields.
	 * @return array
	 *  - @b associative @b array with fields and their info or
	 *  - @b array with field names.  
	 */
	public function fields($fields_info = false)
	{
	    if ($fields_info === false)
			return array_keys($this->meta_data['fields']);
		else
			return $this->meta_data['fields'];
	}
	
	//! Get primary key fields of this model
	/**
	 * @param boolean $fields_info
	 *  - @b true To get all fields information.
	 *  - @b false To get only the name of the fields.
	 * @return array
	 *  - @b associative @b array with fields and their info or
	 *  - @b array with field names.  
	 */
	public function pkFields($fields_info = false)
	{
	    if ($fields_info === false)
			return array_keys($this->meta_data['pk']);
		return $this->meta_data['pk'];
	}

	//! Get auto_increment key fields of this model
	/**
	 * @param boolean $fields_info
	 *  - @b true To get all fields information.
	 *  - @b false To get only the name of the fields.
	 * @return array
	 *  - @b associative @b array with fields and their info or
	 *  - @b array with field names.  
	 */
	public function aiFields($fields_info = false)
	{
	    if ($fields_info === false)
			return array_keys($this->meta_data['ai']);
		return $this->meta_data['ai'];
	}

	//! Get foreign key fields of this model
	/**
	 * @param boolean $fields_info
	 *  - @b true To get all fields information.
	 *  - @b false To get only the name of the fields.
	 * @return
	 *  - @b associative @b array with fields and their info or
	 *  - @b array with field names.  
	 */
	public function fkFields($fields_info = false)
	{
	    if ($fields_info === false)
			return array_keys($this->meta_data['fk']);
		return $this->meta_data['fk'];
	}

	//! Find the foreign key that references to a foreign model
	/**
	 * @param string $model The model that fk references to.
	 * @param boolean $fields_info
	 *   - @b true To get all fields information.
	 *   - @b false To get only the name of the fields.
	 * @return 
	 *  - @b associative @b array All the information of the field.
	 *  - @b string The name of the field.
	 *  - @b null If there is no foreign key for this model or on any error.
	 */
	public function fkFieldFor($model, $field_info = false)
	{  
	    foreach($this->meta_data['fk'] as $fk)
	    {
	    	if ($fk['fk'] === $model)
	            if ($field_info)
	                return $fk;
	            else
	                return $fk['name'];
	    }
	    return NULL;
	}
	
	//! Check if there is a field
	/**
	 * @return boolean
	 *  - @b true if field exist
	 *  - @b false if the name is unknown.
	 */
	public function hasField($name)
	{
	    return isset($this->meta_data['fields'][$name]);
    }
	
	//! Query fields properties
	/**
	 * Ask for a property of a field or all of them.
	 * @param string $name The name of the field as it was defined in model
	 * @param string $property Specify property by name or pass NULL to get all properties in an array.
	 * @return string The string with the property value or an associative array with all properties.
     * @throws \InvalidArgumentException if the $property was unknown.
	 */
	public function fieldInfo($name, $property = NULL)
	{
		if (!isset($this->meta_data['fields'][$name]))
			return NULL;
		if ($property === NULL)
			return $this->meta_data['fields'][$name];
		if (!isset($this->meta_data['fields'][$name][$property]))
			throw \InvalidArgumentException("There is no field property with name $property");
		return $this->meta_data['fields'][$name][$property];
	}
	
	//! Get a field's friendly name based on sqlfield value
	/**
	 * @param string $sqlfield The name of field as it is defined in sql table.
	 * @return string @b FieldName The name of the field or @b NULL if it was not found
	 */
	public function fieldNameBySqlfield($sqlfield)
	{
	    foreach($this->meta_data['fields'] as $field)
			if ($field['sqlfield'] === $sqlfield)
				return $field['name'];
		return NULL;
	}
	
	//! Cast data db -> user 
	/**
	 * @param string $field_name The name of the field that data belongs to.
	 * @param $db_data The data to be casted
	 * @return The data casted to @e user format based on the @e type of the field.
	 * @throws \InvalidArgumentException if $field_name is not valid
	 */
	public function userFieldData($field_name, $db_data)
	{	
		if (($field = $this->fieldInfo($field_name)) === NULL)
			throw new \InvalidArgumentException("There is no field in model {$this->name()} with name $field_name");

		// Short exit for generic
		if ($field['type'] === 'generic')
			return $db_data;

        // Short exit for null
        if ($db_data === null)
            return null;
            
		// Check cast cache
		if ($field['type'] === 'serialized')
			return unserialize($db_data);
		else if ($field['type'] === 'datetime')
		{
		    $utc_time = new \DateTime($db_data, new \DateTimeZone(self::$database_time_zone));
			$utc_time->setTimeZone(new \DateTimeZone(date_default_timezone_get()));
			return $utc_time;
        }

		// Unknown type return same
		return $db_data;
	}
	
	//! Cast data user -> db 
	/**
	 * @param string $field_name The name of the field that data belongs to.
	 * @param $user_data The data to be casted
	 * @return The data casted to @e db format based on the @e type of the field.
	 * @throws \InvalidArgumentException if $field_name is not valid
	 */
	public function dbFieldData($field_name, $user_data)
	{
		if (($field = $this->fieldInfo($field_name)) === NULL)
			throw new \InvalidArgumentException("There is no field in model {$this->name()} with name $field_name");

        // Short exit for null
        if ($user_data === null)
            return null;
            
		// Short exit for generic
		if ($field['type'] === 'generic')
			return (string) $user_data;
			
		if ($field['type'] === 'serialized')
			return serialize($user_data);
		else if ($field['type'] === 'datetime') {   
			return $user_data->setTimeZone(new \DateTimeZone(self::$database_time_zone))
				->format(DATE_ISO8601);
        }
		else if ($field['type'] === 'relationship')
			return $description;
		return (string) $user_data;
	}
	
	//! Check if there is a relationship with name
	/**
	 * @param string $name The name of the relationship
	 * @return boolean
	 *  - @b true if it exists.
	 *  - @b false if it does not exist.
	 */
	public function hasRelationship($name)
	{
	    return isset($this->meta_data['relationships'][$name]);
    }
	
	//! All the relationships of this model
	/**
	 * 
	 * @param boolean $info
	 *   - @b true To get all relationships information.
	 *   - @b false To get only the name of the relationships.
	 * @return array
	 *  - @b associative @b array All the information of the field.
	 *  - @b array with the relationship names.
	 */
	public function relationships($info = false)
	{  
	    if ($info === false)
			return array_keys($this->meta_data['relationships']);
		else
			return $this->meta_data['relationships'];
	}
	
	//! Query relationships properties
	/**
	 * Ask for a property of a field or all of them.
	 * @param string $name The name of the field as it was defined in model
	 * @param string $property Specify property by name or pass NULL to get all properties in an array.
	 * @return The string with the property value or an associative array with all properties.
	 */
	public function relationshipInfo($name, $property = NULL)
	{
		if (!isset($this->meta_data['relationships'][$name]))
			return NULL;
		if ($property === NULL)
			return $this->meta_data['relationships'][$name];
		if (!isset($this->meta_data['relationships'][$name][$property]))
			throw \InvalidArgumentException("There is no relationship property with name $property");
		return $this->meta_data['relationships'][$name][$property];
	}
	
	//! Push in model's private cache
	/**
	 * Push something in model's private cache
	 * @param string $key A key that must be unique inside the model
	 * @param $obj The object to push
	 * @return boolean @b trueif it was cached succesfully.
	 */
	public function cachePush($key, $obj)
	{
	    if (self::$model_cache === NULL)
			return false;
		
		return self::$model_cache->set('dbmodel[' . $this->name() . ']' . $key, $obj);
	}
	
	//! Fetch from model's private cache
	/**
	 * Fetch something from model's private cache
	 * @param string $key The key of the slot in model's cache
	 * @param [out] boolean $succ A by ref boolean that will hold the result of the action 
	 * @return The object that was found inside the cache, or @b NULL if it was not found.
	 */
	public function cacheFetch($key, & $succ)
	{
	    $succ = false;
		if (self::$model_cache === NULL)
			return NULL;

		$obj = self::$model_cache->get('dbmodel[' . $this->name() . ']' . $key, $rsucc);
		if ($rsucc) {
		    $succ = true;
			return $obj;
		}
		
		return NULL;
	}
	
	//! Invalidates something in model's private cache
	/**
	 * Invalidate (delete) something from model's private cache
	 * @param string $key The key of the slot in model's private cache
	 */
	public function cacheInvalidate($key)
	{
		if (self::$model_cache === NULL)
			return false;
			
		return self::$model_cache->delete($key);
	}
}
