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


use toolib\Authz;
use toolib\Authz\ResourceList;
require_once __DIR__ .  '/../path.inc.php';


class Authz_ResourceListTest extends PHPUnit_Framework_TestCase
{
    public function roleFeeder()
    {
        $roles = new Instance\RoleFeeder();
        $roles->addRole(new Role('@game'));
        $roles->addRole(new Role('@video'));
        $roles->addRole(new Role('@user', array('@game', '@video')));
        $roles->addRole(new Role('@web-user'));
        $roles->addRole(new Role('@web-admin', '@web-user'));
        $roles->addRole(new Role('@fs-admin'));
        $roles->addRole(new Role('@logger'));
        $roles->addRole(new Role('@admin', array('@user', '@web-admin', '@fs-admin')));
        return $roles;
    }
    
    public function testGeneral()
    {
        $list = new ResourceList();
        
        $this->assertFalse($list->hasResource('test'));
        $this->assertFalse($list->getResource('test'));

        $list->addResource('directory');
        $this->assertTrue($list->hasResource('directory'));
        $this->assertInstanceOf('toolib\Authz\Resource', $list->getResource('directory'));

        $list->addResource('file', 'directory');
        $this->assertTrue($list->hasResource('directory'));
        $this->assertInstanceOf('toolib\Authz\Resource', $list->getResource('directory'));
        $this->assertTrue($list->hasResource('file'));
        $this->assertInstanceOf('toolib\Authz\Resource', $list->getResource('file'));
    }
    
    public function testRemoveResource()
    {
        $list = new ResourceList();

        // Add and readd a resource
        $dir = $list->addResource('directory');
        $this->assertTrue($list->hasResource('directory'));
        $this->assertFalse($list->hasResource('file'));
        $this->assertTrue($list->removeResource('directory'));
        $this->assertFalse($list->hasResource('directory'));
        $this->assertFalse($list->removeResource('directory'));
        $this->assertFalse($list->hasResource('directory'));
        $dir = $list->addResource('directory');
        $file = $list->addResource('file', 'directory');
        $this->assertTrue($list->hasResource('directory'));
        $this->assertTrue($list->hasResource('file'));
        $this->assertTrue($list->removeResource('file'));
        $this->assertTrue($list->removeResource('directory'));
    }
    
    /**
     * @expectedException RuntimeException
     */
    public function testRemoveResourceException()
    {
        $list = new ResourceList();
        $dir = $list->addResource('directory');
        $file = $list->addResource('file', 'directory');
        $list->removeResource('directory');
    }
    
    public function testGetResource()
    {
        $list = new ResourceList();
        $dir = $list->addResource('directory');
        $file = $list->addResource('file', 'directory');
     
        $this->assertFalse($list->getResource('unknown'));
        $this->assertFalse($list->getResource('unknown', 'unknown id'));
        
        // General inheritance
        $this->assertSame($list->getResource('directory'), $dir);
        $this->assertSame($list->getResource('file'), $file);
        $this->assertSame($list->getResource('file')->getParent(), $dir);
        
        // Instances
        $this->assertInstanceOf('toolib\Authz\Resource', $list->getResource('directory', 'test'), $dir);
        $this->assertSame($list->getResource('directory', 'test'), $list->getResource('directory', 'test'));
        $this->assertSame($list->getResource('directory', 'test')->getParent(), $dir);
        $this->assertSame($list->getResource('file', 'test')->getParent(), $file);
        $this->assertSame($list->getResource('file', 'test')->getParent()->getParent(), $dir);
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testSameResourceException()
    {
        $list = new ResourceList($this->roleFeeder());
        $list->addResource('dir');
        $list->addResource('dir', 'file');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testBrokenDependencyException()
    {
        $list = new ResourceList($this->roleFeeder());
        $list->addResource('dir', 'file');
    }
    
    public function testSerialize()
    {
        $list = new ResourceList();
        $dir = $list->addResource('directory');
        $dir->getAcl()->allow(null, 'read');
        $dir->getAcl()->deny(null, 'write');
        $dir->getAcl()->deny(null, 'delete');
        $dir->getAcl()->allow('@fs-admin', 'write');
        
        $file = $list->addResource('file', 'directory');
        $file->getAcl()->allow('@fs-admin', 'delete');
        
        $list2 = unserialize(serialize($list));
        
        $this->assertNotSame($list, $list2);
        $this->assertSame($list->getResource('directory'), $list->getResource('file')->getParent());

    }
}
