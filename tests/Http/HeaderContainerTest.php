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

use toolib\Http\HeaderContainer;

require_once __DIR__ .  '/../path.inc.php';

class Http_HeaderContainerTest extends PHPUnit_Framework_TestCase
{
    public function testEmptyConstructor()
    {
    	$c = new HeaderContainer();
    	$this->assertEquals(0, count($c));
    	$this->assertNull($c->getValue('unknown'));
		$this->assertInternalType('array', $c->getValues('unknown'));
    	$this->assertEquals(0, count($c->getValues('unknown')));		
    }
    
    public function testNullConstructor()
    {
    	$c = new HeaderContainer(null);
    	$this->assertEquals(0, count($c));
    	$this->assertNull($c->getValue('unknown'));
    	$this->assertInternalType('array', $c->getValues('unknown'));
    	$this->assertEquals(0, count($c->getValues('unknown')));    
    }
    
    public function testArrayConstructor()
    {
    	$c = new HeaderContainer(array('a' => 'va', 'b' => 'vb'));
    	$this->assertEquals(2, count($c));
    	$this->assertNull($c->getValue('unknown'));
    	$this->assertEquals('va', $c->getValue('a'));
    	$this->assertEquals('vb', $c->getValue('b'));
    	$this->assertInternalType('array', $c->getValues('unknown'));
    	$this->assertEquals(0, count($c->getValues('unknown')));
    }
    
    public function testReadValues()
    {
    	$c = new HeaderContainer(array('a' => 'va', 'b' => 'vb'));
    	$c->add('c', 'vc1');
    	$c->add('c', 'vc2');
    	
    	// Has
    	$this->assertFalse($c->has('unknown'));
    	$this->assertTrue($c->has('a'));
    	$this->assertTrue($c->has('b'));
    	$this->assertTrue($c->has('c'));
    	
    	// getValue - no default
    	$this->assertNull($c->getValue('unknown'));
    	$this->assertEquals('va', $c->getValue('a'));
    	$this->assertEquals('vb', $c->getValue('b'));
    	$this->assertEquals('vc1', $c->getValue('c'));
    	
    	// getValue - customDefault
    	$this->assertEquals('def', $c->getValue('unknown', 'def'));
    	$this->assertEquals('va', $c->getValue('a', 'def'));
    	$this->assertEquals('vb', $c->getValue('b', 'def'));
    	$this->assertEquals('vc1', $c->getValue('c', 'def'));
    	
    	// getValues
    	$this->assertEquals(array(), $c->getValues('unknown'));
    	$this->assertEquals(array('va'), $c->getValues('a'));
    	$this->assertEquals(array('vb'), $c->getValues('b'));
    	$this->assertEquals(array('vc1', 'vc2'), $c->getValues('c'));

    	// countValues
    	$this->assertEquals(0, $c->countValues('unknown'));
    	$this->assertEquals(1, $c->countValues('a'));
    	$this->assertEquals(1, $c->countValues('b'));
    	$this->assertEquals(2, $c->countValues('c'));
    }
    
    /**
     * @depends testReadValues
     */
    public function testWriteValues()
    {
    	$c = new HeaderContainer(array('a' => 'va', 'b' => 'vb'));

    	// Add a new value
    	$c->add('c', 'vc1');
    	$this->assertTrue($c->has('c'));
    	$this->assertEquals(1, $c->countValues('c'));
    	$this->assertEquals('vc1', $c->getValue('c'));
    	$this->assertEquals(array('vc1'), $c->getValues('c'));
    	
    	// Add second value to existing one.
    	$c->add('b', 'vb2');
    	$this->assertTrue($c->has('b'));
    	$this->assertEquals(2, $c->countValues('b'));
    	$this->assertEquals('vb', $c->getValue('b'));
    	$this->assertEquals(array('vb', 'vb2'), $c->getValues('b'));
    	
    	// Replace an value
    	$c->replace('d', 'vd3');
    	$this->assertTrue($c->has('d'));
    	$this->assertEquals(1, $c->countValues('d'));
    	$this->assertEquals('vd3', $c->getValue('d'));
    	$this->assertEquals(array('vd3'), $c->getValues('d'));

    	// Replace an existing value
    	$c->replace('b', 'vb3');
    	$this->assertTrue($c->has('b'));
    	$this->assertEquals(1, $c->countValues('b'));
    	$this->assertEquals('vb3', $c->getValue('b'));
    	$this->assertEquals(array('vb3'), $c->getValues('b'));
    	
    	// Remove non-existing values
    	@$c->remove('wrong');
    	$this->assertFalse($c->has('wrong'));
    	
    	// Remove existing values
    	$c->remove('a');
    	$this->assertFalse($c->has('a'));
    	$this->assertNull($c->getValue('a'));
    	$this->assertEquals(array(), $c->getValues('a'));

    	// Remove existing value - array
    	$c->remove('b');
    	$this->assertFalse($c->has('b'));
    	$this->assertNull($c->getValue('b'));
    	$this->assertEquals(array(), $c->getValues('b'));
    }
    
    public function testIs()
    {
    	$c = new HeaderContainer(array(
    		'h1' => 'value1',
    		'h2' => 'Value2',
    		'h3' => 'VaLue3',
    		'h4' => '-value4-'));
    	$c->add('h3', 'Value4');
    	
    	// Check case-sensitive
    	$this->assertTrue($c->is('h1', 'value1'));
    	$this->assertFalse($c->is('h2', 'value2'));
    	$this->assertFalse($c->is('h3', 'value3'));
    	$this->assertFalse($c->is('h3', 'value4'));
    	$this->assertFalse($c->is('h4', 'value4'));
    	
    	// Check case-insensitive
    	$this->assertTrue($c->is('h1', 'value1', true));
    	$this->assertTrue($c->is('h2', 'value2', true));
    	$this->assertTrue($c->is('h3', 'value3', true));
    	$this->assertTrue($c->is('h3', 'value4', true));
    	$this->assertFalse($c->is('h4', 'value4', true));
    }
    
    public function testContains()
    {
    	$c = new HeaderContainer(array(
        		'h1' => '-value1-',
        		'h2' => '-Value2-',
        		'h3' => '-VaLue3-',
    			'h4' => 'value4'));
    	$c->add('h3', '-Value4-');
    	 
    	// Check case-sensitive
    	$this->assertTrue($c->contains('h1', 'value1'));
    	$this->assertFalse($c->contains('h2', 'value2'));
    	$this->assertFalse($c->contains('h3', 'value3'));
    	$this->assertFalse($c->contains('h3', 'value4'));
    	$this->assertTrue($c->contains('h4', 'value4'));
    	
    	// Check case-insensitive
    	$this->assertTrue($c->contains('h1', 'value1', true));
    	$this->assertTrue($c->contains('h2', 'value2', true));
    	$this->assertTrue($c->contains('h3', 'value3', true));
    	$this->assertTrue($c->contains('h3', 'value4', true));
    	$this->assertTrue($c->contains('h4', 'value4', true));
    }
}
