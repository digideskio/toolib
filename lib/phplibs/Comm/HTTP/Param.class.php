<?php

class Comm_HTTP_Param
{
	//! Assure that a parameter is given through 'get', 'post' or 'both'.
	/**
	 * If the parameter is not set it will terminate the script execution.
	 * @param $name The name of the parameter.
	 * @param $param_type The type of this parameter. Accepted values are 'get', 'post' or 'both'.
	 * @return The value of the parameter
	 */
	public static function assure($name, $param_type = 'both')
	{	if ($param_type == 'post')
			$array = & $_POST;
		else if ($param_type == 'get')
			$array = & $_GET;
		else
			$array = & $_REQUEST;
			
		if (!isset($array[$name]))
			exit;
		return $array[$name];
	}
	
	//! Safe check that a parameter is equal with a value
	/**
	 * @param $name The name of the parameter.
	 * @param $param_type The type of this parameter. Accepted values are 'get', 'post' or 'both'.
	 * @return - @b false If the parameter is not set or the parameter is not equal.
	 *  - @b true If the parameter is set and is equal to $check_value
	 *  .
	 */			
	function is_equal($name, $check_value, $param_type = 'both')
	{	if ($param_type == 'post')
			$array = & $_POST;
		else if ($param_type == 'get')
			$array = & $_GET;
		else
			$array = & $_REQUEST;
	    
	    if (isset($array[$name]) && ($array[$name] == $check_value))
	        return true;        
	    return false;
	}
	
	//! Read the value of a parameter
	/**
	 * @param $name The name of the value
     * @param $param_type The type of this parameter. Accepted values are 'get', 'post' or 'both'.
	 * @return - The value of the parameter.
	 *  - @b NULL If the value is not set at all.
	 *	.
	 */
	function get($name, $param_type = 'both')
	{
		if ($param_type == 'post')
			$array = & $_POST;
		else if ($param_type == 'get')
			$array = & $_GET;
		else
			$array = & $_REQUEST;

		return (isset($array[$name]))?$array[$name]:NULL;
	}
	
	//! Check if a parameter is set
	/**
		@param $param_type The type of this parameter. Accepted values are 'get', 'post' or 'both'.
		@return - @b true If this parameter is set
			- @b false If the parameter is not set
			.
	*/
	function is_set($name, $param_type = 'both')
	{
		if ($param_type == 'post')
			$array = & $_POST;
		else if ($param_type == 'get')
			$array = & $_GET;
		else
			$array = & $_REQUEST;
			
		return isset($array[$name]);
	}
	
		//! Check if a parameter is set and is numeric type
	/**
		@param $param_type The type of this parameter. Accepted values are 'get', 'post' or 'both'.
		@return - @b true If this parameter is set and is numeric
			- @b false If the parameter is not set or it is not numeric
			.
	*/
	function is_numeric($name, $param_type = 'both')
	{
		if ($param_type == 'post')
			$array = & $_POST;
		else if ($param_type == 'get')
			$array = & $_GET;
		else
			$array = & $_REQUEST;
			
		return (isset($array[$name]))?is_numeric($array[$name]):false;;
	}
};

?>
