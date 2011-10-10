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


use toolib\Stupid\Knowledge;
require_once __DIR__ .  '/../path.inc.php';

class Stupid_KnowledgeTest extends PHPUnit_Framework_TestCase
{

	public function testEmptyConstruct()
	{
		$k = new Knowledge();
		$this->assertEquals('wrong-value', $k->getOptionalFact('wrong', 'wrong-value'));
		$this->assertEquals(0, count($k->facts));
		$this->assertEquals(0, count($k->results));
		$this->assertEquals(0, count($k->extractors));
		$this->assertInternalType('array', $k->results);
		$this->assertInternalType('array', $k->facts);
		$this->assertInternalType('array', $k->extractors);
	}
	
	public function testPredefinedFactsConstruct()
	{
		$facts = array('fact1' => 'value1', 'fact2' => 'value2');
		$k = new Knowledge($facts);
		$this->assertEquals('wrong-value', $k->getOptionalFact('wrong', 'wrong-value'));
		$this->assertEquals(2, count($k->facts));
		$this->assertEquals($facts, $k->facts);
		$this->assertEquals(0, count($k->results));
		$this->assertEquals(0, count($k->extractors));
		$this->assertInternalType('array', $k->results);
		$this->assertInternalType('array', $k->facts);
		$this->assertInternalType('array', $k->extractors);
	}
	
	public function testGetFact()
	{
		$facts = array('fact1' => 'value1', 'fact2' => 'value2');
		$k = new Knowledge($facts);
		$this->assertEquals('value2', $k->getFact('fact2'));				
	}
	
	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testWrongGetFact()
	{
		$facts = array('fact1' => 'value1', 'fact2' => 'value2');
		$k = new Knowledge($facts);
		$k->getFact('fact-wrong');
	}
	
	public function testGetOptionalFacts()
	{
		$facts = array('fact1' => 'value1', 'fact2' => 'value2');
		$k = new Knowledge($facts);
		$this->assertEquals('value1', $k->getOptionalFact('fact1', null));
		$this->assertNull($k->getOptionalFact('wrong', null));
		$this->assertEquals('default', $k->getOptionalFact('wrong', 'default'));
	}
	
	public function testSetResults()
	{
		$facts = array('fact1' => 'value1', 'fact2' => 'value2');
		$k = new Knowledge($facts);
		
		$k->setResult('result.1', 'value.1.1');		
		$this->assertEquals(array('result.1' => 'value.1.1'), $k->results);
		
		$k->setResult('result.1', 'value.1.2');
		$this->assertEquals(array('result.1' => 'value.1.2'), $k->results);
		
		$k->setResult('result.2', 'value.2.1');
		$this->assertEquals(array('result.1' => 'value.1.2', 'result.2' => 'value.2.1'), $k->results);
	}
	
	public function testSetExtractor()
	{
		$facts = array('fact1' => 'value1', 'fact2' => 'value2');
		$k = new Knowledge($facts);
	
		$exc1 = function(){
			return array();
		};
		$exc2 = function(){
			return array();
		};
		
		// Add
		$k->setExtractor('extractor.1', $exc1);
		$this->assertSame(array('extractor.1' => $exc1), $k->extractors);		
	
		// Replace
		$k->setExtractor('extractor.1', $exc2);
		$this->assertSame(array('extractor.1' => $exc2), $k->extractors);		
	}
	
	public function testAddExtractor()
	{
		$facts = array('fact1' => 'value1', 'fact2' => 'value2');
		$k = new Knowledge($facts);
		
		$exc1 = function(){
			return array();
		};
		$exc2 = function(){
			return array();
		};
		
		// Append
		$k->addExtractor($exc1);
		$this->assertEquals(array($exc1), $k->extractors);
		$this->assertSame($exc1, $k->extractors[0]);
		
		// Append
		$k->addExtractor($exc2);
		$this->assertSame(array($exc1, $exc2), $k->extractors);		
	}
	
	public function testExtractFacts()
	{
		$facts = array('fact1' => 'value1', 'fact2' => 'value2');
		$k = new Knowledge($facts);
		
		$exc1 = function($results){
			return array('more.1' => 'exc1', 'fact2' => 'newvalue2');
		};
		$exc2 = function($results){
			return array('more.2' => 'exc2', 'fact2' => 'newvalue3');
		};
			

		// Extract and clear
		$k->addExtractor($exc1);
		$k->extractFacts();
		$this->assertEquals(array('fact1' => 'value1', 'fact2' => 'newvalue2', 'more.1' => 'exc1'), $k->facts);
		$this->assertEquals(array(), $k->extractors);
		
		// Multiple but do not clear
		$k->addExtractor($exc1);
		$k->addExtractor($exc2);
		$k->extractFacts(false);
		$this->assertEquals(array('fact1' => 'value1', 'fact2' => 'newvalue3', 'more.1' => 'exc1', 'more.2' => 'exc2'), $k->facts);
		$this->assertSame(array($exc1, $exc2), $k->extractors);
		
	}
	
	public function testReplace()
	{
		$facts = array('fact1' => 'value1', 'fact2' => 'value2');
		$k = new Knowledge($facts);
		$k->setResult('result.1', 'value1');		
		$exc1 = function(){
			return array();
		};
		$exc2 = function(){
			return array();
		};
		$k->addExtractor($exc1);
		$k->addExtractor($exc2);
		
		$k2 = new Knowledge();
		$k2->replaceBy($k);
		
		$this->assertEquals($facts, $k2->facts);
		$this->assertEquals(array('result.1' => 'value1'), $k2->results);
		$this->assertSame(array($exc1, $exc2), $k2->extractors);
	}
}
