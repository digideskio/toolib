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


use toolib\Options;
use toolib\Event;

require_once __DIR__ .  '/path.inc.php';

class OptionsTest extends PHPUnit_Framework_TestCase
{
	
	public function commonActionTests(Options $options, $startup_count)
	{
		$options->add('key1', 'value1');
		$this->assertEquals(1 + $startup_count, count($options));
		$this->assertTrue(isset($options['key1']));
		$this->assertTrue($options->has('key1'));
		$this->assertEquals('value1', $options->get('key1'));

		$options->add('key2', 'value2');
		$this->assertEquals(2 + $startup_count, count($options));
		$this->assertTrue(isset($options['key1']));
		$this->assertTrue(isset($options['key2']));
		$this->assertTrue($options->has('key2'));
		$this->assertEquals('value2', $options->get('key2'));

		$options->add('complex!@#$%^&*()_+=-;\':\"|\][?>.,', 'value3');
		$this->assertEquals(3 + $startup_count, count($options));
		$this->assertTrue(isset($options['key1']));
		$this->assertTrue(isset($options['complex!@#$%^&*()_+=-;\':\"|\][?>.,']));
		$this->assertTrue($options->has('complex!@#$%^&*()_+=-;\':\"|\][?>.,'));
		$this->assertEquals('value3', $options->get('complex!@#$%^&*()_+=-;\':\"|\][?>.,'));
		
		$options->remove('complex!@#$%^&*()_+=-;\':\"|\][?>.,', 'value3');
		$this->assertFalse(isset($options['complex!@#$%^&*()_+=-;\':\"|\][?>.,']));
		$this->assertFalse($options->has('complex!@#$%^&*()_+=-;\':\"|\][?>.,'));
		$this->assertNull($options->get('complex!@#$%^&*()_+=-;\':\"|\][?>.,'));
		$this->assertEquals(2 + $startup_count, count($options));
		$this->assertTrue(isset($options['key1']));		
		
		$options->remove('key2', 'value3');
		$this->assertFalse(isset($options['key2']));
		$this->assertFalse($options->has('key2'));
		$this->assertNull($options->get('key2'));
		$this->assertEquals(1 + $startup_count, count($options));
		$this->assertTrue(isset($options['key1']));
		
		$options->remove('key1', 'value3');
		$this->assertFalse(isset($options['key1']));
		$this->assertFalse($options->has('key1'));
		$this->assertNull($options->get('key1'));
		$this->assertEquals($startup_count, count($options));
	}
	
	public function commonFalsyTests($options)
	{
		$this->assertFalse(isset($options['wrong']));
		$this->assertFalse($options->has('wrong'));
		$this->assertNull($options->get('wrong'));
		$this->assertNull($options->remove('wrong'));
	}
	
	public function testCreateEmpty()
	{
		$options = new Options(array());
		$this->assertEquals(0, count($options));
		
		$this->commonFalsyTests($options);
		$this->commonActionTests($options, 0);
	}

	public function testCreateFromArray()
	{
		$options = new Options(array('fix-key1' => 'value1', 'fix-key2' => 'value2'));
		$this->assertEquals(2, count($options));

		//Check prepopulated		
		$this->assertTrue($options->has('fix-key1'));
		$this->assertEquals('value1', $options->get('fix-key1'));
		$this->assertTrue($options->has('fix-key2'));
		$this->assertEquals('value2', $options->get('fix-key2'));
		
		$this->commonFalsyTests($options);

		$this->commonActionTests($options, 2);
	}
	
	public function testCreateFromObject()
	{
		$optobj = new Options(array('fix-key1' => 'value1', 'fix-key2' => 'value2'));
		$options = new Options($optobj);
		$this->assertEquals(2, count($options));

		//Check prepopulated		
		$this->assertTrue($options->has('fix-key1'));
		$this->assertEquals('value1', $options->get('fix-key1'));
		$this->assertTrue($options->has('fix-key2'));
		$this->assertEquals('value2', $options->get('fix-key2'));
		
		$this->commonFalsyTests($options);

		$this->commonActionTests($options, 2);
	}
	
	public function testCreateDefaultValues()
	{
		$options = new Options(array('fix-key3' => 'myvalue3'),
			array('fix-key1' => 'value1', 'fix-key2' => 'value2', 'fix-key3' => 'value3'));
		$this->assertEquals(3, count($options));

		//Check prepopulated		
		$this->assertTrue($options->has('fix-key1'));
		$this->assertEquals('value1', $options->get('fix-key1'));
		$this->assertTrue($options->has('fix-key2'));
		$this->assertEquals('value2', $options->get('fix-key2'));
		$this->assertTrue($options->has('fix-key3'));
		$this->assertEquals('myvalue3', $options->get('fix-key3'));
		$this->commonFalsyTests($options);

		$this->commonActionTests($options, 3);
	}
	
	public function testCreateMandatory()
	{
		$options = new Options(array('fix-key1' => 'value1', 'fix-key2' => 'value2'),
			array(),
			array('fix-key1'));
		$this->assertEquals(2, count($options));

		//Check prepopulated		
		$this->assertTrue($options->has('fix-key1'));
		$this->assertEquals('value1', $options->get('fix-key1'));
		$this->assertTrue($options->has('fix-key2'));
		$this->assertEquals('value2', $options->get('fix-key2'));
		
		$this->commonFalsyTests($options);
		$this->commonActionTests($options, 2);
	}

	public function testCreateMandatoryDefaults()
	{
		$options = new Options(array('fix-key1' => 'value1', 'fix-key2' => 'value2'),
			array('fix-key3' => 'value3'),
			array('fix-key3'));
		$this->assertEquals(3, count($options));

		//Check prepopulated		
		$this->assertTrue($options->has('fix-key1'));
		$this->assertEquals('value1', $options->get('fix-key1'));
		$this->assertTrue($options->has('fix-key2'));
		$this->assertEquals('value2', $options->get('fix-key2'));
		$this->assertTrue($options->has('fix-key3'));
		$this->assertEquals('value3', $options->get('fix-key3'));
				
		$this->commonFalsyTests($options);
		$this->commonActionTests($options, 3);
	}
	
	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testCreateMandatoryException()
	{
		$options = new Options(array('fix-key1' => 'value1', 'fix-key2' => 'value2'),
			array(),
			array('not-set-key'));		
	}
}
