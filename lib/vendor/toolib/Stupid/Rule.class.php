<?php

namespace toolib\Stupid;
use toolib\Stupid;

require_once __DIR__ . '/Condition.class.php';

/**
 * @brief Rule holds conditions and actions.
 */
class Rule
{
	/**
	 * @brief The name of this rule
	 * @var string
	 */
	private $name;
	
	/**
	 * @brief Action to be executed by this rule
	 * @var array
	 */
	private $actions = array();
	
	/**
	 * @brief Conditions to be passed for this rule to be valid
	 * @var array
	 */
	private $conditions = array();
	
	/**
	 * @brief Stupid container that contains this object.
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
	 * @brief Get the container that holds this rule
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
	 * @brief Add a condition to be validated for this rule to be valid
	 * @param toolib\Stupid\Condition $cond
	 */
	public function addCondition($cond)
	{
		$this->conditions[] = $cond;
		return $this;
	}
	
	/**
	 * @brief Add an action to be called on valid conditions.
	 * @param callable $callable
	 */
	public function addAction($callable)
	{
		$this->actions[] = $callable;
		return $this;
	}
	
	/**
	 * @brief Add an action to chain execution on another Stupid container
	 * @param string $class_name The class name of the container.
	 */
	public function addActionChainToClass($class_name)
	{
		$parent = $this->getOwner();
		return $this->addAction(function($knowledge) use($parent, $class_name){
			$stupid = new $class_name($parent);
			$stupid->execute($knowledge);
		});
	}
	
	/**
	 * @brief Execute this rule by validating conditions and if true
	 *  execute all actions.
	 * @param Knowledge $knowledge The knowledge to be passed at conditions
	 * 	and actions.
	 * @return boolean @b True if conditions where true or false if not.
	 */
	public function execute(Knowledge $knowledge)
	{
		if (count($this->conditions) == 0)
			return false;
		
		$copy_knowledge = clone $knowledge;	// We clone knowledge to remain clean on case of fail.
		$copy_knowledge->facts['rule.name'] = $this->name;
		foreach($this->conditions as $cond)
			if (!$cond($copy_knowledge)) {
				return false;
			}
		
		$succeeded = true;
		$this->owner->events()->filter('rule.process.succeeded', $succeeded, array('rule' => $this));
		if (!$succeeded)
			return false;
		
		$knowledge->replaceBy($copy_knowledge);
		foreach($this->actions as $action) {
			call_user_func($action, $knowledge);
		}
		$this->owner->events()->filter('rule.action.executed', $succeeded, array('rule' => $this));
		return true;
	}
	

}