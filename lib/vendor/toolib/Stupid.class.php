<?php

namespace toolib;
use toolib\Stupid;

require_once __DIR__ . '/Stupid/Rule.class.php';

class Stupid
{
	private $rules = array();
	
	/**
	 * @var \toolib\Stupid
	 */
	private $parent = null;
	
	/**
	 * @var \toolib\EventDispatcher
	 */
	protected $event_dispatcher;
	
	public function __construct($parent = null)
	{
		$this->parent = $parent;
		if ($parent !== null)
			$this->event_dispatcher = $this->parent->event_dispatcher;
		else
			$this->event_dispatcher = new EventDispatcher(array(
				'rule.process.begin',
				'rule.process.end',	
				'rule.process.succeeded',		/* filter */
				'rule.action.executed'
			));
	}
	
	/**
	 * Get the event dispatcher object
	 * @return \toolib\EventDispatcher
	 */
	public function events()
	{
		return $this->event_dispatcher;
	}
	
	public function getRules()
	{
		return $this->rules;
	}
	
	public function getRule($name)
	{
		if (isset($this->rules[$name]))
			return $this->rules[$name];
	}
	
	public function addRule(Stupid\Rule $rule)
	{
		return $this->rules[$rule->getName()] = $rule;
	}
	
	public function createRule($name)
	{
		$conditions = array_slice(func_get_args(), 1);
		return $this->rules[$name] = new Stupid\Rule($this, $name, $conditions);
	}
	
	public function execute(Stupid\Knowledge $knowledge)
	{
		foreach($this->rules as $rule) {
			$this->event_dispatcher->notify('rule.process.begin', array('rule' => $rule, 'knowledge' => $knowledge));
			if ($rule->execute($knowledge)) {
				$this->event_dispatcher->notify('rule.process.end', array('rule' => $rule, 'knowledge' => $knowledge));
				return $rule;
			}
			$this->event_dispatcher->notify('rule.process.end', array('rule' => $rule, 'knowledge' => $knowledge));
		}
	}
}