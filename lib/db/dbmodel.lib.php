<?php

//! The schema of model
class DBModel
{
	//! An array with all models
	static private $models = array();
	
	//! The model cache
	static private $model_cache = NULL;
	
	//! Open a model
	static public function open($model_name)
	{	// Check in session cache
		if (self::exists($model_name))
			return self::$models[$model_name];
			
		// If model caching is disabled, quit
		if (self::$model_cache === NULL)
			return NULL;
		
		// Check cache
		$md = self::$model_cache->get('model-' . $model_name, $succ);
		if ($succ)
		{	self::$models[$model_name] = $md;
			return $md;
		}
		
		// Otherwise return false
		return NULL;
	}
	
	//! Create a model
	static public function create($model_name, $table, $fields, $relationships)
	{	
		// Return error if already existign
		if (self::exists($model_name))
			return false;
			
		$md = new DBModel($model_name, $table, $fields, $relationships);
		self::$models[$model_name] = $md;
		
		// Save in model cache
		if (self::$model_cache !== NULL)
			self::$model_cache->set('model-' . $model_name, $md);
		
		return $md;
	}
	
	//! Check if a model exists
	static public function exists($model_name)
	{	return isset(self::$models[$model_name]);	}
	
	//! Define the model cache
	static public function set_model_cache($cache)
	{
		self::$model_cache = $cache;
	}
	
	//! Get the model cache
	static public function get_model_cache()
	{	return self::$model_cache;	}
	
	//! The actual meta data
	private $meta_data = NULL;
		
	//! Create a DBModel object
	final private function __construct($model_name, $table, $fields, $relationships)
	{
		$info = array('pk' => array(), 'ai' => array());
		
		// Validate and copy all fields
		$filtered_fields = array();
		foreach($fields as $field_name => $field)
		{	// Check if it was given as number entry or associative entry
			if (is_numeric($field_name) && is_string($field))
			{	$field_name = $field; 
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
				'unique' => false
			);
			$filtered_field = array_merge($default_field_options, $field);
			
			// Find primary key(s)
			if ($filtered_field['pk'])
			{
				$filtered_field['unique'] = true;
				$info['pk'][$filtered_field['name']] = $filtered_field;
				if ($filtered_field['ai'])
					$info['ai'][$filtered_field['name']] = $filtered_field;
			}
			else if ($filtered_field['ai'])
				$filtered_field['ai'] = false;
				
			$filtered_fields[$field_name] = $filtered_field;
		}
		
		// Store data in meta database
		$this->meta_data = array(
			'fields' => $filtered_fields, 
			'table' => $table,
			'relationships' => $relationships,
			'model' => $model_name,
			'pk' => $info['pk'],
			'ai' => $info['ai']
		);
		
		// Add more statistical data
		$this->meta_data['field_names'] = array_keys($this->meta_data['fields']);
	}
	
	//! The name of the model
	public function name()
	{	return $this->meta_data['model'];	}
	
	//! Name of table associated with the model
	public function table()
	{	return $this->meta_data['table'];	}
	
	//! All the fields of this model
	public function fields($fields_info = false)
	{	if ($fields_info === false)
			return array_keys($this->meta_data['fields']);
		else
			return $this->meta_data['fields'];
	}
	
	//! Get the primary key fields
	public function pk_fields($fields_info = false)
	{	if ($fields_info === false)
			return array_keys($this->meta_data['pk']);
		return $this->meta_data['pk'];
	}
	
	//! Get the autoincrement fields
	public function ai_fields($fields_info = false)
	{	if ($fields_info === false)
			return array_keys($this->meta_data['ai']);
		return $this->meta_data['ai'];
	}
	
	//! Check if there is a field
	public function has_field($name)
	{	return isset($this->meta_data['fields'][$name]);	}
	
	//! Query fields properties
	/**
	 * Ask for a property of a field or all of them.
	 * @param $name The name of the field as it was defined in model
	 * @param $property Specify proparty by name or pass NULL to get all properties in an array.
	 * @return The string with the property value or an associative array with all properties.
	 */
	public function field_info($name, $property = NULL)
	{
		if (!isset($this->meta_data['fields'][$name]))
			return NULL;
		if ($property === NULL)
			return $this->meta_data['fields'][$name];
		if (!isset($this->meta_data['fields'][$name][$property]))
			throw InvalidArgumentException("There is no field property with name $property");
		return $this->meta_data['fields'][$name][$property];
	}
	
