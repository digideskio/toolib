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


use \toolib\Authz\Role;
use \toolib\Authz as AZ;
use \toolib\Authz;

require_once __DIR__ .  '/../path.inc.php';

class Authz_AuthzTest extends PHPUnit_Framework_TestCase
{
    public function roleFeeder()
    {
        $roles = new AZ\Role\FeederInstance();
        $roles->addRole('@game');
        $roles->addRole('@video');
        $roles->addRole('@user', array('@game', '@video'));
        $roles->addRole('@web-user');
        $roles->addRole('@web-admin', '@web-user');
        $roles->addRole('@fs-admin');
        $roles->addRole('@logger');
        $roles->addRole('@admin', array('@user', '@web-admin', '@fs-admin'));
        return $roles;
    }
    
    public function testSetGet()
    {
        $list = Authz::getResourceList();
        $this->assertType('toolib\Authz\ResourceList', $list);
        $list2 = new AZ\ResourceList();
        Authz::setResourceList($list2);
        $this->assertSame(Authz::getResourceList(), $list2);
        $this->assertNotSame($list, $list2);

        
        $roles1 = $this->roleFeeder();
        $roles2 = $this->roleFeeder();
        $this->assertNotSame($roles1, $roles2);
        
        Authz::setRoleFeeder($roles1);
        $this->assertSame(Authz::getRoleFeeder(), $roles1);

        Authz::setRoleFeeder($roles2);
        $this->assertSame(Authz::getRoleFeeder(), $roles2);
    }
    
    /**
     * @depends testSetGet
     */
    public function testIsRoleAllowed()
    {
        Authz::setResourceList($list = new AZ\ResourceList());
        Authz::setRoleFeeder($this->roleFeeder());
        $dir = $list->addResource('directory');
        Authz::allow('directory', null, 'read');
        Authz::deny('directory', null, 'write');
        Authz::allow('directory', 'admin', 'write');
        Authz::allow('directory', 'user', 'list');

        $file = $list->addResource('file', 'directory');
        Authz::allow('file', 'user', 'execute');
        Authz::deny('file', null, 'list');
        
        Authz::allow(array('file', '/') , null, 'list');

        $this->assertFalse(Authz::isRoleAllowedTo(null, 'directory', 'unknown'));
        $this->assertTrue(Authz::isRoleAllowedTo(null, 'directory', 'read'));
        $this->assertFalse(Authz::isRoleAllowedTo(null, 'directory', 'write'));
        $this->assertFalse(Authz::isRoleAllowedTo(null, 'directory', 'execute'));
        $this->assertFalse(Authz::isRoleAllowedTo(null, 'directory', 'list'));
        $this->assertTrue(Authz::isRoleAllowedTo('admin', 'directory', 'write'));
        $this->assertFalse(Authz::isRoleAllowedTo('admin', 'directory', 'execute'));
        $this->assertFalse(Authz::isRoleAllowedTo('admin', 'directory', 'list'));
        $this->assertFalse(Authz::isRoleAllowedTo('user', 'directory', 'execute'));
        $this->assertTrue(Authz::isRoleAllowedTo('user', 'directory', 'list'));
        
        $this->assertFalse(Authz::isRoleAllowedTo(null, 'file', 'unknown'));
        $this->assertTrue(Authz::isRoleAllowedTo(null, 'file', 'read'));
        $this->assertFalse(Authz::isRoleAllowedTo(null, 'file', 'write'));
        $this->assertFalse(Authz::isRoleAllowedTo(null, 'file', 'execute'));
        $this->assertFalse(Authz::isRoleAllowedTo(null, 'file', 'list'));
        $this->assertTrue(Authz::isRoleAllowedTo('admin', 'file', 'write'));
        $this->assertFalse(Authz::isRoleAllowedTo('admin', 'file', 'execute'));
        $this->assertFalse(Authz::isRoleAllowedTo('admin', 'file', 'list'));
        $this->assertTrue(Authz::isRoleAllowedTo('user', 'file', 'execute'));
        $this->assertFalse(Authz::isRoleAllowedTo('user', 'file', 'list'));
        $this->assertFalse(Authz::isRoleAllowedTo('user', array('file', 'unknown'), 'list'));
        $this->assertTrue(Authz::isRoleAllowedTo('user', array('file', '/'), 'list'));
    }
    
