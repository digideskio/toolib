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
	return htmlspecialchars($text, ENT_QUOTES);
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

//! Escape new lines for html
function esc_nl_html($str)
{	$str = mb_ereg_replace("\r\n", "<br>", $str);
	$str = mb_ereg_replace("\n", "<br>", $str);
	$str = mb_ereg_replace("\r", "<br>", $str);
	return $str;
}

// Find links in html text and linkfy them
function linkify_urls($text, $replace_text = '<a href="${0}" target="_blank">${0}</a>')
{
	return preg_replace('/((?:http|ftp):\/\/[^\s\<\>]*)/im', $replace_text, $text);
}

//! Assure that a GET parameter is set and return it
function assert_get_parameter($name)
{	if (!isset($_GET[$name]))
		exit;
	return $_GET[$name];
}

//! Assure that a POST parameter is set and return it
function assert_post_parameter($name)
{	if (!isset($_POST[$name]))
		exit;
	return $_POST[$name];
}

//! Assure that a request (post or get) parameter is set and return it
function assert_parameter($name)
{	if (!isset($_REQUEST[$name]))
		exit;
	return $_REQUEST[$name];
}

//! Safe check for get parameter
function get_is_equal($key, $val)
{   if (isset($_GET[$key]) && ($_GET[$key] == $val))
        return true;        
    return false;
}

//! Safe check for post parameter
function post_is_equal($key, $val)
{
    if (isset($_POST[$key]) && ($_POST[$key] == $val))
        return true;        
    return false;
}

//! Safe check for request (post or get) parameter
function param_is_equal($key, $val)
{
    if (isset($_REQUEST[$key]) && ($_REQUEST[$key] == $val))
        return true;        
    return false;
}

//! Redirect browser to a new url
function redirect($path)
{   header('Location: '. $path);    }
?>
