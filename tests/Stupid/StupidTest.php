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

class Stupid_StupidTest extends PHPUnit_Framework_TestCase
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
	
	public function testConstructors()
	{
		$k = new Stupid\Knowledge();
		
		// Empty constructor
		$s = new Stupid();
		$this->assertNull($s->execute($k));
		$this->assertEquals(array(), $s->getRules());
		$this->assertNull($s->getParent());
		$this->assertInstanceOf('\toolib\EventDispatcher', $s->events());
		
		// Child container
		$s2 = new Stupid($s);
		$this->assertNull($s->execute($k));
		$this->assertEquals(array(), $s->getRules());
		$this->assertNull($s->getParent());
		$this->assertSame($s2->events(), $s->events());	// Same eventdispatcher
	}
	
	public function testCreateRule()
	{
		$k = new Stupid\Knowledge();
		
		// Empty constructor
		$s = new Stupid();
		$rule = $s->createRule('rule1');
		$this->assertSame($rule, $s->getRule('rule1'));
		
		// One invalid rule = nothing
		$this->assertNull($s->execute($k));
		
		// Add a valid rule
		$rule2 = $s->createRule('rule2')->addCondition($this->valid);
		$this->assertSame($rule, $s->getRule('rule1'));
		$this->assertSame($rule2, $s->getRule('rule2'));
		
		// One valid rule = ok (Return the rule)
		$this->assertSame($rule2, $s->execute($k));
	}
	
	
	public function addRule()
	{
		$k = new Stupid\Knowledge();
		
		// Empty constructor
		$s = new Stupid();
		$rule = $s->addRule(new Stupid\Rule($s, 'rule1'));
		$this->assertSame($rule, $s->getRule('rule1'));
		
		// One invalid rule = nothing
		$this->assertNull($s->execute($k));
		
		// Add a valid rule
		$rule2 = $s->addRule(new Stupid\Rule($s, 'rule2'))->addCondition($this->valid);
		$this->assertSame($rule, $s->getRule('rule1'));
		$this->assertSame($rule2, $s->getRule('rule2'));
		
		// One valid rule = ok (Return the rule)
		$this->assertSame($rule2, $s->execute($k));
	}
	
}
