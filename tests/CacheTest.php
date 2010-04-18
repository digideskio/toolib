<?php
require_once 'PHPUnit/Framework.php';
require_once __DIR__ .  '/../lib/cache.lib.php';



class CacheTest extends PHPUnit_Framework_TestCase
{

	public function setUp()
	{
		// Tested cache implementations
		$this->impl = array(
			array('Cache_Memcached', "'127.0.0.1'"), 
			array('Cache_File', ''),
			array('Cache_Sqlite', "'/tmp/cache.db'"),
			array('Cache_Apc', "'', false"),
			array('Cache_Apc', "'test-subscirpts', false"),
			array('Cache_Apc', "'test-subscirpts', true")
		);
	}

	public function set_get_types($cache)
	{
		// Literal value set
		$res = $cache->set('key1', 123);
		$this->assertTrue($res);
		$res = $cache->get('key1', $succ);
		$this->assertTrue($succ);
		$this->assertEquals($res, 123);
	}

	public function testSetGetTypes()
	{	foreach($this->impl as $impl)
			$this->set_get_types(eval("return new {$impl[0]}({$impl[1]});"));

	}
}
?>
