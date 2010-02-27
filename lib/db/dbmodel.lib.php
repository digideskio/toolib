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
			return false;
		
		// Check cache
		$md = self::$model_cache->get('model-' . $model_name, $succ);
		if ($succ)
		{	self::$models[$model_name] = $md;
			return $md;
		}
		
		// Otherwise return false
		return false;
	}
	
	//! Create a model
	static public function create($model_name, & $meta_data)
	{	
		// Return error if already existign
		if (self::exists($model_name))
			return false;
			
		$md = new DBModel($meta_data);
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
	final private function __construct(&$meta_data)
	{
		$this->meta_data = & $meta_data;
		
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
	
	//! Query fields properties
	/**
	 * Ask for a propertiy of a field or all of them.
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
	
	//! Get the primary key of this model
	public function primary_key($index = NULL)
	{
		return $this->meta_data['pk'];
	}
	
	//! Get the autoincrement fields
	public function autoincrement_fields($index = NULL)
	{
		return $this->meta_data['ai'];
	}
	
	//! Cast data to user format
	public function user_field_data($field_name, $db_data)
	{	if (($field = $this->field($field_name)) === NULL)
			throw new InvalidArgumentException("There is no field in model {$this->name()} with name $field_name");
	
		if ($field['type'] == 'serialized')
			return unserialize($db_data);
		else if ($field['type'] == 'datetime')
			return new DateTime($db_data);
		return $db_data;
	}
	
	//! Cast data to db format
	public function db_field_data($field_name, $user_data)
	{
		if (($field = $this->field($field_name)) === NULL)
			throw new InvalidArgumentException("There is no field in model {$this->name()} with name $field_name");
			
		if ($field['type'] == 'serialized')
			return serialize($user_data);
		else if ($field['type'] == 'datetime')
			return $user_data->format(DATE_ISO8601);
		else if ($field['type'] == 'relationship')
			return $description;
		return $user_data;
	}
	
	//! Push in model's cache
	/**
	 * Push something in model's cache
	 * @param $key A key that must be unique inside the model
	 * @param $obj The object to push
	 * @return @b TRUE if it was cached succesfully.
	 */
	public function push_cache($key, $obj)
	{	if (self::$model_cache === NULL)
			return false;
		
		return self::$model_cache->set('dbmodel[' . $this->name() . ']' . $key, $obj);
	}
	
	//! Fetch from model's cache
	/**
	 * Fetch something from model's cache
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
	
	//! Invalidates something in model's cache
	/**
	 * Invalidate (delete) something from model's cache
	 * @param $key The key of the slot in model's cache
	 */
	public function invalidate_cache($key)
	{
		if (self::$model_cache === NULL)
			return false;
			
		return self::$model_cache->delete($key);
	}
}
?>