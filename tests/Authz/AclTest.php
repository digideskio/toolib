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

class Authz_AclTest extends PHPUnit_Framework_TestCase
{
    public function testGeneral()
    {
        $list = new Authz_RoleList();
        $list->add_role(new Authz_Role('@game'));
        $list->add_role(new Authz_Role('@video'));
        $list->add_role(new Authz_Role('@user', array('@game', '@video')));
        $list->add_role(new Authz_Role('@web-user'));
        $list->add_role(new Authz_Role('@web-admin', '@web-user'));
        $list->add_role(new Authz_Role('@fs-admin'));
        $list->add_role(new Authz_Role('@logger'));
        $list->add_role(new Authz_Role('@admin', array('@user', '@web-admin', '@fs-admin')));
        
        $acl = new Authz_ACL($list);
        $acl->allow(null, 'read');
        $acl->deny('@logger', 'read');
        
        $acl->deny('@user', 'write');
        $acl->allow('@fs-admin', 'write');
        
        $acl->deny(null, 'play');
        $acl->allow('@game', 'play');
        
        $this->assertFalse($acl->is_allowed(null, 'unknown-action'));
        
        // Read 
        $this->assertTrue($acl->is_allowed(null, 'read'));
        $this->assertTrue($acl->is_allowed('@user', 'read'));
        $this->assertTrue($acl->is_allowed('@admin', 'read'));
        $this->assertTrue($acl->is_allowed('@web-user', 'read'));
        $this->assertTrue($acl->is_allowed('@web-admin', 'read'));
        $this->assertTrue($acl->is_allowed('@fs-admin', 'read'));
        $this->assertTrue($acl->is_allowed('@game', 'read'));
        $this->assertTrue($acl->is_allowed('@video', 'read'));
        $this->assertFalse($acl->is_allowed('@logger', 'read'));
        
        // Write
        $this->assertFalse($acl->is_allowed(null, 'write'));
        $this->assertFalse($acl->is_allowed('@user', 'write'));
        $this->assertTrue($acl->is_allowed('@admin', 'write'));
        $this->assertFalse($acl->is_allowed('@web-user', 'write'));
        $this->assertFalse($acl->is_allowed('@web-admin', 'write'));
        $this->assertTrue($acl->is_allowed('@fs-admin', 'write'));
        $this->assertFalse($acl->is_allowed('@game', 'write'));
        $this->assertFalse($acl->is_allowed('@video', 'write'));
        
        // Play
        $this->assertFalse($acl->is_allowed(null, 'play'));
        $this->assertTrue($acl->is_allowed('@user', 'play'));
        $this->assertTrue($acl->is_allowed('@admin', 'play'));
        $this->assertFalse($acl->is_allowed('@web-user', 'play'));
        $this->assertFalse($acl->is_allowed('@web-admin', 'play'));
        $this->assertFalse($acl->is_allowed('@fs-admin', 'play'));
        $this->assertTrue($acl->is_allowed('@game', 'play'));
        $this->assertFalse($acl->is_allowed('@video', 'play'));
    }
}
?>
