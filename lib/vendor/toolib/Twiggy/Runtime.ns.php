<?php

/**
 * @brief This is the namespace of the generated templates
 */
namespace toolib\Twiggy\Runtime;
use toolib\Twiggy\Frame;

/**
* @brief Escape all html control characters from a text and return the result
*/
function escape_html($what)
{
	return htmlspecialchars($what, ENT_QUOTES, 'UTF-8');
}

/**
 * @brief Escape white space in html
 * @param string $what Text to be escaped
 * @param integer $tab_width Number of spaces of a tab.
 * @param boolena $nobreak If true spaces are replace with non-breakable spaces.
 */
function escape_sp($what, $tab_width = '4', $nobreak = true)
{
	$esc_char = ($nobreak?'&nbsp;':'&ensp;');
	$what = str_replace(' ', $esc_char, $what);
	$what = mb_ereg_replace("\t", str_repeat($esc_char, $tab_width), $what);
	return $what;
}

/**
 * @brief Escape data for javascript strings.
 * @param string $what Text to be escaped
 */
function escape_js($what)
{
	$what = mb_ereg_replace("\\\\", "\\\\", $what);
	$what = mb_ereg_replace("\"", "\\\"", $what);
	$what = mb_ereg_replace("'", "\\'", $what);
	$what = mb_ereg_replace("\r\n", "\\n", $what);
	$what = mb_ereg_replace("\r", "\\n", $what);
	$what = mb_ereg_replace("\n", "\\n", $what);
	$what = mb_ereg_replace("\t", "\\t", $what);
	$what = mb_ereg_replace("<", "\\x3C", $what); // for inclusion in HTML
	$what = mb_ereg_replace(">", "\\x3E", $what);
	return $what;
}

function escape($what, $mode = 'html')
{
	if ($mode === 'js')
		return escape_js($what);
	else
		return escape_html($what);
}

class RawWrapper
{
	public $value;

	public function __construct($value)
	{
		$this->value = $value;
	}

	public function __toString()
	{
		return $this->value;
	}
}

/**
 * @brief Protect the content from being escaped
 * @param string $what Content to be escaped
 */
function raw($what)
{
	return new RawWrapper($what);
}

function autoescape($enabled, $mode = 'html')
{
	Frame::$current->auto_escape = $enabled;
	Frame::$current->escape_mode = $mode;	
}

/**
 * @brief Declare that this template extends another one.
 * @param string $template Name of the template
 * @param array $enviroment Optional extra enviroment to be passed at parent template
 */
function extends_template($template, $enviroment = array())
{
	Frame::$current->extendsTemplate($template, $enviroment);
}

/**
* @brief Include another template at current body
* @param string $template Name of the template
* @param array $enviroment Optional enviroment to be passed at included template
*/
function include_template($template, $enviroment = array())
{
	Frame::$current->includeTemplate($template, $enviroment);
}

/**
 * @brief Start a new block section
 * @param string $name Name of block
 * @param string $content If you pass contents you don't need to call end_block().
 */
function block($name, $content = null)
{
	Frame::$current->startBlock($name, $content);
}

/**
 * @brief Stop last open blocked section and write output.
 * @param string $name Optional name block
 */
function end_block($name = null)
{
	Frame::$current->endBlock($name);
}

/**
 * @brief Print the contents of a block
 * @param string $name Name of the block
 */
function show_block($name)
{
	Frame::$current->writeBlockAlias($name);
}

/**
 * @brief If value is empty or null return default one. 
 * @param string $what Value to wrap for default one
 * @param string $default Value to return if it is empty or null
 */
function defvalue($what, $default)
{
	if (($what === null) || !strlen((string)$what)) {
		return $default;
	}
	return $what;
}