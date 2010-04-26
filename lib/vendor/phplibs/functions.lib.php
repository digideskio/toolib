<?php

// Sample a part of the text and return the result with three dots at the end (if needed)
function text_sample($text, $length)
{	$text_length = strlen($text);
	
	if ($text_length < $length)
		return $text;
		
	return substr($text, 0, $length - 3) . '...';
}

/* Backport functions */

if (!function_exists('get_called_class'))
{	
	//! This function has been added at php 5.3
	/** 
		Although this hack is working well, it is slow,
		and there are cases that will not work.
	*/
	function get_called_class()
	{	$bt = debug_backtrace();
		$lines = file($bt[1]['file']);
		preg_match('/([a-zA-Z0-9\_]+)::'.$bt[1]['function'].'/',
		           $lines[$bt[1]['line']-1],
		           $matches);
		return $matches[1];
	}
}

if ( !function_exists('sys_get_temp_dir')) {
	function sys_get_temp_dir()
	{
		if( $temp=getenv('TMP') )
			return $temp;
		if( $temp=getenv('TEMP') )
			return $temp;
		if( $temp=getenv('TMPDIR') )
			return $temp;

		$temp=tempnam(__FILE__,'');
		if (file_exists($temp))
		{
			unlink($temp);
			return dirname($temp);
		}
		return null;
	}
}
 
function get_static_var($class_name, $var_name)
{
    /*  Too much noise
	if (version_compare(PHP_VERSION, '5.3.0', '>='))
		error_log('get_static_var() should not be used with PHP >= 5.3 as there is native support.!');
    */
	return eval("return {$class_name}::\${$var_name};");
}

function isset_static_var($class_name, $var_name)
{   /* Too much noise
	if (version_compare(PHP_VERSION, '5.3.0', '>='))
		error_log('isset_static_var() should not be used with PHP >= 5.3 as there is native support.!');
    */    
	return eval("return isset({$class_name}::\${$var_name});");
}

?>
