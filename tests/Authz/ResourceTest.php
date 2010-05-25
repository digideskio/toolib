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

class Authz_ResourceTest extends PHPUnit_Framework_TestCase
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
    
    public function testConstrutc()
    {

        $dir = new Authz_Resource('directory');
        
        $this->assertTrue($dir->get_acl()->is_empty());
        $this->assertEquals($dir->get_name(), 'directory');
        $this->assertFalse($dir->has_parent());
        $this->assertNull($dir->get_parent());

        $file = new Authz_Resource('file', $dir);
        $this->assertTrue($file->get_acl()->is_empty());
        $this->assertEquals($file->get_name(), 'file');
        $this->assertTrue($file->has_parent());
        $this->assertSame($file->get_parent(), $dir);
    }
    
    public function dataEffectiveAce()
    {
        
        $roles = $this->roleFeeder();
        
        $dir = new Authz_Resource('directory');
        $dir->get_acl()->allow(null, 'read');
        $dir->get_acl()->deny(null, 'write');
        $dir->get_acl()->deny(null, 'delete');
        $dir->get_acl()->allow('@fs-admin', 'write');
        
        $file = new Authz_Resource('file', $dir);
        $file->get_acl()->allow('@fs-admin', 'delete');
        
        return array(
            // roles, $resource, $role, $action, $ace, $depth
            array($roles, $dir, null, 'unknown-action', null, null),
            array($roles, $dir, 'unknown', 'unknown-action', null, null),
            array($roles, $dir, '@user', 'unknown-action', null, null),
            array($roles, $dir, '@user', 'read', true, 500),
            array($roles, $dir, '@logger', 'read', true, 500),
            array($roles, $dir, '@logger', 'write', false, 500),
            array($roles, $dir, '@logger', 'delete', false, 500),
            array($roles, $dir, '@fs-admin', 'delete', false, 500),
            array($roles, $dir, '@admin', 'delete', false, 500),
            array($roles, $dir, '@fs-admin', 'write', true, 0),
            array($roles, $dir, '@admin', 'write', true, 1),
            
            array($roles, $file, null, 'unknown-action', null, null),
            array($roles, $file, 'unknown', 'unknown-action', null, null),
            array($roles, $file, '@user', 'unknown-action', null, null),
            array($roles, $file, '@user', 'read', true, 10500),
            array($roles, $file, '@logger', 'read', true, 10500),
            array($roles, $file, '@logger', 'write', false, 10500),
            array($roles, $file, '@logger', 'delete', false, 10500),
            array($roles, $file, '@fs-admin', 'delete', true, 0),
            array($roles, $file, '@admin', 'delete', true, 1),
            array($roles, $file, '@fs-admin', 'write', true, 10000),
            array($roles, $file, '@admin', 'write', true, 10001),
        );
    }
    
    /**
     * @dataProvider dataEffectiveAce
     */
    public function testEffectiveAce($roles, $resource, $role, $action, $expected_ace, $expected_depth)
    {
    
        $depth = null;
        $ace = $resource->effective_ace($role, $action, $roles, $depth);
        
        if ($expected_ace === null)
        {
            $this->assertNull($ace);
        }
        else
        {
            $this->assertNotNull($ace);
            $this->assertEquals($ace->is_allowed(), $expected_ace);
        }
        $this->assertEquals($expected_depth, $depth);
    }

  
}
?>
