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

use toolib\Net\Http\ParameterContainer;
use toolib\Net\Http\Cookie;

require_once __DIR__ .  '/../path.inc.php';

class Net_ParameterContainerTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
    	$c = new ParameterContainer();
    	$this->assertEquals(0, count($c));
    	$this->assertEquals(null, $c->get('unknown'));
		$this->assertEquals(null, $c->getInt('unknown'));
		$this->assertEquals(null, $c->getDateTime('unknown'));
		$this->assertEquals(null, $c->checkAndGet('/.*/', 'unknown'));
		
		$c = new ParameterContainer(array(
			'param1' => 'value1',
			'param_2' => 'big_long',
			'param_int' => '5',
			'param_date' => '2010-04-25 02:24:16+12:00',));
    	$this->assertEquals(4, count($c));
    	$this->assertEquals(null, $c->get('unknown'));
    	$this->assertEquals('value1', $c->get('param1'));
		$this->assertEquals(null, $c->getInt('unknown'));
		$this->assertEquals(5, $c->getInt('param_int'));
		$this->assertEquals(null, $c->getDateTime('unknown'));
		$this->assertEquals(new \DateTime('2010-04-25 02:24:16+12:00'), $c->getDateTime('param_date'));
		$this->assertEquals(null, $c->checkAndGet('/.*/', 'unknown'));
		$this->assertEquals(null, $c->checkAndGet('/^[a-z]+_[a-z]+$/', 'param_2'));
    }
    
    public function testGet()
    {
    	$c = new ParameterContainer(array(
			'param1' => 'value1',
			'param_2' => 'big_long',
			'param_int' => '5',
			'param_date' => '2010-04-25 02:24:16+12:00',));
    	$this->assertEquals(null, $c->get('unknown'));
    	$this->assertEquals('big_long', $c->get('param_2'));
    	$this->assertEquals('2010-04-25 02:24:16+12:00', $c->get('param_date'));
    	
    	// Test again with a new default value
    	$this->assertEquals('default', $c->get('unknown', 'default'));
    	$this->assertEquals('big_long', $c->get('param_2', 'default'));
    	$this->assertEquals('2010-04-25 02:24:16+12:00', $c->get('param_date', 'default'));
    }

    public function testGetInt()
    {
    	$c = new ParameterContainer(array(
			'param1' => 'value1',
			'param_2' => 'big_long',
			'param_int' => '8',
			'param_date' => '2010-04-25 02:24:16+12:00',));
    	$this->assertEquals(null, $c->getInt('unknown'));
    	$this->assertEquals(0, $c->getInt('param_2'));
    	$this->assertEquals(2010, $c->getInt('param_date'));
    	$this->assertEquals(8, $c->getInt('param_int'));
    	
    	// Test again with a new default value
    	$this->assertEquals(15, $c->getInt('unknown', 15));
    	$this->assertEquals(0, $c->getInt('param_2', 15));
    	$this->assertEquals(2010, $c->getInt('param_date', 15));
    	$this->assertEquals(8, $c->getInt('param_int', 15));
    }
    
    
    public function testGetDateTime()
    {
		$c = new ParameterContainer(array(
			'param1' => 'value1',
			'param_2' => 'big_long',
			'param_int' => '8',
			'param_date' => '2010-04-25 02:24:16+12:00',));
		$date = new DateTime('2010-04-25 02:24:16+12:00');		
		$default_date = new DateTime('2001-02-23 05:44:12+10:00');
		
		// check for phpunit that can compare datetimes
		$this->assertNotEquals($date, $default_date);
		
    	$this->assertEquals(null, $c->getDateTime('unknown'));
    	$this->assertEquals(null, $c->getDateTime('param_2'));
    	$this->assertEquals($date, $c->getDateTime('param_date'));
    	$this->assertEquals(null, $c->getDateTime('param_int'));
    	
    	// Test again with a new default value
    	$this->assertEquals($default_date, $c->getDateTime('unknown', $default_date));
    	$this->assertEquals($default_date, $c->getDateTime('param_2', $default_date));
    	$this->assertEquals($date, $c->getDateTime('param_date', $default_date));
    	$this->assertEquals($default_date, $c->getDateTime('param_int', $default_date));
    }
    
	public function testGetDateTimeFromFormat()
    {
		$c = new ParameterContainer(array(
			'param1' => 'value1',
			'param_2' => 'big_long',
			'param_int' => '8',
			'param_date' => '2010-04-25 02:24:16+12:00',));
		$date = new DateTime('2010-04-25 02:24:16+12:00');		
		$default_date = new DateTime('2001-02-23 05:44:12+10:00');
		
		// check for phpunit that can compare datetimes
		$this->assertNotEquals($date, $default_date);
		var_dump(DateTime::createFromFormat('Y-m-d H:i:sP', '2001-02-23 05:44:12+10:00'));
		
    	$this->assertEquals(null, $c->getDateTimeFromFormat('unknown', 'Y-m-d H:i:sP'));
    	$this->assertEquals(null, $c->getDateTimeFromFormat('param_2', 'Y-m-d H:i:sP'));
    	$this->assertEquals($date, $c->getDateTimeFromFormat('param_date', 'Y-m-d H:i:sP'));
    	$this->assertEquals(null, $c->getDateTimeFromFormat('param_int', 'Y-m-d H:i:sP'));
    	
    	// Test again with a new default value
    	$this->assertEquals($default_date, $c->getDateTimeFromFormat('unknown', 'Y-m-d H:i:sP', $default_date));
    	$this->assertEquals($default_date, $c->getDateTimeFromFormat('param_2', 'Y-m-d H:i:sP', $default_date));
    	$this->assertEquals($date, $c->getDateTimeFromFormat('param_date', 'Y-m-d H:i:sP', $default_date));
    	$this->assertEquals($default_date, $c->getDateTimeFromFormat('param_int', 'Y-m-d H:i:sP', $default_date));
    }
}