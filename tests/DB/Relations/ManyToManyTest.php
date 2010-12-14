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


require_once __DIR__ .  '/../../path.inc.php';
require_once __DIR__ .  '/../SampleSchema.class.php';
require_once __DIR__ .  '/../SampleModels.inc.php';

use toolib\DB\Connection;

class Relations_ManyToManyTest extends PHPUnit_Framework_TestCase
{

	public static function setUpBeforeClass()
	{
		SampleSchema::build();
	}

	public static function tearDownAfterClass()
	{
		SampleSchema::destroy();
	}

	public function setUp()
	{
		SampleSchema::connect();
	}
	public function tearDown()
	{
		Connection::disconnect();
	}

	public function check_last_event($type, $name, $check_last)
	{
		$e = self::pop_event();
		$this->assertType('Event', $e);
		$this->assertEquals($e->type, $type);
		$this->assertEquals($e->name, $name);
		if ($check_last)
		$this->assertEquals(0, count(self::$events));
		return $e;
	}

	public function check_first_event($type, $name, $check_last)
	{
		$e = array_shift(self::$events);
		$this->assertType('Event', $e);
		$this->assertEquals($e->type, $type);
		$this->assertEquals($e->name, $name);
		if ($check_last)
		$this->assertEquals(0, count(self::$events));
		return $e;
	}

	public function testAll()
	{
		// User => Group
		$groups = User::open('user3')->groups->all();
		$this->assertEquals(count($groups), 2);		
		$this->assertEquals($groups[0]->groupname, 'group1');
		$this->assertEquals($groups[1]->groupname, 'group2');
		
		$groups = User::open('user1')->groups->all();
		$this->assertEquals(count($groups), 1);		
		$this->assertEquals($groups[0]->groupname, 'group1');

		// Group => User
		$users = Group::open('group1')->users->all();
		$this->assertEquals(count($users), 3);		
		$this->assertEquals($users[0]->username, 'user1');
		$this->assertEquals($users[1]->username, 'user2');
		$this->assertEquals($users[2]->username, 'user3');
	}
	
	public function testSubquery()
	{
		// Simple execute
		$users = Group::open('group1')->users->subquery()
			->execute();
		$this->assertEquals(count($users), 3);
		$this->assertEquals($users[0]->username, 'user1');
		$this->assertEquals($users[1]->username, 'user2');
		$this->assertEquals($users[2]->username, 'user3');
		
		// Where with like
		$users = Group::open('group1')->users->subquery()
			->where('l.groupname like ?')
			->execute('g%');
		$this->assertEquals(count($users), 3);
		$this->assertEquals($users[0]->username, 'user1');
		$this->assertEquals($users[1]->username, 'user2');
		$this->assertEquals($users[2]->username, 'user3');
		
		// Order by (Reverse order)
		$users = Group::open('group1')->users->subquery()
			->orderBy('username', 'DESC')
			->execute();
		$this->assertEquals(count($users), 3);
		$this->assertEquals($users[0]->username, 'user3');
		$this->assertEquals($users[1]->username, 'user2');
		$this->assertEquals($users[2]->username, 'user1');				
		
		// Limit result
		$users = Group::open('group1')->users->subquery()
			->limit(1)
			->orderBy('username', 'DESC')
			->execute();
		$this->assertEquals(count($users), 1);
		$this->assertEquals($users[0]->username, 'user3');		
	}

	public function testAddMethod()
	{
		// Add one new user in group1
		$user5 = User::open('user5');
		$this->assertType('Group_Members', $gm = Group::open('group1')->users->add($user5));
		
		// Check membership
		$users = Group::open('group1')->users->subquery()
			->execute();
		$this->assertEquals(count($users), 4);
		$this->assertEquals($users[0]->username, 'user1');
		$this->assertEquals($users[1]->username, 'user2');
		$this->assertEquals($users[2]->username, 'user3');
		$this->assertEquals($users[3]->username, 'user5');
		
		// Add an already existing (An error will be popped)		
		@$this->assertFalse($gm = Group::open('group1')->users->add($user5));
		
		// Check membership
		$users = Group::open('group1')->users->subquery()
			->execute();
		$this->assertEquals(count($users), 4);
		$this->assertEquals($users[0]->username, 'user1');
		$this->assertEquals($users[1]->username, 'user2');
		$this->assertEquals($users[2]->username, 'user3');
		$this->assertEquals($users[3]->username, 'user5');

		// Recreate Database
		SampleSchema::destroy();
		SampleSchema::build();		
	}

	public function testRemoveMethod()
	{
		// Remove a record froum group1
		$user1 = User::open('user1');
		$this->assertTrue(Group::open('group1')->users->remove($user1));
		
		// Check membership
		$users = Group::open('group1')->users->subquery()
			->execute();
		$this->assertEquals(count($users), 2);		
		$this->assertEquals($users[0]->username, 'user2');
		$this->assertEquals($users[1]->username, 'user3');
		
		
		// Remove a non- existing (An error will be popped)
		$this->assertFalse(Group::open('group1')->users->remove($user1));
		
		// Check membership
		$users = Group::open('group1')->users->subquery()
			->execute();
		$this->assertEquals(count($users), 2);		
		$this->assertEquals($users[0]->username, 'user2');
		$this->assertEquals($users[1]->username, 'user3');

		// Recreate Database
		SampleSchema::destroy();
		SampleSchema::build();		
	}
}
