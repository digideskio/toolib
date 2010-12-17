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
use toolib\Authz\Role\FeederDatabase;

require_once __DIR__ .  '/../path.inc.php';
require_once __DIR__ .  '/SampleSchema.class.php';

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


    /**
     * @expectedException InvalidArgumentException
     */
    public function testMandatoryOptions1()
    {
        $list = new FeederDatabase(array(
            'role_query' => Users::openQuery()->where('username = ?')
        ));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testMandatoryOptions2()
    {
        $list = new FeederDatabase(array(
            'role_name_field' => 'username'
        ));
    }
    
    public function testNoDependency()
    {
        $list = new FeederDatabase(array(
            'role_query' => Users::openQuery()->where('username = ?'),
            'role_name_field' => 'username'
        ));
        
        
        $this->assertFalse($list->hasRole('unknown-user'));
        $this->assertFalse($list->hasRole('unknown-user'));
        
        $this->assertTrue($list->hasRole('user1'));
        $this->assertTrue($list->hasRole('user2'));
        $this->assertTrue($list->hasRole('user3'));
        $this->assertTrue($list->hasRole('user4'));
        $this->assertTrue($list->hasRole('user5'));
        $this->assertTrue($list->hasRole('user6'));
        
        $this->assertFalse($list->hasRole('user7'));
        $this->assertFalse($list->getRole('user7'));
        
        $user1 = $list->getRole('user1');
        $user2 = $list->getRole('user2');
        $this->assertType('toolib\Authz\Role\Database', $user1);
        $this->assertEquals($user1->getName(), 'user1');
        $this->assertType('toolib\Authz\Role\Database', $user2);
        $this->assertEquals($user2->getName(), 'user2');
        
        $this->assertFalse($user1->hasParent('test'));
        $this->assertFalse($user1->hasParent('wrong'));
        $this->assertEquals($user1->getParents(), array());
    }

    public function testDependency()
    {
        $users_query = Users::openQuery()->where('username = ?');
        $group_query = Membership::openQuery()->where('username = ?');
        
        $list = new FeederDatabase(array(
            'role_query' => $users_query,
            'role_name_field' => 'username',
            'parents_query' => $group_query,
            'parent_name_field' => 'groupname'));        
        
        $this->assertFalse($list->hasRole('unknown-user'));
        $this->assertFalse($list->hasRole('unknown-user'));
        
        $this->assertTrue($list->hasRole('user1'));
        $this->assertTrue($list->hasRole('user2'));
        $this->assertTrue($list->hasRole('user3'));
        $this->assertTrue($list->hasRole('user4'));
        $this->assertTrue($list->hasRole('user5'));
        $this->assertTrue($list->hasRole('user6'));
        
        $this->assertFalse($list->hasRole('user7'));
        $this->assertFalse($list->getRole('user7'));
        
        $user1 = $list->getRole('user1');
        $user4 = $list->getRole('user4');
        $user5 = $list->getRole('user5');
        $this->assertType('toolib\Authz\Role\Database', $user1);
        $this->assertEquals($user1->getName(), 'user1');
        $this->assertType('toolib\Authz\Role\Database', $user5);
        $this->assertEquals($user5->getName(), 'user5');
        $this->assertType('toolib\Authz\Role\Database', $user4);
        $this->assertEquals($user4->getName(), 'user4');
        
        $this->assertFalse($user1->hasParent('wrong'));
        $this->assertTrue($user1->hasParent('group13'));
        $this->assertFalse($user5->hasParent('wrong'));
        $this->assertTrue($user5->hasParent('group46'));
        $this->assertFalse($user5->hasParent('group12'));

        $this->assertType('array', $user5->getParents());
        $this->assertEquals(count($user5->getParents()), 1);
        $parents = $user5->getParents();
        $group46 = $parents['group46'];
        $this->assertEquals($group46->getName(), 'group46');
        $this->assertFalse($group46->hasParent('test'));
        $this->assertEquals($group46->getParents(), array());
        
        $this->assertType('array', $user4->getParents());
        $this->assertEquals(count($user4->getParents()), 2);
        $parents = $user4->getParents();
        $group34 = $parents['group34'];
        $group46 = $parents['group46'];
        $this->assertEquals($group34->getName(), 'group34');
        $this->assertFalse($group34->hasParent('test'));
        $this->assertEquals($group34->getParents(), array());
        $this->assertEquals($group46->getName(), 'group46');
        $this->assertFalse($group46->hasParent('test'));
        $this->assertEquals($group46->getParents(), array());
    }
    
    public function testDependencyFilter()
    {
        $users_query = Users::openQuery()->where('username = ?');
        $group_query = Membership::openQuery()->where('username = ?');
        
        $list = new FeederDatabase(array(
            'role_query' => $users_query,
            'role_name_field' => 'username',
            'parents_query' => $group_query,
            'parent_name_field' => 'groupname',
            'parent_name_filter_func' => create_function('$name', ' return "@" . $name; ')
        ));
        
        $this->assertFalse($list->hasRole('unknown-user'));
        $this->assertFalse($list->hasRole('unknown-user'));
        
        $this->assertTrue($list->hasRole('user1'));
        $this->assertTrue($list->hasRole('user2'));
        $this->assertTrue($list->hasRole('user3'));
        $this->assertTrue($list->hasRole('user4'));
        $this->assertTrue($list->hasRole('user5'));
        $this->assertTrue($list->hasRole('user6'));
        
        $this->assertFalse($list->hasRole('user7'));
        $this->assertFalse($list->getRole('user7'));
        
        $user1 = $list->getRole('user1');
        $user4 = $list->getRole('user4');
        $user5 = $list->getRole('user5');
        $this->assertType('toolib\Authz\Role\Database', $user1);
        $this->assertEquals($user1->getName(), 'user1');
        $this->assertType('toolib\Authz\Role\Database', $user5);
        $this->assertEquals($user5->getName(), 'user5');
        $this->assertType('toolib\Authz\Role\Database', $user4);
        $this->assertEquals($user4->getName(), 'user4');
        
        $this->assertFalse($user1->hasParent('wrong'));
        $this->assertTrue($user1->hasParent('@group13'));
        $this->assertFalse($user5->hasParent('wrong'));
        $this->assertTrue($user5->hasParent('@group46'));
        $this->assertFalse($user5->hasParent('@group12'));

        $this->assertType('array', $user5->getParents());
        $this->assertEquals(count($user5->getParents()), 1);
        $parents = $user5->getParents();
        $group46 = $parents['@group46'];
        $this->assertEquals($group46->getName(), '@group46');
        $this->assertFalse($group46->hasParent('test'));
        $this->assertEquals($group46->getParents(), array());
        
        $this->assertType('array', $user4->getParents());
        $this->assertEquals(count($user4->getParents()), 2);
        $parents = $user4->getParents();
        $group34 = $parents['@group34'];
        $group46 = $parents['@group46'];
        $this->assertEquals($group34->getName(), '@group34');
        $this->assertFalse($group34->hasParent('test'));
        $this->assertEquals($group34->getParents(), array());
        $this->assertEquals($group46->getName(), '@group46');
        $this->assertFalse($group46->hasParent('test'));
        $this->assertEquals($group46->getParents(), array());
    }
}
