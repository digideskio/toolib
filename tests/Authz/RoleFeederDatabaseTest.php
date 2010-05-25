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
require_once dirname(__FILE__) .  '/SampleSchema.class.php';

class Authz_RoleFeederDatabaseTest extends PHPUnit_Framework_TestCase
{

    public static function setUpBeforeClass()
    {
        Authz_SampleSchema::build();
        Authz_SampleSchema::connect();
    }

    public static function tearDownAfterClass()
    {
        Authz_SampleSchema::destroy();
    }


    public function testNoDependency()
    {
        $list = new Authz_Role_FeederDatabase(Users::open_query()->where('username = ?'), 'username');
        
        
        $this->assertFalse($list->has_role('unknown-user'));
        $this->assertFalse($list->has_role('unknown-user'));
        
        $this->assertTrue($list->has_role('user1'));
        $this->assertTrue($list->has_role('user2'));
        $this->assertTrue($list->has_role('user3'));
        $this->assertTrue($list->has_role('user4'));
        $this->assertTrue($list->has_role('user5'));
        $this->assertTrue($list->has_role('user6'));
        
        $this->assertFalse($list->has_role('user7'));
        $this->assertFalse($list->get_role('user7'));
        
        $user1 = $list->get_role('user1');
        $user2 = $list->get_role('user2');
        $this->assertType('Authz_Role_Database', $user1);
        $this->assertEquals($user1->get_name(), 'user1');
        $this->assertType('Authz_Role_Database', $user2);
        $this->assertEquals($user2->get_name(), 'user2');
        
        $this->assertFalse($user1->has_parent('test'));
        $this->assertFalse($user1->has_parent('wrong'));
        $this->assertEquals($user1->get_parents(), array());
    }

    public function testDependency()
    {
        $users_query = Users::open_query()->where('username = ?');
        $group_query = Membership::open_query()->where('username = ?');
        
        $list = new Authz_Role_FeederDatabase($users_query, 'username', $group_query, 'groupname');
        
        
        $this->assertFalse($list->has_role('unknown-user'));
        $this->assertFalse($list->has_role('unknown-user'));
        
        $this->assertTrue($list->has_role('user1'));
        $this->assertTrue($list->has_role('user2'));
        $this->assertTrue($list->has_role('user3'));
        $this->assertTrue($list->has_role('user4'));
        $this->assertTrue($list->has_role('user5'));
        $this->assertTrue($list->has_role('user6'));
        
        $this->assertFalse($list->has_role('user7'));
        $this->assertFalse($list->get_role('user7'));
        
        $user1 = $list->get_role('user1');
        $user5 = $list->get_role('user5');
        $this->assertType('Authz_Role_Database', $user1);
        $this->assertEquals($user1->get_name(), 'user1');
        $this->assertType('Authz_Role_Database', $user5);
        $this->assertEquals($user5->get_name(), 'user5');
        
        $this->assertFalse($user1->has_parent('wrong'));
        $this->assertTrue($user1->has_parent('group12'));
        $this->assertFalse($user5->has_parent('wrong'));
        $this->assertTrue($user5->has_parent('group56'));
        $this->assertFalse($user5->has_parent('group12'));
        
        $this->assertEquals($user1->get_parents(), array());
    }
    
}
?>
