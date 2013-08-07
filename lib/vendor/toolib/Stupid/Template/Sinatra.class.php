<?php


namespace toolib\Stupid\Template;
use toolib\Url\Container;

use \toolib\Stupid;
use \toolib\Stupid\Condition as Cond;
use \toolib\Http;

require_once __DIR__ . '/../../Stupid.class.php';
require_once __DIR__ . '/../Condition/Request.class.php';
require_once __DIR__ . '/../../Http/Cgi/Gateway.class.php';

/**
 * @brief Implement sinatra-style decision system.
 */
class Sinatra extends Stupid
{
	/**
	* @var array
	*/
	public $params;
	
	/**
	* @var \toolib\Http\Request
	*/
	public $request;
	
	/**
	* @var \toolib\Http\Response
	*/
	public $response;
	
	/**
	* @var \toolib\Http\Gateway
	*/
	public $gateway;
	
	/**
	 * @brief Actions to be executed before chain reaction.
	 * @var array
	 */
	protected $predos = array();
	
	/**
	* @brief Actions to be executed after chain reaction.
	* @var array
	*/
	protected $postdos = array();
	
	/**
	 * @var \toolib\Url\Container
	 */
	public $url;
	
	private function createSinatraRule($http_method, $path_pattern, $action, $extra_conditions = array())
	{
		$rule = $this->createRule($http_method . $path_pattern);
		$req_condition = Cond\Request::create();
		if ($http_method != false)
			$req_condition->methodIs($http_method);
		$req_condition->pathPatternIs($path_pattern);
		$rule->addCondition($req_condition);
		
		$sinatra = $this;
		$predos = $this->predos;
		$postdos = $this->postdos;
		$rule->addAction(function(Stupid\Knowledge $k) use($action, $sinatra, $predos, $postdos) {
			foreach($predos as $predo) {
				$predo($sinatra);
			}
			$res = call_user_func_array($action, $k->results['request.params']);
			foreach($postdos as $postdo) {
				$postdo($sinatra);
			}				
			if (is_string($res))
				$sinatra->response->appendContent($res);
		});
		foreach($extra_conditions as $cond)
			$rule->addCondition($cond);
	}
	
	public function predo($action)
	{
		$this->predos[] = $action;
	}
	
	public function postdo($action)
	{
		$this->postdos[] = $action;
	}
	
	public function GET($path_pattern, $action)
	{
		return $this->createSinatraRule('GET', $path_pattern, $action);		
	}
	
	public function PUT($path_pattern, $action)
	{
		return $this->createSinatraRule('PUT', $path_pattern, $action);
	}
	
	public function DELETE($path_pattern, $action)
	{
		return $this->createSinatraRule('DELETE', $path_pattern, $action);
	}
	
	public function POST($path_pattern, $action)
	{
		return $this->createSinatraRule('POST', $path_pattern, $action);
	}
	
	public function HEAD($path_pattern, $action)
	{
		return $this->createSinatraRule('HEAD', $path_pattern, $action);
	}
	
	public function ANY($path_pattern, $action)
	{
		return $this->createSinatraRule(false, $path_pattern, $action);
	}
	
	public function __invoke($gateway = null)
	{
		if($gateway === null)
			$this->gateway = new \toolib\Http\Cgi\Gateway();
		else 
			$this->gateway = $gateway;
		$this->request = $this->gateway->getRequest();
		$this->response = $this->gateway->getResponse();
		$this->url = new \toolib\Url\Container();
	
		if (!$this->execute(new Stupid\Knowledge(array('request.gateway' => $this->gateway)))) {
			$this->response->reply404NotFound();
			$this->response->appendContent('Not found');
		}
	}
}