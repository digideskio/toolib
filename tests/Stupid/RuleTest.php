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


use toolib\Stupid;
require_once __DIR__ .  '/../path.inc.php';

class Stupid_RuleTest extends PHPUnit_Framework_TestCase
{
	public function setup()
	{
		$this->valid = function(Stupid\Knowledge $k){
				return true;
		};
		
		$this->invalid = function(Stupid\Knowledge $k){
				return false;
		};
		
		$this->mark = function(Stupid\Knowledge $k){
			$k->results['mark'] = isset($k->results['mark'])?$k->results['mark']+1:1;
			return true;
		};
	}
	
	public function testEmptyConstruct()
	{
		$s = new Stupid();
		$r = new Stupid\Rule($s, 'dummy');
		$k = new Stupid\Knowledge();
		
		$this->assertEquals('dummy', $r->getName());
		$this->assertSame($s, $r->getOwner());
		
		// no conditions = fail
		$this->assertFalse($r->execute($k));
		
		// One valid = ok
		$r->addCondition($this->valid);
		$this->assertTrue($r->execute($k));
		
		// One valid + invalid = fail (AND)
		$r->addCondition($this->invalid);
		$this->assertFalse($r->execute($k));
		
		// One invalid = fail
		$r = new Stupid\Rule($s, 'dummy');
		$r->addCondition($this->invalid);
		$this->assertFalse($r->execute($k));
	}
	
	public function testConditionsConstruct()
	{
		$s = new Stupid();		
		$k = new Stupid\Knowledge();
		
		
		// no conditions = fail
		$r = new Stupid\Rule($s, 'dummy', array());
		$this->assertFalse($r->execute($k));

		// One valid = ok
		$r = new Stupid\Rule($s, 'dummy', array($this->valid));
		$this->assertTrue($r->execute($k));
		
		// One valid + invalid = fail (AND)
		$r = new Stupid\Rule($s, 'dummy', array($this->valid, $this->invalid));
		$this->assertFalse($r->execute($k));
		
		// One invalid = fail
		$r = new Stupid\Rule($s, 'dummy', array($this->invalid));
		$this->assertFalse($r->execute($k));
	}
	
	public function testKnowledgeMarking()
	{
		$s = new Stupid();
		$r = new Stupid\Rule($s, 'dummy');
		$k = new Stupid\Knowledge();
		
		// dont mark on failed
		$r->addCondition($this->invalid);
		$r->addCondition($this->mark);
		$this->assertFalse($r->execute($k));
		$this->assertFalse(isset($r->results['mark']));
		
		// mark on success
		$r = new Stupid\Rule($s, 'dummy');
		$r->addCondition($this->mark);
		$this->assertTrue($r->execute($k));
		$this->assertEquals(1, $k->results['mark']);
	}
	
	public function testDoNotCallActionsOnFail()
	{
		$badaction = function(){ throw new \RuntimeException();	};
		
		$s = new Stupid();
		$r = new Stupid\Rule($s, 'dummy');
		$k = new Stupid\Knowledge();
		
		$r->addCondition($this->invalid);
		$r->addAction($badaction);
		$this->assertFalse($r->execute($k));		
	}
	
	public function testCallAllActions()
	{
		$calls = 0;
		$action1 = function()use( & $calls){
			$calls += 5;
		};
		$action2 = function()use( & $calls){
			$calls += 3;
		};
		
		$s = new Stupid();
		$r = new Stupid\Rule($s, 'dummy');
		$k = new Stupid\Knowledge();
		
		$r->addCondition($this->valid);
		$r->addAction($action1);
		$r->addAction($action2);
		$this->assertTrue($r->execute($k));
		$this->assertEquals(8, $calls);
	}
	
	public function testChaining()
	{
		$s = new Stupid();
		$r = new Stupid\Rule($s, 'dummy');
		
		$this->assertSame($r, $r->addCondition($this->valid));
		$this->assertSame($r, $r->addAction($this->valid));
		$this->assertSame($r, $r->addActionChainToClass('WhatEver'));
	}
}
