<?php

namespace toolib\Stupid\Template;

use \toolib\Stupid;

require_once __DIR__ . '/../../Stupid.class.php';

/**
 * @brief Implement web-purpose decision system.
 */
abstract class Web extends Stupid
{
	/**
	 * @brief Request currently processed
	 * @var \toolib\Http\Request
	 */
	protected $request;
	
	/**
	 * @brief Response currently processed
	 * @var \toolib\Http\Response
	 */	
	protected $response;
	
	/**
	 * @brief Wrapper function to call proper action method on rule validation.
	 * @param \toolib\Stupid\Knowledge $k
	 */
	public function callAction(\toolib\Stupid\Knowledge $k)
	{
		$rule_name = $k->facts['rule.name'];
		$function = str_replace(' ', '', ucwords(str_replace(array('.', '-', '_'), ' ', $rule_name)));
		$function[0] = strtolower($function[0]);
		if (isset($k->results['request.params'])) {
			call_user_func_array(array($this, $function), $k->results['request.params']);
		} else {
			$this->$function();
		}
	}
	
	/**
	 * @brief All created rules will trigger object methods.  
	 * @see toolib.Stupid::createRule()
	 */
	public function createRule($name)
	{
		$conditions = array_slice(func_get_args(), 1);
		return $this->addRule(new Stupid\Rule($this, $name, $conditions))
			->addAction(array($this, 'callAction'));		
	}

	/**
	 * @brief Called automatically, can be overriden.
	 */
	public function initialize()
	{
		// Configure
		if (method_exists($this, 'configure'))
			$this->configure();
	}
	
	public function execute(Stupid\Knowledge $knowledge)
	{
		// Extract needed information
		$this->request = $knowledge->facts['request.gateway']->getRequest();
		$this->response = $knowledge->facts['request.gateway']->getResponse();
		
		$this->initialize();
		
		// Execute
		if (!$rule = parent::execute($knowledge)) {
			$this->response->reply404NotFound();
		}
	}
}