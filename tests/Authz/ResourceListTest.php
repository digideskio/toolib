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

class Authz_ResourceListTest extends PHPUnit_Framework_TestCase
{
    public function roleFeeder()
    {
        $roles = new Authz_RoleFeederInstance();
        $roles->add_role(new Authz_Role('@game'));
        $roles->add_role(new Authz_Role('@video'));
        $roles->add_role(new Authz_Role('@user', array('@game', '@video')));
        $roles->add_role(new Authz_Role('@web-user'));
        $roles->add_role(new Authz_Role('@web-admin', '@web-user'));
        $roles->add_role(new Authz_Role('@fs-admin'));
        $roles->add_role(new Authz_Role('@logger'));
        $roles->add_role(new Authz_Role('@admin', array('@user', '@web-admin', '@fs-admin')));
        return $roles;
    }
    
    public function testGeneral()
    {
        $list = new Authz_ResourceList();
        
        $this->assertFalse($list->has_resource('test'));
        $this->assertFalse($list->get_resource('test'));

        $list->add_resource('directory');
        $this->assertTrue($list->has_resource('directory'));
        $this->assertType('Authz_Resource', $list->get_resource('directory'));

        $list->add_resource('file', 'directory');
        $this->assertTrue($list->has_resource('directory'));
        $this->assertType('Authz_Resource', $list->get_resource('directory'));
        $this->assertTrue($list->has_resource('file'));
        $this->assertType('Authz_Resource', $list->get_resource('file'));
    }
    
    public function testRemoveResource()
    {
        $list = new Authz_ResourceList();

        // Add and readd a resource
        $dir = $list->add_resource('directory');
        $this->assertTrue($list->has_resource('directory'));
        $this->assertFalse($list->has_resource('file'));
        $this->assertTrue($list->remove_resource('directory'));
        $this->assertFalse($list->has_resource('directory'));
        $this->assertFalse($list->remove_resource('directory'));
        $this->assertFalse($list->has_resource('directory'));
        $dir = $list->add_resource('directory');
        $file = $list->add_resource('file', 'directory');
        $this->assertTrue($list->has_resource('directory'));
        $this->assertTrue($list->has_resource('file'));
        $this->assertTrue($list->remove_resource('file'));
        $this->assertTrue($list->remove_resource('directory'));
    }
    
    /**
     * @expectedException RuntimeException
     */
    public function testRemoveResourceException()
    {
        $list = new Authz_ResourceList();
        $dir = $list->add_resource('directory');
        $file = $list->add_resource('file', 'directory');
        $list->remove_resource('directory');
    }
    
    public function testGetResource()
    {
        $list = new Authz_ResourceList();
        $dir = $list->add_resource('directory');
        $file = $list->add_resource('file', 'directory');
     
        $this->assertFalse($list->get_resource('unknown'));
        $this->assertFalse($list->get_resource('unknown', 'unknown id'));
        
        // General inheritance
        $this->assertSame($list->get_resource('directory'), $dir);
        $this->assertSame($list->get_resource('file'), $file);
        $this->assertSame($list->get_resource('file')->get_parent(), $dir);
        
        // Instances
        $this->assertType('Authz_Resource', $list->get_resource('directory', 'test'), $dir);
        $this->assertSame($list->get_resource('directory', 'test'), $list->get_resource('directory', 'test'));
        $this->assertSame($list->get_resource('directory', 'test')->get_parent(), $dir);
        $this->assertSame($list->get_resource('file', 'test')->get_parent(), $file);
        $this->assertSame($list->get_resource('file', 'test')->get_parent()->get_parent(), $dir);
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testSameResourceException()
    {
        $list = new Authz_ResourceList($this->roleFeeder());
        $list->add_resource('dir');
        $list->add_resource('dir', 'file');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testBrokenDependencyException()
    {
        $list = new Authz_ResourceList($this->roleFeeder());
        $list->add_resource('dir', 'file');
    }
    
    public function testSerialize()
    {
        $list = new Authz_ResourceList();
        $dir = $list->add_resource('directory');
        $dir->get_acl()->allow(null, 'read');
        $dir->get_acl()->deny(null, 'write');
        $dir->get_acl()->deny(null, 'delete');
        $dir->get_acl()->allow('@fs-admin', 'write');
        
        $file = $list->add_resource('file', 'directory');
        $file->get_acl()->allow('@fs-admin', 'delete');
        
        $list2 = unserialize(serialize($list));
        
        $this->assertNotSame($list, $list2);
        $this->assertSame($list->get_resource('directory'), $list->get_resource('file')->get_parent());

    }
}
?>
