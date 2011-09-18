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


use \toolib\Authz\Acl;
require_once __DIR__ .  '/../path.inc.php';

class AclTest extends PHPUnit_Framework_TestCase
{
   
    public function dataEffectiveAceTestData()
    {
        return array(
            array('read', array(
                array(null, true),
                array('@user', true),
                array('@admin', true),
                array('@logger', false),
                array('@game', true),
                array('unknown', true)
                )
            ),
            array('write', array(
                array(null, null),
                array('@user', false),
                array('@admin', true),
                array('@logger', null),
                array('@game', null),
                array('unknown', null)
                )
            ),
            array('play', array(
                array(null, false),
                array('@user', false),
                array('@admin', false),
                array('@logger', false),
                array('@game', true),
                array('unknown', false)
                )
            ),
        );
    }
    
    /**
     * @dataProvider dataEffectiveAceTestData
     */
    public function testEffectiveAce($test_action, $tests)
    {
        $acl = new Acl();
        $acl->allow(null, 'read');
        $acl->deny('@logger', 'read');
        $acl->deny('@user', 'write');
        $acl->allow('@admin', 'write');
        $acl->deny(null, 'play');
        $acl->allow('@game', 'play');
        
        // Unknown action
        $this->assertNull($acl->effectiveAce(null, 'unknown-action'));
        
        // Read
        foreach($tests as $test)
        {
            if ($test[1] === null)
            {
                $this->assertNull($acl->effectiveAce($test[0], $test_action,
                    "Ace [{$test[0]}, $test_action] must be null"));
                continue;
            }
            
            $this->assertNotNull($acl->effectiveAce($test[0], $test_action),
                "Ace [{$test[0]}, $test_action] must not be null");
            $this->assertEquals($acl->effectiveAce($test[0], $test_action)->getAction(), $test_action);
            
            if ($test[1])
                $this->assertTrue($acl->effectiveAce($test[0], $test_action)->isAllowed());
            else
                $this->assertFalse($acl->effectiveAce($test[0], $test_action)->isAllowed());
        }
    }
    
    public function testEmpty()
    {
        $acl = new Acl();
        $this->assertTrue($acl->isEmpty());
        
        $acl->allow(null, 'read');
        $this->assertFalse($acl->isEmpty());
        $acl->deny('@logger', 'read');
        $this->assertFalse($acl->isEmpty());
        
        $acl->deny('@user', 'write');
        $this->assertFalse($acl->isEmpty());
        $acl->allow('@fs-admin', 'write');
        $this->assertFalse($acl->isEmpty());
        
        $acl->deny(null, 'play');
        $this->assertFalse($acl->isEmpty());
        $acl->allow('@game', 'play');
        $this->assertFalse($acl->isEmpty());
    }
    
    public function testGetAces()
    {
        $acl = new Acl();
        $this->assertEquals($acl->getAces(), array());
        
        $acl->allow(null, 'read');
        $this->assertType('array', $acl->getAces());
        $this->assertEquals(count($acl->getAces()), 1);
        $acl->deny('@logger', 'read');
        $this->assertEquals(count($acl->getAces()), 2);
        
        // Rewrite same rule
        $acl->allow('@logger', 'read');
        $this->assertEquals(count($acl->getAces()), 2);
        
        $acl->deny('@user', 'write');
        $this->assertEquals(count($acl->getAces()), 3);
        $acl->allow('@fs-admin', 'write');
        $this->assertEquals(count($acl->getAces()), 4);
    }
    
    public function testRemoveAce()
    {
        $acl = new Acl();
        $this->assertEquals($acl->getAces(), array());
        
        $acl->allow(null, 'read');
        $acl->deny('@logger', 'read');
        $acl->allow('@logger', 'read');
        $acl->deny('@user', 'write');
        $acl->allow('@fs-admin', 'write');
        
        $this->assertEquals(count($acl->getAces()), 4);
        $acl->removeAce('@fs-admin', 'write');
        $this->assertEquals(count($acl->getAces()), 3);
    }
}
