<?php

namespace toolib;

/**
 * @brief Container class for Options with extra actions.
 * 
 * It is helpfull to automate the procedure of managing
 * the options of any object. It supports default values,
 * mandatory values and extending.
 */
class Options extends \ArrayObject
{
	/**
	 * @brief Construct a new options container and initialize it.
	 * @param mixed $values Directly assigned options on this container, or another Options.
	 * @param array $default Default values for unset keys.
	 * @param array $mandatory An array with the names of values that are mandatatory.
	 * @throws InvalidArgumentException If a mandatory field was not set.
	 */
	public function __construct($values, $default = array(), $mandatory = array())
	{		
		parent::__construct($this->calculateValues($values, $default, $mandatory));
	}
	
	/**
	 * @brief Function to check for mandatory and create the array merging result
	 * @see __construct()
	 * @return array With the calculate result
	 * @throws InvalidArgumentException
	 */
	private function calculateValues($values, $default, $mandatory)
	{
		if (is_array($values))
			$nvalues = array_merge($default, $values);
		elseif ($values instanceof \ArrayObject)
			$nvalues = array_merge($default, $values->getArrayCopy());
			
		if (count($mandatory)) {
			$missing = array_diff($mandatory, array_keys($nvalues));
			if (count($missing) > 0)
				throw new \InvalidArgumentException("Missing mandatory options from Options object.");
    	}
    	return $nvalues;
	}
	
	/**
	 * @brief Check if an option is set.
	 * @param string $name The key name of the option
	 */
	public function has($name)
    {
        return $this->offsetExists($name);
    }
    
    /**
     * @brief Get the value of an option.
     * @param string $name The key name of the option.
     * @return mixed The value of option or null if it was not found. 
     */
    public function get($name)
    {
        return $this->has($name)?$this->offsetGet($name):null;
    }
    
    /**
     * @brief Add or overwrite the value of an option.
     * @param string $name The name of the option.
     * @param string $value The value of the option.
     */
    public function set($name, $value)
    {
        $this->offsetSet($name, $value);
    }
    
    /**
     * @brief Add only if value does NOT exists.
     * @param string $name The name of the option.
     * @param string $value The value of the option.
     * @return boolean
     * - @b true: If the value was added.
     * - @b false: If the key is already used.
     * .
     */
    public function add($name, $value)
    {
    	if (!$this->offsetExists($name)) {
        	$this->offsetSet($name, $value);
        	return true;
    	}
    	return false;
    }
    
    /**
     * @brief Remove an option from the container.
     * @param string $name The name of the option.
     * @param string $value The value of the option.
     */
    public function remove($name)
    {
    	if ($this->offsetExists($name))
        	$this->offsetUnset($name);
    }   
}