<?php

namespace toolib\Url;

/**
 * @brief Pattern-based Url/Uri constructor.
 */
class ResourceConstructor
{
	/**
	 * @brief Friendly name of this resource
	 * @var string
	 */
	private $name;
	
	/**
	 * @brief Pattern of resource's uri
	 * @var string
	 */
	private $pattern;
	
	/**
	 * @brief Placeholders on the uri pattern
	 * @var array
	 */
	private $placeholders;
	
	/**
	 * @brief printf compatible string to print uri
	 * @var array
	 */
	private $print_str;
	
	/**
	 * @brief Initialize the constructor 
	 * @param string $name Friendly name of resource
	 * @param string $pattern Uri pattern of the resource
	 */
	public function __construct($name, $pattern)
	{
		$this->name = $name;
		$this->pattern = $pattern;
	}
	
	/**
	 * @brief Get the friendly name of this resource
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}
	
	/**
	 * @brief Get the uri pattern
	 */
	public function getPattern()
	{
		return $this->pattern;
	}
	
	/**
	 * @brief Get all placeholders found on pattern.
	 * @return array
	 */
	public function getPlaceholders()
	{
		if ($this->placeholders === null)
			$this->analyze();
		return $this->placeholders;
	}
	
	/**
	 * @brief Analyze the pattern and extract placeholders
	 */
	private function analyze()
	{
		$counter = 0;
		$placeholders = array();
		$this->print_str = preg_replace_callback('/(?<key>{[^}]+})/', function($match) use (& $counter, & $placeholders)
		{
			$placeholders[$counter + 1] = explode('.', trim($match['key'], '{}'));			
			$counter ++;
			return "%{$counter}\$s";
		}, str_replace('%', '%%', $this->pattern));
		$this->placeholders = $placeholders;
	}
	
	/**
	 * @brief Generate a new absolute URI path based on given parameters.
	 * @param mixed $params All the needed parameters given as array or object.
	 * @param boolean $escape Flag to control escaping of parameters. 
	 * @throws \InvalidArgumentException
	 */
	public function path($params = array(), $escape = true)
	{
		if (!is_object($params) && !is_array($params)) {
			throw new \InvalidArgumentException('Values must be given through object or array');
		}
		if (!$this->placeholders)
			$this->analyze();

		// Gather sprintf arguments
		$args = array($this->print_str);
		foreach($this->placeholders as $id => $place) {
			$value = $params;
			foreach($place as $prop) {
				if (is_object($value)) {
					$value = $value->$prop;
					
				} else {
					$value = $value[$prop];
				}
			}
			$args[$id] = ($escape?urlencode($value):$value);
		}		
		return call_user_func_array('sprintf', $args);
	}
	
	/**
	 * @brief Generate absolute URL based on the Uri pattern and an Http request
	 * @param \toolib\Http\Request $request Request to retrieve connection information
	 * @param mixed $params All the needed parameters given as array or object.
	 * @param boolean $escape Flag to control escaping of parameters. 
	 */
	public function urlFromRequest(\toolib\Http\Request $request, $params, $escape = true)
	{
		return strtolower($request->getScheme()) . 
			"://" . 
			$request->getHeaders()->getValue('host') . 
			$this->path($params, $escape);
	}
	
	/**
	 * @brief Generate absolute URL
	 * @param mixed $params All the needed parameters given as array or object.
	 * @param string $host The host where the resource is located
	 * @param boolean $secure True if it is an HTTP Secure connection (https)
	 * @param string $port The port that HTTP protocol will serve the resource
	 * @param boolean $escape Flag to control escaping of parameters. 
	 */
	public function url($params, $host,  $secure = false, $port = null, $escape = true)
	{
		return
			(($secure)?'https://':'http://') .
			$host .
			($port === null
				?''		// No specific port
				:(($secure && ($port == 443)) || (!$secure && ($port == 80))
					?''		// Default ports are omitted
					:":$port"
				)
			).
			$this->path($params, $escape);
	}
}
