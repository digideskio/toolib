<?php
/*
 *  This file is part of PHPLibs <http://phplibs.kmfa.net/>.
 *
 *  Copyright (c) 2010 < squarious at gmail dot com > .
 *
 *  PHPLibs is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  PHPLibs is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with PHPLibs.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

require_once __DIR__ .  '/path.inc.php';

use \toolib\Cache as c;

class CacheTest extends PHPUnit_Framework_TestCase
{
	public static function tearDownAfterClass()
	{
		unlink(sys_get_temp_dir() . '/cache.db');
	}

	public function provider_impl()
	{
		// Tested cache implementations
		return array(
			array(new c\Memcached('127.0.0.1')),
			array(new c\File()),
			array(new c\Sqlite(sys_get_temp_dir() . '/cache.db')),
			//array(new c\Apc('test-subscirpts', true))
		);
	}

	public function provider_data1()
	{
		return array(
			array('key1', 123),
			array('key2', 'big string'),
			array('key3', array(123, 'big string')),
			array('key4', array('beta', new stdClass())),
			array('key5', new stdClass()),
			array('wierd_name/!@#$%^&*()_+{}[]:";"\'<>,.?/>', 'hasdfasdf'),
			array('/\\\\wierd_name26123/!@#$%^&*()_+{}[]:";"\'<>,.?/>', 'gasd'),
			array('#/\\\\wierd_name26123/!@#$%^&*()_+{}[]:";"\'<>,.?/>', 'fdh'),
			array('124#/\\\\wierd_name26123/!@#$%^&*()_+{}[]:";"\'<>,.?/>', 'qwer'),
			array('key7', array_fill(0, 50000, '_')),
			array('key8', str_repeat('_', 50000))
		);
	}

	public function provider_data2()
	{
		return array(
			array('key1', 6453),
			array('key2', 'new string'),
			array('key3', array('big string', 123)),
			array('key4', array(new Datetime('@0'), 62345)),
			array('key5', new Datetime('@0')),
			array('wierd_name/!@#$%^&*()_+{}[]:";"\'<>,.?/>', 'test123'),
			array('/\\\\wierd_name26123/!@#$%^&*()_+{}[]:";"\'<>,.?/>', 'okeke'),
			array('#/\\\\wierd_name26123/!@#$%^&*()_+{}[]:";"\'<>,.?/>', 'ghjbvn'),
			array('124#/\\\\wierd_name26123/!@#$%^&*()_+{}[]:";"\'<>,.?/>', 'zxcxcv'),
			array('key7', array_fill(0, 50000, 'o')),
			array('key8', str_repeat('o', 50000))
		);
	}

	public function provider_impl_data1()
	{
		$cache_data_set = array();
		foreach($this->provider_impl() as $impl)
		foreach($this->provider_data1() as $pair)
			$cache_data_set[] = array($impl[0], $pair[0], $pair[1]);
		return $cache_data_set;
	}

	public function provider_impl_data2()
	{
		$cache_data_set = array();
		foreach($this->provider_impl() as $impl)
		foreach($this->provider_data2() as $pair)
			$cache_data_set[] = array($impl[0], $pair[0], $pair[1]);
		return $cache_data_set;
	}

	/**
	 * @dataProvider provider_impl_data2
	 */
	public function testNoTTLSetGetTypes($cache, $key, $value)
	{
		// Set value
		$res = $cache->set($key, $value);
		$this->assertTrue($res);

		// Get value
		$res = $cache->get($key, $succ);
		$this->assertTrue($succ);
		$this->assertEquals($value, $res);
	}

	/**
	 * @dataProvider provider_impl_data2
	 */
	public function testNoTTLSetOverwrite($cache, $key, $value)
	{
		// Set value
		$res = $cache->set($key, $value);
		$this->assertTrue($res);

		// Get value
		$res = $cache->get($key, $succ);
		$this->assertTrue($succ);
		$this->assertEquals($value, $res);
	}

	/**
	 * @dataProvider provider_impl_data2
	 * @depends testNoTTLSetOverwrite
	 */
	public function testDelete($cache, $key, $value)
	{
		// Get value
		$res = $cache->get($key, $succ);
		$this->assertTrue($succ);
		$this->assertEquals($value, $res);

		// Delete value
		$res = $cache->delete($key);
		$this->assertTrue($res);
	}

	/**
	 * @dataProvider provider_impl_data2
	 * @depends testDelete
	 */
	public function testNonExistingGetAndDelete($cache, $key, $value)
	{
		// Get value
		$res = $cache->get($key, $succ);
		$this->assertFalse($succ);

		// Delete value
		$res = $cache->delete($key);
		$this->assertFalse($res);
	}

	/**
	 * @dataProvider provider_impl_data1
	 * @depends testNonExistingGetAndDelete
	 */
	public function testNonTTLAdd($cache, $key, $value)
	{
		// Add value
		$res = $cache->add($key, $value);
		$this->assertTrue($res);

		// Get value
		$res = $cache->get($key, $succ);
		$this->assertTrue($succ);
		$this->assertEquals($value, $res);

		// Re-add value (must fail)
		$res = $cache->add($key, 'dumb value');
		$this->assertFalse($res);

		// Get value to check unaffected
		$res = $cache->get($key, $succ);
		$this->assertTrue($succ);
		$this->assertEquals($value, $res);
	}

	/**
	 * @dataProvider provider_impl
	 * @depends testNonTTLAdd
	 */
	public function testDeleteAll($cache)
	{
		$data = $this->provider_data1();

		// Check that are is something stored
		$res = $cache->get($data[0][0], $succ);
		$this->assertTrue($succ);
		$this->assertEquals($data[0][1], $res);

		// Delete all
		$res = $cache->deleteAll();
		$this->assertTrue($res);

		// Check that this data does not exist
		$res = $cache->get($data[2][0], $succ);
		$this->assertFalse($succ);

		// Try to re-delete
		$res = $cache->deleteAll();
		$this->assertTrue($res);
	}

	/**
	 * @dataProvider provider_impl
	 * @depends testDeleteAll
	 */
	public function testMultiSetGet($cache)
	{
		$data = array();
		foreach($this->provider_data2() as $pair)
		$data[$pair[0]] = $pair[1];
		// Set values
		$res = $cache->setMulti($data);
		$this->assertTrue($res);

		// Get values;
		foreach($data as $key => $value) {
			$res = $cache->get($key, $succ);
			$this->assertTrue($succ);
			$this->assertEquals($value, $res);
		}
	}

	/**
	 * @dataProvider provider_impl
	 * @depends testMultiSetGet
	 */
	public function testOverwriteMultiSetGet($cache)
	{
		$data = array();
		foreach($this->provider_data1() as $pair)
		$data[$pair[0]] = $pair[1];

		// Set values
		$res = $cache->setMulti($data);
		$this->assertTrue($res);

		// Get values;
		foreach($data as $key => $value) {
			$res = $cache->get($key, $succ);
			$this->assertTrue($succ);
			$this->assertEquals($value, $res);
		}
	}

	/**
	 * @dataProvider provider_impl
	 * @depends testOverwriteMultiSetGet
	 */
	public function testMultiGet($cache)
	{
		$data = array();
		$keys = array();
		foreach($this->provider_data1() as $pair) {
			$keys[] = $pair[0];
			$data[$pair[0]] = $pair[1];
		}

		// Get values
		$res = $cache->getMulti($keys);
		$this->assertEquals($data, $res);

		// Erase values
		$cache->deleteAll();

		// Get values
		$res = $cache->getMulti($keys);
		$this->assertEquals(array(), $res);
	}

	/**
	 * @dataProvider provider_impl_data1
	 * @depends testMultiGet
	 */
	public function testTTLSetGet($cache, $key, $value)
	{
		// Set values with 2 seconds ttl
		$res = $cache->set($key, $value, 2);
		$this->assertTrue($res);

		// Check that value exists
		$res = $cache->get($key, $succ);
		$this->assertTrue($succ);
		$this->assertEquals($value, $res);
	}

	/**
	 * @depends testTTLSetGet
	 */
	public function testForceTTLExpiration()
	{   
		// Force cache expiration
		sleep(3);
	}

	/**
	 * @dataProvider provider_impl_data1
	 * @depends testForceTTLExpiration
	 */
	public function testTTLExpiredGet($cache, $key, $value)
	{   // Skip APC Checks as an optimazation feature does invalidate
		// cache in same request making unit test useless.
		if ($cache instanceof Cache_Apc)
		return;

		// Check that value exists
		$res = $cache->get($key, $succ);
		$this->assertFalse($succ);
	}

	/**
	 * @dataProvider provider_impl
	 * @depends testTTLExpiredGet
	 */
	public function testAfterTTLDeleteAll($cache)
	{
		$cache->deleteAll();
	}

	/**
	 * @dataProvider provider_impl_data2
	 * @depends testAfterTTLDeleteAll
	 */
	public function testTTLAddGet($cache, $key, $value)
	{
		// Set values with 2 seconds ttl
		$res = $cache->add($key, $value, 2);
		$this->assertTrue($res);

		// Check that value exists
		$res = $cache->get($key, $succ);
		$this->assertTrue($succ);
		$this->assertEquals($value, $res);
	}

	/**
	 * @depends testTTLAddGet
	 */
	public function testForceTTLAddExpiration()
	{
		// Force cache expiration
		sleep(3);
	}

	/**
	 * @dataProvider provider_impl_data2
	 * @depends testForceTTLAddExpiration
	 */
	public function testTTLAddExpiredGet($cache, $key, $value)
	{
		// Skip APC Checks as an optimazation feature does invalidate
		// cache in same request making unit test useless.
		if ($cache instanceof Cache_Apc)
		return;

		// Check that value exists
		$res = $cache->get($key, $succ);
		$this->assertFalse($succ);
	}
}
