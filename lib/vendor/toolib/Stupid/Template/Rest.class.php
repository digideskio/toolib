<?php


namespace toolib\Stupid\Template;
use toolib\Stupid\Condition as Co;
use \toolib\Stupid;
use \toolib\Http;

require_once __DIR__ . '/../Condition/Request.class.php';
require_once __DIR__ . '/Web.class.php';

abstract class Rest extends Web
{
	private $item_name = 'item';
	
	private $key_name = 'id';
	
	
	public function setKeyName($name)
	{
		$this->key_name = $name;
	}
	
	public function getKeyName()
	{
		return $this->key_name;
	}
	
	public function setItemName($name)
	{
		$this->item_name = $name;
	}
	
	public function getItemName()
	{
		return $this->item_name;
	}
	
	private function _createRestRules()
	{
		$this->createRule("{$this->item_name}.index",
			Co\Request::create()->methodIsGetOrHead()->pathIs('/'));

		$this->createRule("{$this->item_name}.create",
			Co\Request::create()->methodIsPost()->pathIs('/'));
		
		$this->createRule("{$this->item_name}.update",
			Co\Bool::opOr(
				Co\Request::create()->methodIsPut()->pathPatternIs('/{' . $this->key_name .'}'),
				Co\Request::create()->methodIsPost()->pathPatternIs('/{' . $this->key_name .'}')
			)
		);
		
		$this->createRule("{$this->item_name}.get",
			Co\Request::create()->methodIsGet()->pathPatternIs('/{' . $this->key_name .'}'));
		
		$this->createRule("{$this->item_name}.delete",
			Co\Request::create()->methodIsDelete()->pathPatternIs('/{' . $this->key_name .'}'));
	}

	public function initialize()
	{
		parent::initialize();
		$this->_createRestRules();
	}
}