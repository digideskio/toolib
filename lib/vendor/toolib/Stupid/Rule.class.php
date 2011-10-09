<?php

namespace toolib\Stupid;
use toolib\Stupid;

require_once __DIR__ . '/Condition.class.php';

class Rule
{
	private $name;
	
	private $actions = array();
	
	private $conditions = array();
	
	/**
	 * @var \toolib\Stupid
	 */
	private $owner;
	
	public function __construct(Stupid $owner, $name, $conditions = null)
	{
		$this->owner = $owner;
		$this->name = $name;
		if (is_array($conditions))
			$this->conditions = $conditions;
	}
	
	/**
	 * @return \toolib\Stupid
	 */
	public function getOwner()
	{
		return $this->owner;
	}
	
	/**
	 * @brief Get rule's name
	 */
	public function getName()
	{
		return $this->name;
	}
	
	/**
	 * @param callable $callable
	 */
	public function addAction($callable)
	{
		$this->actions[] = $callable;
	}

	public function addCondition(toolib\Stupid\Condition $cond)
	{
		$this->conditions[] = $cond;
	}
	
	public function execute(Knowledge $knowledge)
	{
		if (count($this->conditions) == 0)
			return false;
		foreach($this->conditions as $cond)
			if (!$cond($knowledge)) {
				return false;
			}
		
		$succeeded = true;
		$this->owner->events()->filter('rule.process.succeeded', $succeeded, array('rule' => $this));
		if (!$succeeded)
			return false;
		
		foreach($this->actions as $action)
			$action($knowledge);
		$this->owner->events()->filter('rule.action.executed', $succeeded, array('rule' => $this));
		return true;
	}
	
	public function addActionChainToClass($class)
	{
		$parent = $this->getOwner();
		$this->addAction(function($knowledge) use($parent, $class){
			$stupid = new $class($parent);
			$stupid->execute($knowledge);
		});
	}
}