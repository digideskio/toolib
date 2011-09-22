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

namespace toolib\Http;

/**
 * @brief Container for HTTP headers
 * @see ArrayObject for additional reference.
 */
class HeaderContainer extends \ArrayObject
{

	/**
	 * @brief Create a new header container.
	 * @param array headers
	 */
	public function __construct($headers = array())
	{
		parent::__construct(is_array($headers)?$headers:array());
	}

	/**
	 * @brief Add a new header
	 * @param string $name The name of header.
	 * @param string $value The value of header.
	 */
	public function add($name, $value)
	{
		if (!$this->offsetExists($name)) {
			$this[$name] = $value;
		} else 	if (is_array($this[$name])) {
			$this[$name][] = $value;
		} else {
			$this[$name] = array($this[$name], $value);
		}
	}
	
	/**
	 * @brief Replace a header, by overwritting previous values.
	 * @param string $name The name of header.
	 * @param string $value The value of header.
	 */
	public function replace($name, $value)
	{
		$this[$name] = $value;		
	}
	
	/**
	 * @brief Remove all values of a header
	 * @param string $name The name of header.
	 */
	public function remove($name)
	{
		unset($this[$name]);
	}
	
	/**
	 * @brief Check if a header exists
	 * @param string $name The name of the header
	 * @return boolean true if header exists in this object or false if not.
	 */
	public function has($name)
	{
		return $this->offsetExists($name);
	}
	
	/**
	 * @brief Get one value of a header.
	 * @param string $name The name of header
	 * @param mixed $default The default value of the header
	 */
	public function getValue($name, $default = null)
	{
		if (!$this->offsetExists($name))
			return $default;
		if (is_array($this[$name]))
			return $this[$name][0];
			
		return $this[$name];
	}
	
	/**
	 * @brief Get all values of a specific header.
	 * @param string $name The name of header
	 * @return array
	 */
	public function getValues($name)
	{
		if (!$this->offsetExists($name))
			return array();
		if (is_array($this[$name]))
			return $this[$name];
			
		return array($this[$name]);
	}
	
	/**
	* @brief Count how many values exist for a header name
	* @param string $name The name of header
	*/
	public function countValues($name)
	{
		if (!$this->offsetExists($name))
			return 0;
		if (is_array($this[$name]))
			return count($this[$name]);
			
		return 1;
	}
	
	/**
	 * @brief Check if any header value is equal to a value
	 * @param string $name The name of the header
	 * @param mixed $expected The expected value of header to check.
	 * @param boolean $case_insensitive Flag if comparisson should be done in strict mode.
	 */
	public function is($name, $expected, $case_insensitive = false)
	{
		if (!$this->offsetExists($name))
			return false;
		
		$values = is_array($this[$name])?$this[$name]:array($this[$name]);
		
		foreach($values as $v) {
			if ($case_insensitive
				?(strcasecmp($v, $expected) == 0)
				:($v == $expected))
				return true;
		}
		
		return false;
	}
	
	/**
	* @brief Check if any header value contains a string
	* @param string $name The name of the header
	* @param mixed $needle The string to search inside values.
	* @param boolean $case_insensitive Flag if comparisson should be done in strict mode.
	*/
	public function contains($name, $needle, $case_insensitive = false)
	{
		if (!$this->offsetExists($name))
		return false;
	
		$values = is_array($this[$name])?$this[$name]:array($this[$name]);
	
		foreach($values as $v) {
			if ($case_insensitive
				?(stripos($v, $needle) !== false)
				:(strpos($v, $needle) !== false))
			return true;
		}
	
		return false;
	}
}