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

class Authz_AuthzTest extends PHPUnit_Framework_TestCase
{
    public function roleFeeder()
    {
        $roles = new Authz_Role_FeederInstance();
        $roles->add_role('@game');
        $roles->add_role('@video');
        $roles->add_role('@user', array('@game', '@video'));
        $roles->add_role('@web-user');
        $roles->add_role('@web-admin', '@web-user');
        $roles->add_role('@fs-admin');
        $roles->add_role('@logger');
        $roles->add_role('@admin', array('@user', '@web-admin', '@fs-admin'));
        return $roles;
    }
    
    public function testSetGet()
    {
        $list = Authz::get_resource_list();
        $this->assertType('Authz_ResourceList', $list);
        $list2 = new Authz_ResourceList();
        Authz::set_resource_list($list2);
        $this->assertSame(Authz::get_resource_list(), $list2);
        $this->assertNotSame($list, $list2);

        
        $roles1 = $this->roleFeeder();
        $roles2 = $this->roleFeeder();
        $this->assertNotSame($roles1, $roles2);
        
        Authz::set_role_feeder($roles1);
        $this->assertSame(Authz::get_role_feeder(), $roles1);

        Authz::set_role_feeder($roles2);
        $this->assertSame(Authz::get_role_feeder(), $roles2);
    }
    
    /**
     * @depends testSetGet
     */
    public function testIsRoleAllowed()
    {
        Authz::set_resource_list($list = new Authz_ResourceList());
        Authz::set_role_feeder($this->roleFeeder());
        $dir = $list->add_resource('directory');
        Authz::allow('directory', null, 'read');
        Authz::deny('directory', null, 'write');
        Authz::allow('directory', 'admin', 'write');
        Authz::allow('directory', 'user', 'list');

        $file = $list->add_resource('file', 'directory');
        Authz::allow('file', 'user', 'execute');
        Authz::deny('file', null, 'list');
        
        Authz::allow(array('file', '/') , null, 'list');

        $this->assertFalse(Authz::is_role_allowed_to(null, 'directory', 'unknown'));
        $this->assertTrue(Authz::is_role_allowed_to(null, 'directory', 'read'));
        $this->assertFalse(Authz::is_role_allowed_to(null, 'directory', 'write'));
        $this->assertFalse(Authz::is_role_allowed_to(null, 'directory', 'execute'));
        $this->assertFalse(Authz::is_role_allowed_to(null, 'directory', 'list'));
        $this->assertTrue(Authz::is_role_allowed_to('admin', 'directory', 'write'));
        $this->assertFalse(Authz::is_role_allowed_to('admin', 'directory', 'execute'));
        $this->assertFalse(Authz::is_role_allowed_to('admin', 'directory', 'list'));
        $this->assertFalse(Authz::is_role_allowed_to('user', 'directory', 'execute'));
        $this->assertTrue(Authz::is_role_allowed_to('user', 'directory', 'list'));
        
        $this->assertFalse(Authz::is_role_allowed_to(null, 'file', 'unknown'));
        $this->assertTrue(Authz::is_role_allowed_to(null, 'file', 'read'));
        $this->assertFalse(Authz::is_role_allowed_to(null, 'file', 'write'));
        $this->assertFalse(Authz::is_role_allowed_to(null, 'file', 'execute'));
        $this->assertFalse(Authz::is_role_allowed_to(null, 'file', 'list'));
        $this->assertTrue(Authz::is_role_allowed_to('admin', 'file', 'write'));
        $this->assertFalse(Authz::is_role_allowed_to('admin', 'file', 'execute'));
        $this->assertFalse(Authz::is_role_allowed_to('admin', 'file', 'list'));
        $this->assertTrue(Authz::is_role_allowed_to('user', 'file', 'execute'));
        $this->assertFalse(Authz::is_role_allowed_to('user', 'file', 'list'));
        $this->assertFalse(Authz::is_role_allowed_to('user', array('file', 'unknown'), 'list'));
        $this->assertTrue(Authz::is_role_allowed_to('user', array('file', '/'), 'list'));
    }
    
    public function testIsAllowed()
    {
        Authz::set_resource_list($list = new Authz_ResourceList());
        Authz::set_role_feeder($this->roleFeeder());
        $dir = $list->add_resource('directory');
        Authz::allow('directory', null, 'read');
        Authz::deny('directory', null, 'write');
        Authz::allow('directory', 'admin', 'write');
        Authz::allow('directory', 'user', 'list');

        $file = $list->add_resource('file', 'directory');
        Authz::allow('file', 'user', 'execute');
        Authz::deny('file', null, 'list');
        
        Authz::allow(array('file', '/') , null, 'list');

        Authz::set_current_role_func(create_function('', 'return null;'));
        $this->assertFalse(Authz::is_allowed('directory', 'unknown'));
        $this->assertTrue(Authz::is_allowed('directory', 'read'));
        $this->assertFalse(Authz::is_allowed('directory', 'write'));
        $this->assertFalse(Authz::is_allowed('directory', 'execute'));
        $this->assertFalse(Authz::is_allowed('directory', 'list'));
        Authz::set_current_role_func(create_function('', 'return "admin";'));
        $this->assertTrue(Authz::is_allowed('directory', 'write'));
        $this->assertFalse(Authz::is_allowed('directory', 'execute'));
        $this->assertFalse(Authz::is_allowed('directory', 'list'));
        Authz::set_current_role_func(create_function('', 'return "user";'));
        $this->assertFalse(Authz::is_allowed('directory', 'execute'));
        $this->assertTrue(Authz::is_allowed('directory', 'list'));


        Authz::set_current_role_func(create_function('', 'return null;'));
        $this->assertFalse(Authz::is_allowed('file', 'unknown'));
        $this->assertTrue(Authz::is_allowed('file', 'read'));
        $this->assertFalse(Authz::is_allowed('file', 'write'));
        $this->assertFalse(Authz::is_allowed('file', 'execute'));
        $this->assertFalse(Authz::is_allowed('file', 'list'));
        Authz::set_current_role_func(create_function('', 'return "admin";'));
        $this->assertTrue(Authz::is_allowed('file', 'write'));
        $this->assertFalse(Authz::is_allowed('file', 'execute'));
        $this->assertFalse(Authz::is_allowed('file', 'list'));
        Authz::set_current_role_func(create_function('', 'return "user";'));
        $this->assertTrue(Authz::is_allowed('file', 'execute'));
        $this->assertFalse(Authz::is_allowed('file', 'list'));
        $this->assertFalse(Authz::is_allowed(array('file', 'unknown'), 'list'));
        $this->assertTrue(Authz::is_allowed(array('file', '/'), 'list'));
    }
    
    public function testGetResource()
    {
        Authz::set_resource_list($list = new Authz_ResourceList());
        $dir = $list->add_resource('directory');
        Authz::allow('directory', null, 'read');
        Authz::deny('directory', null, 'write');
        Authz::allow('directory', 'admin', 'write');
        Authz::allow('directory', 'user', 'list');

        $file = $list->add_resource('file', 'directory');
        Authz::allow('file', 'user', 'execute');
        Authz::deny('file', null, 'list');
        $root = $file->get_instance('/');
        
        $this->assertSame($file, Authz::get_resource('file'));
        $this->assertSame($dir, Authz::get_resource('directory'));
        
        $this->assertSame($root, Authz::get_resource(array('file', '/')));
        
        $this->assertFalse(Authz::get_resource(array('unknown', '/')));
        $this->assertFalse(Authz::get_resource('unknown'));
    }
}
?>
