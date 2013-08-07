<?php


namespace toolib\Stupid\Template;
use toolib\Stupid\Condition as Co;
use \toolib\Stupid;
use \toolib\Http;

require_once __DIR__ . '/../Condition/Request.class.php';
require_once __DIR__ . '/Rest.class.php';

abstract class RestDB extends Rest
{
	/**
	 * @var string
	 */	
	private $model_name = null;
	
	/**
	 * @var \toolib\DB\Record
	 */
	private $record;
	
	public function callAction(Stupid\Knowledge $k)
	{
		parent::callAction($k);
	}
}