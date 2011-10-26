<?php

namespace toolib;
use \toolib\Twiggy\Template;
use \toolib\Options;

/**
 * @brief Twiggy loader and template manager.
 */
class Twiggy
{
	/**
	 * @brief Directories that templates can be found
	 * @var array
	 */
	static private $template_directories = null;
	
	/**
	 * @brief
	 * @var \toolib\Options
	 */
	static private $options = null;
	
	/**
	 * @brief Initialize template system
	 * @param array|string $template_directories One or more places to search for templates.
	 * @param array $options Options to be passed on system.
	 *  - @b compile_directory (default: false): path to save compiled templates or false.
	 *  - @b auto_reload (default: true): Recompile templates if they are changed. Usefull at debug
	 */
	static public function initialize($template_directories = array(), $options = array())
	{
		self::$template_directories = !is_array($template_directories)
			?array($template_directories)
			:$template_directories;
		self::$options = new Options($options, array(
			'compiled_directory' => false,
			'auto_reload' => true,			
		));
	}
	
	/**
	 * @brief Search and open a template
	 * @param string $template The template name as stored on filesystem.
	 * @return toolib\Twiggy\Template;
	 */
	static public function open($template)
	{
		foreach(self::$template_directories as $temp_dir) {
			if (file_exists($temp_dir . '/' . $template)) {
				return new Template($temp_dir . '/' . $template, self::$options);
			}
		}
		return false;
	}
}