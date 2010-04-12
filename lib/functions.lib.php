<?php
/*******************************************
 @file Every-day functions
 */

//! Convert date to text using the standard format
/** 
	The format can be defined by assinging it to the
	global variable $GLOBALS['default_date_format'],
	otherwise the 'j F Y (H:i)' is used
*/
function dateLongFormat($ndate)
{
	if (!isset($GLOBALS['default_date_format']))
		return date('j F Y (H:i)',$ndate);
	return date($GLOBALS['default_date_format'], $ndate);
}

//! Convert date to a small text using the standard format
/** 
	The format can be defined by assinging it to the
	global variable $GLOBALS['default_date_smallformat'],
	otherwise the 'j M Y (H:i)' is used
*/
function dateSmallFormat($ndate)
{
	if (!isset($GLOBALS['default_date_smallformat']))
		return date('j M Y (H:i)',$ndate);
	return date($GLOBALS['default_date_smallformat'], $ndate);
}

//! Convert a date to textual format in using human intelligent format
/** 
	If the date is today it will return 'Today h:i a' else it will
	return the month, day and hour. If the date is in different year
	it will return the year too.
*/
function dateSmartFormat($ndate)
{	$currentTime = time();
	$currentTimeDay = date('d m Y', $currentTime);
	$ndateDay = date('d m Y', $ndate);
	if ($currentTimeDay == $ndateDay)
		return 'Today '.date('h:i a', $ndate);
	if (date('Y', $currentTime) == date('Y', $ndate))
		return substr(date('F', $ndate), 0, 3) . date(' d,  h:i a', $ndate);
		
	return substr(date('F', $ndate), 0, 3) . date(' d, Y', $ndate);
}

//! Convert a date to a textual format that represents the time passed till now.
function dateSmartDiffFormat($ndate)
{	$currentTime = time();
	$currentTimeDay = date('d m Y', $currentTime);
	$ndateDay = date('d m Y', $ndate);
	if ($currentTimeDay == $ndateDay)
	{	$tdiff = abs($ndate - $currentTime);
		if (abs($tdiff) <= 60)
			return 'some moments ago';
		else if (abs($tdiff) <= 3600)
			return floor($tdiff / 60) . ' minutes ago';
		
		return floor($tdiff / (24*60)) . ' hours ago';
	}
	if (date('Y', $currentTime) == date('Y', $ndate))
		return substr(date('F', $ndate), 0, 3) . date(' d,  h:i a', $ndate);
		
	return substr(date('F', $ndate), 0, 3) . date(' d, Y', $ndate);
}

//! Human readable file size
/**
 * It is preferable to display size closer to the unit that
 * results with less digits and without using floating point. It is better
 * to use 1.2K or 1K than 1200bytes.
 */
function human_html_fsize($size, $postfix = 'ytes')
{	if ($size < 1024)
		return $size . ' b' . $postfix;
	else if ($size < 1048576)
		return ceil($size/1024) . ' KB' . $postfix;
	else if ($size < 1073741824)
		return ceil($size/1048576) . ' MB' . $postfix;
	return ceil($size/1073741824) . ' GB' . $postfix;
}
//! Human-friendly date representation
/** 
	Humans usually prefer the time in differnce of the present,
	this function will return a human representation of a DateTime
	object, enclosed in a \<span\> element with a detailed tooltip
	of the time event.
*/
function human_html_date($dt)
{	$full_date = $dt->format('D, j M, Y \a\t H:i:s');
	$sec_diff = abs($dt->format('U') - time());
	
	$ret = '<span title="' . $full_date . '">';

	if ($sec_diff <= 60)	// Same minute
		$ret .= 'some moments ago';
	else if ($sec_diff <= 3600)	// Same hour
		$ret .= floor($sec_diff / 60) . ' minutes ago';
	else if ($sec_diff <= 86400)	// Same day
		$ret .= floor($sec_diff / 3600) . ' hours ago';
	else /*if ($sec_diff <= (86400 * 14))	// Same last 2 weeks
		$ret .= $dt->format('M j') . '(' . floor($sec_diff / 86400) . ' days ago)';*/
	{	$cur_date = getdate();
		$that_date = getdate($dt->format('U'));
		
		if ($cur_date['year'] == $that_date['year'])
			$ret .=$dt->format('M d, H:i');
		else
			$ret .= $dt->format('d/m/Y');
	}
	
	$ret .= '</span>';
	return $ret;
}

// Sample a part of the text and return the result with three dots at the end (if needed)
function text_sample($text, $length)
{	$text_length = strlen($text);
	
	if ($text_length < $length)
		return $text;
		
	return substr($text, 0, $length - 3) . '...';
}

//! Escape all html control characters from a text and return the result
function esc_html($text)
{
	return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

//! Escape javascript code
function esc_js($str)
{
    $str = mb_ereg_replace("\\\\", "\\\\", $str);
    $str = mb_ereg_replace("\"", "\\\"", $str);
    $str = mb_ereg_replace("'", "\\'", $str);
    $str = mb_ereg_replace("\r\n", "\\n", $str);
    $str = mb_ereg_replace("\r", "\\n", $str);
    $str = mb_ereg_replace("\n", "\\n", $str);
    $str = mb_ereg_replace("\t", "\\t", $str);
    $str = mb_ereg_replace("<", "\\x3C", $str); // for inclusion in HTML
    $str = mb_ereg_replace(">", "\\x3E", $str);
    return $str;
}

// Find links in html text and linkfy them
function linkify_urls($text, $replace_text = '<a href="${0}" target="_blank">${0}</a>')
{
	return preg_replace('/((?:http|ftp):\/\/[^\s\<\>]*)/im', $replace_text, $text);
}


//! Redirect browser to a new url
function redirect($path, $auto_exit = true)
{   header('Location: '. $path);  if ($auto_exit) exit;  }


//! Add google analytics code
function ga_code($site_id, $return_code = false)
{
	$code = '<script type="text/javascript">';
	$code .= 'var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");';
	$code .= 'document.write(unescape("%3Cscript src=\'" + gaJsHost + "google-analytics.com/ga.js\' ';
	$code .= 'type=\'text/javascript\'%3E%3C/script%3E"));';
	$code .= '</script>';
	$code .= '<script type="text/javascript">';
	$code .= 'try {';
	$code .= 'var pageTracker = _gat._getTracker("' . $site_id . '");';
	$code .= 'pageTracker._trackPageview();';
	$code .= '} catch(err) {}</script>';
	if ($return_code)
		return $code;
	
	echo $code;
	return true;
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
	if (version_compare(PHP_VERSION, '5.3.0', '>='))
		error_log('get_static_var() should not be used with PHP >= 5.3 as there is native support.!');
		
	return eval("return {$class_name}::\${$var_name};");
}

function isset_static_var($class_name, $var_name)
{
	if (version_compare(PHP_VERSION, '5.3.0', '>='))
		error_log('isset_static_var() should not be used with PHP >= 5.3 as there is native support.!');
		
	return eval("return isset({$class_name}::\${$var_name});");
}

?>
