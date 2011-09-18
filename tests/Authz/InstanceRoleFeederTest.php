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
use toolib\Authz\Instance\RoleFeeder;

require_once __DIR__ .  '/../path.inc.php';

class Authz_InstanceRoleFeederTest extends PHPUnit_Framework_TestCase
{
    public function testGeneral()
    {
        $list = new toolib\Authz\Instance\RoleFeeder();
        
        $this->assertFalse($list->hasRole('test'));
        $this->assertFalse($list->getRole('test'));

        $member_role = $list->addRole('member');
        $this->assertTrue($list->hasRole('member'));
        $this->assertEquals($list->getRole('member'), $member_role);
        
        $admin_role = $list->addRole('admin', 'member');
        $this->assertTrue($list->hasRole('admin'));
        $this->assertEquals($list->getRole('admin'), $admin_role);
        $this->assertTrue($list->hasRole('member'));
        $this->assertEquals($list->getRole('member'), $member_role);
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testSameRoleException()
    {
        $list = new toolib\Authz\Instance\RoleFeeder();
        $list->addRole('member');
        $list->addRole('member', array('test', 'test2'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testBrokenDependencyException()
    {
        $list = new toolib\Authz\Instance\RoleFeeder();
        $list->addRole('member', array('everyone'));
    }
}
