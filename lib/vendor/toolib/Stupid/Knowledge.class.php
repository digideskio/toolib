<?php

namespace toolib\Stupid;

/**
 * @brief Knowledge container 
 */
class Knowledge
{
	/**
	 * @brief The list of facts
	 * @var array
	 */
	public $facts;
	
	/**
	 * @brief the list of results
	 * @var array
	 */
	public $results = array();
	
	/**
	 * @brief The list of fact extractors.
	 * @var array
	 */
	public $extractors = array();
	
	/**
	 * @brief Construct a new knowledge container
	 * @param array $facts Predefined facts
	 */
	public function __construct($facts = array())
	{
		$this->facts = $facts;
	}
	
	/**
	 * @brief Get mandatory fact.
	 * @param string $name Name of fact
	 * @throws \InvalidArgumentException if there is no fact iwth this name.
	 */
	public function getFact($name)
	{
		if (!isset($this->facts[$name]))
			throw new \InvalidArgumentException("There is no \"{$name}\" defined fact.");
		return $this->facts[$name];
	}
	
	/**
	 * @brief Get optional fact with default fallback value.
	 * @param string $name Name of fact.
	 * @param mixed $default Default value of fact, if it does not exist.
	 */
	public function getOptionalFact($name, $default)
	{
		if (!isset($this->facts[$name]))
			return $default;
		return $this->facts[$name];
	}
	
	/**
	 * @brief Set a result value
	 * @param string $name Key of the result entry
	 * @param mixed $value Any value for the result.
	 */
	public function setResult($name, $value)
	{
		$this->results[$name] = $value;
	}
	
	/**
	 * @brief Direct property access to results.
	 * @param string $name
	 */
	public function __get($name)
	{
		return $this->results[$name];
	}
	
	/**
	 * @brief Add or replace a named extractor 
	 * @param string $name The name of the extractor
	 * @param callable $extractor Callable of the extractor
	 */
	public function setExtractor($name, $extractor)
	{
		$this->extractors[$name] = $extractor;
	}
	
	/**
	 * @brief Add an unamed extractor
	 * @param callable $extractor Callable of the extractor
	 */
	public function addExtractor($extractor)
	{
		$this->extractors[] = $extractor;
	}
	
	/**
	 * @brief Execute all extractors and extract new facts from results
	 * @param boolean $clear_extractors If true, the list of extractors will be cleared.
	 */
	public function extractFacts($clear_extractors = true)
	{
		foreach($this->extractors as $extractor) {
			$this->facts = array_merge($this->facts, $extractor($this));
		}
		if ($clear_extractors)
			$this->extractors = array();
	}
	
	/**
	 * @brief Replace data with those from another container
	 * @param  Knowledge $src Source of data
	 */
	public function replaceBy(Knowledge $src)
	{
		$this->extractors = $src->extractors;
		$this->facts = $src->facts;
		$this->results = $src->results;
	}
}