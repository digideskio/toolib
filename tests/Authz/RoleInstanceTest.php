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


require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) .  '/../path.inc.php';

class Authz_RoleInstanceTest extends PHPUnit_Framework_TestCase
{
    public function testGeneral()
    {
        $parent = new Authz_Role_Instance('parent');
        $this->assertEquals($parent->get_name(), 'parent');
        $this->assertEquals($parent->get_parents(), array());
        $this->assertFalse($parent->has_parent('admin'));
        $this->assertFalse($parent->has_parent(null));

        $child = new Authz_Role_Instance('child', array($parent));
        $this->assertEquals($child->get_name(), 'child');
        $this->assertEquals($child->get_parents(), array('parent' => $parent));
        $this->assertFalse($child->has_parent('admin'));
        $this->assertFalse($child->get_parent('admin'));
        $this->assertFalse($child->has_parent(null));
        $this->assertTrue($child->has_parent('parent'));
        $this->assertEquals($child->get_parent('parent'), $parent);
        
        $mix = new Authz_Role_Instance('mix', array($parent, $child) );
        $this->assertEquals($mix->get_name(), 'mix');
        $this->assertEquals($mix->get_parents(), array('parent' => $parent, 'child' => $child));
        $this->assertFalse($mix->has_parent('mix'));
        $this->assertFalse($mix->has_parent(null));
        $this->assertTrue($mix->has_parent('parent'));
        $this->assertEquals($mix->get_parent('parent'), $parent);
        $this->assertTrue($mix->has_parent('child'));
        $this->assertEquals($mix->get_parent('child'), $child);
    }
}
?>
