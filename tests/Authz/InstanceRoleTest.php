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


use toolib\Authz\Instance\Role;

require_once __DIR__ .  '/../path.inc.php';

class Authz_InstanceRoleTest extends PHPUnit_Framework_TestCase
{
    public function testGeneral()
    {
        $parent = new Role('parent');
        $this->assertEquals($parent->getName(), 'parent');
        $this->assertEquals($parent->getParents(), array());
        $this->assertFalse($parent->hasParent('admin'));
        $this->assertFalse($parent->hasParent(null));

        $child = new Role('child', array($parent));
        $this->assertEquals($child->getName(), 'child');
        $this->assertEquals($child->getParents(), array('parent' => $parent));
        $this->assertFalse($child->hasParent('admin'));
        $this->assertFalse($child->getParent('admin'));
        $this->assertFalse($child->hasParent(null));
        $this->assertTrue($child->hasParent('parent'));
        $this->assertEquals($child->getParent('parent'), $parent);
        
        $mix = new Role('mix', array($parent, $child) );
        $this->assertEquals($mix->getName(), 'mix');
        $this->assertEquals($mix->getParents(), array('parent' => $parent, 'child' => $child));
        $this->assertFalse($mix->hasParent('mix'));
        $this->assertFalse($mix->hasParent(null));
        $this->assertTrue($mix->hasParent('parent'));
        $this->assertEquals($mix->getParent('parent'), $parent);
        $this->assertTrue($mix->hasParent('child'));
        $this->assertEquals($mix->getParent('child'), $child);
    }
}
