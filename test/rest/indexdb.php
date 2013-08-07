<?php

use \toolib\Http;
use \toolib\Stupid;
use \toolib\Stupid\Condition as Co;
use \toolib\Twiggy;

require_once __DIR__ . '/../../lib/vendor/toolib/autoload.php';
require_once __DIR__ . '/Test.class.php';

\toolib\DB\Connection::connect('localhost', 'root', 'root', 'test');
//\toolib\DB\Connection::events()->connect('stmt.executed', function($e){ var_dump($e);	});

Twiggy::initialize(__DIR__ . '/templates');

class RestTest extends \toolib\Stupid\Template\RestDB
{

	public function configure()
	{
		$this->setItemName('slot');
		$this->setKeyName('key');
		
		$this->createRule('slot.req_delete', Co\Request::create()->pathPatternIs('/{key}/+delete')->methodIsGet());
		$this->createRule('slot.post_delete', Co\Request::create()->pathPatternIs('/{key}/+delete')->methodIsPost());
		$this->createRule('slot.req_create', Co\Request::create()->pathIs('/+create')->methodIsGet());
		$this->createRule('slot.req_update', Co\Request::create()->pathPatternIs('/{key}/+edit')->methodIsGet());
	}
	
	public function slotIndex()
	{
		Twiggy::open('index.html')->display(array('items' => TestTable::openAll()));
	}
	
	public function slotGet($key)
	{
		$this->response->setCachePublic();
		if (!$rec = TestTable::open($key)) {
			return $this->response->reply404NotFound();
		}

		$this->response->setLastModified($rec->modified_at);
		if ($this->response->isNotModified($this->request)) {
			return $this->response->reply304NotModified();
		}
		
		Twiggy::open('item.html')->display(array('item' => $rec));
	}
	
	public function slotReqCreate()
	{
		Twiggy::open('new.html')->display();
	}
	
	public function slotReqUpdate($key)
	{
		if (!$rec = TestTable::open($key)) {
			return $this->response->reply404NotFound();
		}
		
		echo "<form method=\"post\" action=\"/projects/toolib/test/rest/indexdb.php/{$key}\" >
			Name: <input name=\"name\" value=\"{$rec->name}\" />
			Value: <input name=\"value\" value=\"{$rec->value}\" />
			<input type=\"submit\" /></form>";
	}
	
	public function slotUpdate($key)
	{
		if (!$rec = TestTable::open($key)) {
			return $this->response->reply404NotFound();
		}
		foreach($this->request->getContent()->getArrayCopy() as $name => $value)
			if ($name != 'id')
				$rec->$name = $value;
		
		if (!$rec->update())
			return $this->response->reply400BadRequest();
		
		return $this->slotGet($key);		
	}
	
	public function slotCreate()
	{
		$values = $this->request->getContent()->getArrayCopy();
		if (!$rec = TestTable::create($values)) {
			echo 'Error creating record!';
			return $this->response->reply400BadRequest();
		}
		return $this->response->reply303SeeOther('/projects/toolib/test/rest/indexdb.php/' . $rec->id);
	}
	
	public function slotReqDelete($key)
	{
		if (!$rec = TestTable::open($key)) {
			return $this->response->reply404NotFound();
		}
		echo '<form method="post"><input type="submit" /></form>';
	}
	
	public function slotPostDelete($key)
	{
		return $this->slotDelete($key);		
	}
	
	public function slotDelete($key)
	{
		if (!$rec = TestTable::open($key)) {
			return $this->response->reply404NotFound();
		}
		
		$rec->delete();
		//return $this->response->reply204NoContent();
	}
};

$app = new RestTest();
$app->execute(new Stupid\Knowledge(array('request.gateway' => new \toolib\Http\Cgi\Gateway())));