	//! Get a field's friendly name based on sqlfield value
	/**
	 * @param $sqlfield The name of field as it is defined in sql table.
	 * @return @b FieldName The name of the field or @b NULL if it was not found
	 */
	public function field_name_by_sqlfield($sqlfield)
	{	foreach($this->meta_data['fields'] as $field)
			if ($field['sqlfield'] === $sqlfield)
				return $field['name'];
		return NULL;
	}
	
	//! Cast data to user format
	public function user_field_data($field_name, $db_data)
	{	
		if (($field = $this->field_info($field_name)) === NULL)
			throw new InvalidArgumentException("There is no field in model {$this->name()} with name $field_name");

		// Fast exit for generic
		if ($field['type'] === 'generic')
			return $db_data;

		// Check cast cache
		if ($field['type'] === 'serialized')
			return unserialize($db_data);
		else if ($field['type'] === 'datetime')
			return new DateTime($db_data);

		// Unknown type return same
		return $db_data;
	}
	
	//! Cast data to db format
	public function db_field_data($field_name, $user_data)
	{
		if (($field = $this->field_info($field_name)) === NULL)
			throw new InvalidArgumentException("There is no field in model {$this->name()} with name $field_name");

		// Fast exit for generic
		if ($field['type'] === 'generic')
			return $user_data;

		if ($field['type'] === 'serialized')
			return serialize($user_data);
		else if ($field['type'] === 'datetime')
			return $user_data->format(DATE_ISO8601);
		else if ($field['type'] === 'relationship')
			return $description;
		return $user_data;
	}
	
	//! Check if there is a relationship with name
	public function has_relationship($name)
	{	return isset($this->meta_data['relationships'][$name]);	}
	
	//! All the relationships of this model
	public function relationships($info = false)
	{	if ($info === false)
			return array_keys($this->meta_data['relationships']);
		else
			return $this->meta_data['relationships'];
	}
	
	//! Query relationships properties
	/**
	 * Ask for a property of a field or all of them.
	 * @param $name The name of the field as it was defined in model
	 * @param $property Specify proparty by name or pass NULL to get all properties in an array.
	 * @return The string with the property value or an associative array with all properties.
	 */
	public function relationship_info($name, $property = NULL)
	{
		if (!isset($this->meta_data['relationships'][$name]))
			return NULL;
		if ($property === NULL)
			return $this->meta_data['relationships'][$name];
		if (!isset($this->meta_data['relationships'][$name][$property]))
			throw InvalidArgumentException("There is no relationship property with name $property");
		return $this->meta_data['relationships'][$name][$property];
	}
	
	//! Push in model's private cache
	/**
	 * Push something in model's private cache
	 * @param $key A key that must be unique inside the model
	 * @param $obj The object to push
	 * @return @b TRUE if it was cached succesfully.
	 */
	public function push_cache($key, $obj)
	{	if (self::$model_cache === NULL)
			return false;
		
		return self::$model_cache->set('dbmodel[' . $this->name() . ']' . $key, $obj);
	}
	
	//! Fetch from model's private cache
	/**
	 * Fetch something from model's private cache
	 * @param $key The key of the slot in model's cache
	 * @param $succ A by ref boolean that will hold the result of the action 
	 * @return The object that was found inside the cache, or @b NULL if it was not found.
	 */
	public function fetch_cache($key, & $succ)
	{	$succ = false;
		if (self::$model_cache === NULL)
			return NULL;

		$obj = self::$model_cache->get('dbmodel[' . $this->name() . ']' . $key, $rsucc);
		if ($rsucc)
		{	$succ = true;
			return $obj;
		}
		
		return NULL;
	}
	
	//! Invalidates something in model's private cache
	/**
	 * Invalidate (delete) something from model's private cache
	 * @param $key The key of the slot in model's private cache
	 */
	public function invalidate_cache($key)
	{
		if (self::$model_cache === NULL)
			return false;
			
		return self::$model_cache->delete($key);
	}
}
?>
