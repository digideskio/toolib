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
 * Management of Models.
 */
class Model
{
	/**
	 * An array with all models
	 * @var array
	 */
	static private $models = array();
	
	/**
	 * Model caching engine
	 * @var \toolib\Cache
	 */
	static private $model_cache = null;
	
	/**
	 * Database time zone
	 * @var string
	 */
	static public $database_time_zone = 'UTC';
	 
	/**
	 * Open a model from models pool
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
		if (self::$model_cache === null)
			return null;
		
		// Check cache
		$md = self::$model_cache->get('model-' . $model_name, $succ);
		if ($succ) {
			self::$models[$model_name] = $md;
			return $md;
		}
		
		// Otherwise return false
		return null;
	}
	
	/**
	 * Create a new uniquely identified model. This is used from Record on first usage.
	 * @param string $model_name The name of the model
	 * @param string $table The database table that is stored at.
	 * @param array $fields Array with field information.
	 * @param array $relationships Array with relationships informations.
	 * @return \toolib\DB\Model Object of the model.
	 */
	static public function create($model_name, $table, $fields, $relationships)
	{	
		// Return error if already existign
		if (self::exists($model_name))
			return false;
			
		$md = new self($model_name, $table, $fields, $relationships);
		self::$models[$model_name] = $md;
		
		// Save in model cache
		if (self::$model_cache !== null)
			self::$model_cache->set('model-' . $model_name, $md);
		
		return $md;
	}
	
	/**
	 * Check if a model exists
	 * @return boolean
	 *  - @b true if model exists
	 *  - @b if model does not exists.
	 */
	static public function exists($model_name)
	{
		return isset(self::$models[$model_name]);
	}
	
	/** 
	 * Define the model cache storage
	 * @param \toolib\Cache $cache Instance of a Cache repository
	 */
	static public function setModelCache($cache)
	{
		self::$model_cache = $cache;
	}
	
	/**
	 * Get the model cache
	 * @return \toolib\Cache
	 *  - Instance of the cache object.
	 *  - @b null if cache is disabled.
	 */
	static public function getModelCache()
	{
	    return self::$model_cache;
    }
	
	/**
	 * Models actual meta data
	 * @var array
	 */
	private $meta_data = null;
		
	//! Create a Model object
	final private function __construct($model_name, $table, $fields, $relationships)
	{
		// Initialize meta information
		$this->meta_data = array(
			'fields' => array(),
			'field_names' => array(), 
			'table' => $table,
			'relationships' => $relationships,
			'model' => $model_name,
			'pk' => array(),
			'ai' => array(),
			'fk' => array()
		);
		
		// Insert all pre-set fields
		foreach($fields as $name => $options) {				
		    // Check if it was given as number entry or associative entry
			if (is_numeric($name) && is_string($options))
				$this->addField($options, array());
			else 
				$this->addField($name, $options);
		}
	}
	
	/**
	 * Add dynamically a new field on this model
	 * @param string $name The name of the field
	 * @param array $options Options of field
	 */
	public function addField($name, $options)
	{
		// Setup default values of fields
		$normalized_options = array_merge(array(
			'name' => $name,
			'sqlfield' => $name,	
			'type' => 'generic',
			'pk' => false,
			'ai' => false,
			'default' => null,
			'unique' => false,
			'fk' => false
		), $options);
		
		
		// Find key(s)
		if ($normalized_options['pk']) {
			$normalized_options['unique'] = true;
			$this->meta_data['pk'][$name] = $normalized_options;
			if ($normalized_options['ai'])
				$this->meta_data['ai'][$name] = $normalized_options;
		} else if ($normalized_options['ai']) {
			$normalized_options['ai'] = false;
		}

		if ($normalized_options['fk'] != false)
		    $this->meta_data['fk'][$name] = $normalized_options;
		
		$this->meta_data['fields'][$name] = $normalized_options;
		$this->meta_data['field_names'][] = $name;
	}

	/**
	 * Get the name of this model.
	 * @return string
	 */
	public function getName()
	{
	    return $this->meta_data['model'];
    }

	/**
	 * Get the table name associated with this model
	 * @return string
	 */
	public function getTable()
	{
	    return $this->meta_data['table'];
    }
	
