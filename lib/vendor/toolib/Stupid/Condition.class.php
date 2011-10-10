<?php

namespace toolib\Stupid;
use toolib\Stupid\Knowledge;

/**
 * @brief Base class to create conditions 
 */
abstract class Condition
{
	/**
	 * @brief Knowledge object to work on.
	 * @var \toolib\Stupid\Knowledge
	 */
	protected $knowledge;
	
	/**
	 * @brief Implemented by condition derivative.
	 */
	abstract public function evaluate();
	
	/**
	 * @brief Evalueate this condition based on given knowledge.
	 * @param \toolib\Stupid\Knowledge $knowledge
	 */
	public function __invoke(Knowledge $knowledge)
	{
		$this->knowledge = $knowledge;
		return $this->evaluate();
	}
	
	/**
	 * @brief Create condition object (late static binding)
	 */
	static public function create()
	{
		return new static();
	}
}