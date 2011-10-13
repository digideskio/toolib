<?php

namespace toolib\Url;

require_once __DIR__ . '/ResourceConstructor.class.php';

/**
 * @brief Container of resource constructors
 */
class Container
{
	/**
	 * @brief List with all constructors
	 * @var array
	 */
	private $resources = array();
	
	/**
	 * @brief Create and register a new constructor
	 * @return ResourceConstructor
	 */
	public function create($name, $pattern)
	{
		return $this->resources[$name] = new ResourceConstructor($name, $pattern);
	}
	
	/**
	 * @brief Create and register multiple constructors at once
	 * @param array $resources Associative array where key is the name and value is the pattern.
	 */
	public function createMultiple($resources)
	{
		foreach($resources as $name => $pattern) {
			$this->resources[$name] = new ResourceConstructor($name, $pattern);
		}
	}
	
	/**
	 * @brief Open a registered Resource constructor
	 * @return ResourceConstructor
	 */
	public function open($name)
	{
		if (isset($this->resources[$name])) {
			return $this->resources[$name];
		}
	}	
} 