	/**
	 * Get all fields of this model.
	 * @param boolean $fields_info
	 *  - @b true To get all fields information.
	 *  - @b false To get only the name of the fields.
	 * @return array
	 *  - @b associative @b array with fields and their info or
	 *  - @b array with field names.  
	 */
	public function getFields($fields_info = false)
	{
	    if ($fields_info === false)
			return array_keys($this->meta_data['fields']);
		else
			return $this->meta_data['fields'];
	}
	
	/**
	 * Get primary key fields of this model
	 * @param boolean $fields_info
	 *  - @b true To get all fields information.
	 *  - @b false To get only the name of the fields.
	 * @return array
	 *  - @b associative @b array with fields and their info or
	 *  - @b array with field names.  
	 */
	public function getPkFields($fields_info = false)
	{
	    if ($fields_info === false)
			return array_keys($this->meta_data['pk']);
		return $this->meta_data['pk'];
	}

	/**
	 * Get auto_increment key fields of this model
	 * @param boolean $fields_info
	 *  - @b true To get all fields information.
	 *  - @b false To get only the name of the fields.
	 * @return array
	 *  - @b associative @b array with fields and their info or
	 *  - @b array with field names.  
	 */
	public function getAiFields($fields_info = false)
	{
	    if ($fields_info === false)
			return array_keys($this->meta_data['ai']);
		return $this->meta_data['ai'];
	}

	/**
	 * Get foreign key fields of this model
	 * @param boolean $fields_info
	 *  - @b true To get all fields information.
	 *  - @b false To get only the name of the fields.
	 * @return
	 *  - @b associative @b array with fields and their info or
	 *  - @b array with field names.  
	 */
	public function getFkFields($fields_info = false)
	{
	    if ($fields_info === false)
			return array_keys($this->meta_data['fk']);
		return $this->meta_data['fk'];
	}

	/**
	 * Find the foreign key that references to a foreign model
	 * @param string $model The model that fk references to.
	 * @param boolean $fields_info
	 *   - @b true To get all fields information.
	 *   - @b false To get only the name of the fields.
	 * @return 
	 *  - @b associative @b array All the information of the field.
	 *  - @b string The name of the field.
	 *  - @b null If there is no foreign key for this model or on any error.
	 */
	public function getFkFieldFor($model, $field_info = false)
	{  
	    foreach($this->meta_data['fk'] as $fk)
	    {
	    	if ($fk['fk'] === $model)
	            if ($field_info)
	                return $fk;
	            else
	                return $fk['name'];
	    }
	    return null;
	}
	
	/**
	 * Check if there is a field
	 * @return boolean
	 *  - @b true if field exist
	 *  - @b false if the name is unknown.
	 */
	public function hasField($name)
	{
	    return isset($this->meta_data['fields'][$name]);
    }
	
	/**
	 * Get one or all properties of a field.
	 * @param string $name The name of the field as it was defined in model
	 * @param string $property Specify property by name or pass NULL to get all properties in an array.
	 * @return string The string with the property value or an associative array with all properties.
     * @throws \InvalidArgumentException if the $property was unknown.
	 */
	public function getFieldInfo($name, $property = null)
	{
		if (!isset($this->meta_data['fields'][$name]))
			return null;
		if ($property === null)
			return $this->meta_data['fields'][$name];
		if (!isset($this->meta_data['fields'][$name][$property]))
			throw \InvalidArgumentException("There is no field property with name $property");
		return $this->meta_data['fields'][$name][$property];
	}
	
	/**
	 * Get a field's friendly name based on sqlfield value
	 * @param string $sqlfield The name of field as it is defined in sql table.
	 * @return string The name of the field or @b NULL if it was not found
	 */
	public function getFieldNameBySqlfield($sqlfield)
	{
	    foreach($this->meta_data['fields'] as $field)
			if ($field['sqlfield'] === $sqlfield)
				return $field['name'];
		return null;
	}
	
