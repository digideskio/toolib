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
}
