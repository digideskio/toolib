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

namespace toolib\Net\Http;

//! Container for parameters
/**
 * @see ArrayObject for additional reference.
 */
class ParameterContainer extends \ArrayObject
{

	//! Create a new parameter container.
	/**
	 * @param array $parameters
	 */
	public function __construct($parameters = array())
	{
		parent::__construct($parameters);
	}

	//! Check if a parameter exists
	/**
	 * @param string $name The name of the parameter
	 * @return boolean true if parameter exists in this object or false if not.
	 */
	public function has($name)
	{
		return $this->offsetExists($name);
	}
	
	//! Get a parameter
	/**
	 * @param string $name The name of the parameter
	 * @param mixed $default The default value of the parameter
	 */
	public function get($name, $default = null)
	{
		if ($this->offsetExists($name))
			return $this->offsetGet($name);
		return $default;
	}
	
	//! Check if a parameter is equal to a value
	/**
	 * @param string $name The name of the parameter
	 * @param mixed $default The expected value of parameter to check.
	 * @param boolean $strict Flag if comparisson should be done in strict mode.
	 */
	public function is($name, $expected, $strict = false)
	{
		if (!$this->offsetExists($name))
			return false;
		return $strict?($this->offsetGet($name) === $expected):($this->offsetGet($name) == $expected);
	}

	//! Get a parameter and cast to integer
	/**
	 * @param string $name The name of the parameter
	 * @param mixed $default The default value of the parameter
	 */
	public function getInt($name, $default = null)
	{
		if ($this->offsetExists($name))
			return (int)$this->offsetGet($name);
		return $default;
	}
	
	//! Get a parameter and cast to DateTime
	/**
	 * The format of the string will be autodetected by DateTime class.
	 * @param string $name The name of the parameter
	 * @param \DateTime $default The value to return if paremeter is
	 *   missing or is not datetime.
	 * @return \DateTime Object with the value of parameter parsed.
	 */
	public function getDateTime($name, \DateTime $default = null)
	{
		if ($this->offsetExists($name)) {
			try {
				return new \DateTime($this->offsetGet($name));
			} catch(\Exception $e) {
				return $default;
			}
		}
		return $default;
	}
	
	//! Get a parameter and cast to DateTime based on format
	/**
	 * @param string $name The name of the parameter
	 * @param string $format The format of the datetime string.
	 * @param \DateTime $default The value to return if paremeter is
	 *   missing or is not datetime.
	 * @return \DateTime Object with the value of parameter parsed.
	 */
	public function getDateTimeFromFormat($name, $format, \DateTime $default = null)
	{
		if ((! $this->offsetExists($name))
			 || (!($date = \DateTime::createFromFormat($format, $this->offsetGet($name)))))
				return $default;
				
		return $date;
	}
	
	//! Get a parameter after checked agains a regular expresion.
	/**
	 * @param string $name The name of the parameter.
	 * @param string $regex The regular expression that must be true.
	 * @param mixed $default The value to return if paremeter is
	 *   missing or does not match.
	 */
	public function checkAndGet($name, $regex, $default = null)
	{
		if (!$this->offsetExists($name))
			return $default;
			
		if (preg_match($regex, $this->offsetGet($name)) > 0)
			return $this->offsetGet($name);
		return $default;
	}
}