	/**
	 * Unpack data from database to runtime enviroment.
	 * @param string $name The name of the field that data belongs to.
	 * @param $db_data The data to be unpacked.
	 * @return The data converted to @e runtime format based on the @e type of the field.
	 * @throws \InvalidArgumentException if $field_name is not valid
	 */
	public function unpackFieldData($field_name, $db_data)
	{	
		if (($field = $this->getFieldInfo($field_name)) === null)
			throw new \InvalidArgumentException("There is no field in model {$this->getName()} with name $field_name");

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
	
	/**
	 * Pack data from runtime enviroment to database format.
	 * @param string $field_name The name of the field that data belongs to.
	 * @param $runtime_data The data to converted
	 * @return The data casted to @e db format based on the @e type of the field.
	 * @throws \InvalidArgumentException if $field_name is not valid
	 */
	public function packFieldData($field_name, $runtime_data)
	{
		if (($field = $this->getFieldInfo($field_name)) === null)
			throw new \InvalidArgumentException("There is no field in model {$this->getName()} with name $field_name");

        // Short exit for null
        if ($runtime_data === null)
            return null;
            
		// Short exit for generic
		if ($field['type'] === 'generic')
			return (string) $runtime_data;
			
		if ($field['type'] === 'serialized')
			return serialize($runtime_data);
		else if ($field['type'] === 'datetime') {   
			return $runtime_data->setTimeZone(new \DateTimeZone(self::$database_time_zone))
				->format(DATE_ISO8601);
        }
		else if ($field['type'] === 'relationship')
			return $description;
		return (string) $runtime_data;
	}
	
	/**
	 * Check if there is a relationship with name
	 * @param string $name The name of the relationship
	 * @return boolean
	 *  - @b true if it exists.
	 *  - @b false if it does not exist.
	 */
	public function hasRelationship($name)
	{
	    return isset($this->meta_data['relationships'][$name]);
    }
	
	/**
	 * Get all the relationships of this model
	 * @param boolean $info
	 *   - @b true To get all relationships information.
	 *   - @b false To get only the name of the relationships.
	 * @return array
	 *  - @b associative @b array All the information of the field.
	 *  - @b array with the relationship names.
	 */
	public function getRelationships($info = false)
	{  
	    if ($info === false)
			return array_keys($this->meta_data['relationships']);
		else
			return $this->meta_data['relationships'];
	}
	
	/**
	 * Get one or all properties of a relationship.
	 * @param string $name The name of the relationship as it was defined in model
	 * @param string $property Specify property by name or pass NULL to get all properties in an array.
	 * @return The string with the property value or an associative array with all properties.
	 */
	public function getRelationshipInfo($name, $property = null)
	{
		if (!isset($this->meta_data['relationships'][$name]))
			return null;
		if ($property === null)
			return $this->meta_data['relationships'][$name];
		if (!isset($this->meta_data['relationships'][$name][$property]))
			throw \InvalidArgumentException("There is no relationship property with name $property");
		return $this->meta_data['relationships'][$name][$property];
	}
	
	/**
	 * Push something in model's private cache
	 * @param string $key A key that must be unique inside the model
	 * @param $obj The object to push
	 * @return boolean @b trueif it was cached succesfully.
	 */
	public function cachePush($key, $obj)
	{
	    if (self::$model_cache === null)
			return false;
		
		return self::$model_cache->set('dbmodel[' . $this->getName() . ']' . $key, $obj);
	}
	
	/**
	 * Fetch something from model's private cache
	 * @param string $key The key of the slot in model's cache
	 * @param [out] boolean $succ A by ref boolean that will hold the result of the action 
	 * @return The object that was found inside the cache, or @b NULL if it was not found.
	 */
	public function cacheFetch($key, & $succ)
	{
	    $succ = false;
		if (self::$model_cache === null)
			return null;

		$obj = self::$model_cache->get('dbmodel[' . $this->getName() . ']' . $key, $rsucc);
		if ($rsucc) {
		    $succ = true;
			return $obj;
		}
		
		return null;
	}
	
	/**
	 * Invalidate (delete) something from model's private cache
	 * @param string $key The key of the slot in model's private cache
	 */
	public function cacheInvalidate($key)
	{
		if (self::$model_cache === null)
			return false;
			
		return self::$model_cache->delete($key);
	}
}
