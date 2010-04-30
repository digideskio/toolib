<?php

//! Global space to hold configuration information
class Config
{
	protected static $options = array();

	//! Check if an option exists
	public static function exists($name)
	{	if (!isset(self::$options[$name]))
			return false;
		return true;
	}
	
	//! Return null if the option is not found or the value of it
	public static function get($name)
	{	if (!self::exists($name))
			return NULL;
		return self::$options[$name];
	}

	//! Add a new option. Option must not exists
	public static function add($name, $value)
	{	if (self::exists($name))
			return NULL;
		return self::$options[$name] = $value;
	}
	
	//! Change an option. It will be created if needed
	public static function set($name, $value)
	{	
	    return self::$options[$name] = $value;
    }
};
?>
