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
        $role = new Authz_Role_Instance('admin');
        $this->assertEquals($role->get_name(), 'admin');
        $this->assertEquals($role->get_parents(), array());
        $this->assertFalse($role->has_parent('admin'));
        $this->assertFalse($role->has_parent(null));

        $role = new Authz_Role_Instance('admin', 'test');
        $this->assertEquals($role->get_name(), 'admin');
        $this->assertEquals($role->get_parents(), array('test'));
        $this->assertFalse($role->has_parent('admin'));
        $this->assertFalse($role->has_parent(null));
        $this->assertTrue($role->has_parent('test'));
        
        $role = new Authz_Role_Instance('super-admin', array('network-admin', 'disk-admin') );
        $this->assertEquals($role->get_name(), 'super-admin');
        $this->assertEquals($role->get_parents(), array('network-admin', 'disk-admin'));
        $this->assertFalse($role->has_parent('super-admin'));
        $this->assertFalse($role->has_parent(null));
        $this->assertTrue($role->has_parent('network-admin'));
        $this->assertTrue($role->has_parent('disk-admin'));
    }
}
?>
