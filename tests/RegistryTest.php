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
use toolib\Registry;

class RegistryTest extends PHPUnit_Framework_TestCase
{

    public function testConstruct()
    {
        $reg = new Registry();
        $this->assertEquals(0, count($reg));

        $reg = new Registry(array('test1', 'test2', 'test3'));
        $this->assertEquals(3, count($reg));
    }

    public function testGetters()
    {
        $reg = new Registry(array(
            'entry1' => 'value1',
            'entry2' => 'value2',
            'entry3' => 'value3'
        ));
        $this->assertEquals(3, count($reg));
        $this->assertEquals($reg['entry1'], 'value1');
        $this->assertEquals($reg['entry2'], 'value2');
        $this->assertEquals($reg['entry3'], 'value3');
        @$this->assertNull($reg['unknown']);
        $this->assertEquals($reg->offsetGet('entry1'), 'value1');
        $this->assertEquals($reg->offsetGet('entry2'), 'value2');
        $this->assertEquals($reg->offsetGet('entry3'), 'value3');
        @$this->assertNull($reg->offsetGet('unknown'));
    }
    
    public function testSetter()
    {
        $reg = new Registry(array(
            'entry1' => 'value1',
            'entry2' => 'value2',
            'entry3' => 'value3'
        ));
        $this->assertEquals(3, count($reg));

        // Overwrite with set
        $this->assertEquals($reg->offsetGet('entry1'), 'value1');
        $reg->offsetSet('entry1', 'newvalue');
        $this->assertEquals(3, count($reg));
        $this->assertEquals($reg->offsetGet('entry1'), 'newvalue');
        
        // Overwrite with array assignment
        $this->assertEquals($reg->offsetGet('entry2'), 'value2');
        $reg['entry2'] = 'entry2overwritten';
        $this->assertEquals(3, count($reg));
        $this->assertEquals($reg->offsetGet('entry2'), 'entry2overwritten');
        
        // Expand with set
        $reg->offsetSet('entrynew', 'valuenew');
        $this->assertEquals(4, count($reg));
        $this->assertEquals($reg->offsetGet('entrynew'), 'valuenew');
        
        // Expand with array assign
        $reg['entrynew1'] = 'valuenew15';
        $this->assertEquals(5, count($reg));
        $this->assertEquals($reg->offsetGet('entrynew1'), 'valuenew15');
    }
    
    public function testExist()
    {
        $reg = new Registry(array(
            'entry1' => 'value1',
            'entry2' => 'value2',
            'entry3' => 'value3'
        ));
        
        // Array isset
        $this->assertTrue(isset($reg['entry1']));
        $this->assertTrue(isset($reg['entry2']));
        $this->assertFalse(isset($reg['unknown']));
    }
    
    public function testRemove()
    {
        $reg = new Registry(array(
            'entry1' => 'value1',
            'entry2' => 'value2',
            'entry3' => 'value3'
        ));
        $this->assertEquals(3, count($reg));
        unset($reg['entry2']);
        $this->assertEquals(2, count($reg));
        @$this->assertNull($reg->offsetGet('entry2'));

    }
    
    public function testIterator()
    {
        $reg = new Registry(array(
            'entry1' => 'value1',
            'entry2' => 'value2',
            'entry3' => 'value3'
        ));

        $iterated = array();        
        foreach($reg as $key => $value)
            $iterated[$key] = $value;
        $this->assertSame($reg->getArrayCopy(), $iterated);
        
        
        // Empty
        $reg = new Registry();
        $iterated = array();        
        foreach($reg as $key => $value)
            $iterated[$key] = $value;
        $this->assertSame($reg->getArrayCopy(), array());
        $this->assertSame($reg->getArrayCopy(), $iterated);

    }
    
    public function testStatic()
    {
        $this->assertType('toolib\Registry', Registry::getInstance());
        $this->assertEquals(0, count(Registry::getInstance()));
        
        // Set
        Registry::set('entry1', 'value1');
        $this->assertEquals(1, count(Registry::getInstance()));
        $this->assertEquals('value1', Registry::get('entry1'));
        Registry::set('entry1', 'valuenew1');
        $this->assertEquals(1, count(Registry::getInstance()));
        $this->assertEquals('valuenew1', Registry::get('entry1'));
        
        Registry::set('entry2', 'value2');
        $this->assertEquals(2, count(Registry::getInstance()));
        $this->assertEquals('value2', Registry::get('entry2'));
        
        // Get empty
        $this->assertNull(Registry::get('unknown'));
        
        // Has
        $this->assertTrue(Registry::has('entry1'));
        $this->assertFalse(Registry::has('unknwon'));
        
    }
}
