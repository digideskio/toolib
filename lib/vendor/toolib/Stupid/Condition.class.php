<?php

namespace toolib\Stupid;
use toolib\Stupid\Knowledge;

abstract class Condition
{
	/**
	 * @var \toolib\Stupid\Knowledge
	 */
	protected $knowledge;
	
	abstract public function evaluate();
	
	public function __invoke(Knowledge $knowledge)
	{
		$this->knowledge = $knowledge;
		return $this->evaluate();
	}
	
	static public function create()
	{
		return new static();
	}
}