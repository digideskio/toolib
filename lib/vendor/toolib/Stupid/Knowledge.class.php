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
	 * @brief Temporary container of results and extractors.
	 * @var array
	 */
	public $assumptions = array('results' => array(), 'extractors' => array());
	
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
	
	public function setResult($name, $value, $assumption = true)
	{
		$this->results[$name] = $value;
	}
	
	/**
	 * @brief Enter description here ...
	 * @param callable $extractor 
	 * @param unknown_type $name
	 * @param unknown_type $assumption
	 */
	public function setExtractor($extractor, $name = null, $assumption = true)
	{
		$this->results[$name] = $value;
	}
	
	/**
	 * @brief Assumptions were not correct and must be discarded.
	 */
	public function discardAssumptions()
	{
		$this->assumptions = array('results' => array(), 'extractors' => array());
	}
	
	public function validateAssumptions()
	{
		$this->results = array_merge($this->results, $this->assumptions['results']);
		$this->extractors = array_merge($this->results, $this->assumptions['extractors']);
	}
	
	public function __get($name)
	{
		return $this->results[$name];
	}
	
	/**
	 * @brief Execute all extractors and extract new facts from results
	 * @param boolean $clear_extractors If true, the list of extractors will be cleared.
	 */
	public function extractFacts($clear_extractors = true)
	{
		foreach($this->extractors as $extractor) {
			$this->facts = array_merge($this->facts, $extractor($this->results));
		}
		if ($clear_extractors)
			$this->extractors = array();
	}
}