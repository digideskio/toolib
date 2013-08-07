<?php

echo '<pre>';
session_start();

use \toolib\Http;
use \toolib\Stupid;
use \toolib\Stupid\Condition as Co;

require_once __DIR__ . '/../../lib/vendor/toolib/autoload.php';


class RestTest extends \toolib\Stupid\Template\Rest
{

	public function configure()
	{
		$this->setItemName('slot');
		$this->setKeyName('key');
		
		$this->createRule('slot.req_delete', Co\Request::create()->pathPatternIs('/{key}/+delete')->methodIsGet());
		$this->createRule('slot.post_delete', Co\Request::create()->pathPatternIs('/{key}/+delete')->methodIsPost());
		$this->createRule('slot.req_create', Co\Request::create()->pathIs('/+generate')->methodIsGet());
		$this->createRule('slot.req_edit', Co\Request::create()->pathPatternIs('/{key}/+edit')->methodIsGet());
	}
	
	public function slotIndex()
	{
		foreach($_SESSION as $name => $value) {
			echo "<a href=\"{$name}\" >{$name}</a> => " . $value->format(DATE_ATOM) . "\n";
		}
		
		echo '<a href="+generate" > Generate </a>';
	}
	
	public function slotGet($key)
	{
		$this->response->setCachePublic();
		if (!isset($_SESSION[$key])) {
			$_SESSION[$key] = date_create();
			$_SESSION['last_item'] = $_SESSION[$key];			
		}
		$gen_time = $_SESSION[$key];

		$this->response->setLastModified($gen_time);
		if ($this->response->isNotModified($this->request)) {
			
			return $this->response->reply304NotModified();
		}
		
		echo '<h1>' . $key . '</h1>';
		echo ' Generated @ ' . $gen_time->format(DATE_ATOM) . '<br/>';
		echo '<a href="' . $key . '/+delete">Delete</a><br />';
		echo '<a href="./">Show all </a>';
	}
	
	public function slotReqCreate()
	{
		echo '<form method="post" action="./" ><input type="submit" /></form>';
	}
	
	public function slotCreate()
	{
		$key = sha1(rand());
		return $this->response->reply303SeeOther($key);
	}
	
	public function slotReqDelete($key)
	{
		if (!isset($_SESSION[$key]))
			return $this->response->reply404NotFound();
		echo '<form method="delete"><input type="submit" /></form>';
	}
	
	public function slotPostDelete($key)
	{
		return $this->slotDelete($key);		
	}
	
	public function slotDelete($key)
	{
		if (!isset($_SESSION[$key]))
			return $this->response->reply404NotFound();
		
		unset($_SESSION[$key]);
		return $this->response->reply204NoContent();
	}
};


$app = new RestTest();
$app->execute(new Stupid\Knowledge(array('request.gateway' => new \toolib\Http\Cgi\Gateway())));
echo '</pre>';