    public function testIsAllowed()
    {
        Authz::setResourceList($list = new AZ\ResourceList());
        Authz::setRoleFeeder($this->roleFeeder());
        $dir = $list->addResource('directory');
        Authz::allow('directory', null, 'read');
        Authz::deny('directory', null, 'write');
        Authz::allow('directory', 'admin', 'write');
        Authz::allow('directory', 'user', 'list');

        $file = $list->addResource('file', 'directory');
        Authz::allow('file', 'user', 'execute');
        Authz::deny('file', null, 'list');
        
        Authz::allow(array('file', '/') , null, 'list');

        Authz::setCurrentRoleFunc(create_function('', 'return null;'));
        $this->assertFalse(Authz::isAllowed('directory', 'unknown'));
        $this->assertTrue(Authz::isAllowed('directory', 'read'));
        $this->assertFalse(Authz::isAllowed('directory', 'write'));
        $this->assertFalse(Authz::isAllowed('directory', 'execute'));
        $this->assertFalse(Authz::isAllowed('directory', 'list'));
        Authz::setCurrentRoleFunc(create_function('', 'return "admin";'));
        $this->assertTrue(Authz::isAllowed('directory', 'write'));
        $this->assertFalse(Authz::isAllowed('directory', 'execute'));
        $this->assertFalse(Authz::isAllowed('directory', 'list'));
        Authz::setCurrentRoleFunc(create_function('', 'return "user";'));
        $this->assertFalse(Authz::isAllowed('directory', 'execute'));
        $this->assertTrue(Authz::isAllowed('directory', 'list'));


        Authz::setCurrentRoleFunc(create_function('', 'return null;'));
        $this->assertFalse(Authz::isAllowed('file', 'unknown'));
        $this->assertTrue(Authz::isAllowed('file', 'read'));
        $this->assertFalse(Authz::isAllowed('file', 'write'));
        $this->assertFalse(Authz::isAllowed('file', 'execute'));
        $this->assertFalse(Authz::isAllowed('file', 'list'));
        Authz::setCurrentRoleFunc(create_function('', 'return "admin";'));
        $this->assertTrue(Authz::isAllowed('file', 'write'));
        $this->assertFalse(Authz::isAllowed('file', 'execute'));
        $this->assertFalse(Authz::isAllowed('file', 'list'));
        Authz::setCurrentRoleFunc(create_function('', 'return "user";'));
        $this->assertTrue(Authz::isAllowed('file', 'execute'));
        $this->assertFalse(Authz::isAllowed('file', 'list'));
        $this->assertFalse(Authz::isAllowed(array('file', 'unknown'), 'list'));
        $this->assertTrue(Authz::isAllowed(array('file', '/'), 'list'));
    }
    
    public function testGetResource()
    {
        Authz::setResourceList($list = new AZ\ResourceList());
        $dir = $list->addResource('directory');
        Authz::allow('directory', null, 'read');
        Authz::deny('directory', null, 'write');
        Authz::allow('directory', 'admin', 'write');
        Authz::allow('directory', 'user', 'list');

        $file = $list->addResource('file', 'directory');
        Authz::allow('file', 'user', 'execute');
        Authz::deny('file', null, 'list');
        $root = $file->getInstance('/');
        
        $this->assertSame($file, Authz::getResource('file'));
        $this->assertSame($dir, Authz::getResource('directory'));
        
        $this->assertSame($root, Authz::getResource(array('file', '/')));
        
        $this->assertFalse(Authz::getResource(array('unknown', '/')));
        $this->assertFalse(Authz::getResource('unknown'));
    }
}
