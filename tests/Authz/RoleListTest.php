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

class Authz_RoleListTest extends PHPUnit_Framework_TestCase
{
    public function testGeneral()
    {
        $list = new Authz_RoleList();
        
        $this->assertFalse($list->has_role('test'));
        $this->assertFalse($list->get_role('test'));

        $member_role = new Authz_Role('member');
        $list->add_role($member_role);
        $this->assertTrue($list->has_role('member'));
        $this->assertEquals($list->get_role('member'), $member_role);
        
        $admin_role = new Authz_Role('admin', 'member');
        $list->add_role($admin_role);
        $this->assertTrue($list->has_role('admin'));
        $this->assertEquals($list->get_role('admin'), $admin_role);
        $this->assertTrue($list->has_role('member'));
        $this->assertEquals($list->get_role('member'), $member_role);
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testSameRoleException()
    {
        $list = new Authz_RoleList();
        $list->add_role(new Authz_Role('member'));
        $list->add_role(new Authz_Role('member', array('test', 'test2')));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testBrokenDepndancyException()
    {
        $list = new Authz_RoleList();
        $list->add_role(new Authz_Role('member', array('everyone')));
    }
}
?>
