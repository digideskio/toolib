<?php

namespace toolib;
use toolib\Stupid;

require_once __DIR__ . '/Stupid/Rule.class.php';
require_once __DIR__ . '/Stupid/Knowledge.class.php';
require_once __DIR__ . '/EventDispatcher.class.php';

/**
 * @brief Stupid knowledge processor.
 */
class Stupid
{
	/**
	 * @brief List of all rules
	 * @var array
	 */
	private $rules = array();
	
	/**
	 * @brief Optional parent container
	 * @var \toolib\Stupid
	 */
	private $parent = null;
	
	/**
	 * @brief Event dispatcher system
	 * @var \toolib\EventDispatcher
	 */
	protected $event_dispatcher;
	
	/**
	 * @brief Construct a new stupid rules container
	 * @param \toolib\Stupid $parent Optional parent container
	 */
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
	
	/**
	 * @brief Get all rules stored at this container.
	 */
	public function getRules()
	{
		return $this->rules;
	}
	
	/**
	 * @brief Get parent container (if any).
	 * @return Stupid
	 */
	public function getParent()
	{
		return $this->parent;
	}
	
	/**
	 * @brief Get a rule of this container.
	 * @param string $name Name of rule that was registered.
	 * @return \toolib\Stupid\Rule
	 */
	public function getRule($name)
	{
		if (isset($this->rules[$name]))
			return $this->rules[$name];
	}
	
	/**
	 * @brief Add a new rule on this container.
	 * @param Stupid\Rule $rule
	 * @return \toolib\Stupid\Rule
	 */
	public function addRule(Stupid\Rule $rule)
	{
		return $this->rules[$rule->getName()] = $rule;
	}
	
	/**
	 * @brief Create a rule and add it on this container.
	 * @param string $name Name of the rule
	 * @param callable $cond1 Condition to be added on rule
	 * @return \toolib\Stupid\Rule
	 */
	public function createRule($name)
	{
		$conditions = array_slice(func_get_args(), 1);
		return $this->rules[$name] = new Stupid\Rule($this, $name, $conditions);
	}
	
	/**
	 * @brief Validate all rules and conditions and execute the appropriate actions
	 * @param Stupid\Knowledge $knowledge Knowledge to be passed on system
	 * @return boolean|\toolib\Stupid\Rule The succeeded rule or null.
	 */
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