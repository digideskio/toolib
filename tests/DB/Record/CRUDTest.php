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
require_once dirname(__FILE__) .  '/../../path.inc.php';
require_once dirname(__FILE__) .  '/../SampleSchema.class.php';
require_once dirname(__FILE__) .  '/../SampleModels.inc.php';

class Record_CRUDTest extends PHPUnit_Framework_TestCase
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
		DB_Conn::disconnect();
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

	public function testCount()
	{
		// Count single primary-key
		$total = User::count();
		$this->assertEquals($total, 7);

		// Open all with multi primary-key
		$total = Group_Members::count();
		$this->assertEquals($total, 8);
	}

	public function testOpenPrimaryKey()
	{
		// Open non-existing user
		$u = User::open('non-existing');
		$this->assertFalse($u);

		// Open existing user
		$u = User::open('admin');
		$this->assertType('User', $u);

		// Open FALSE record with two-field primary key
		$m = Group_members::open('wrong');
		$this->assertFalse($m);

		// Open record with two-field primary key
		$m = Group_members::open(array('username' => 'user1', 'groupname' => 'group1'));
		$this->assertType('Group_Members', $m);
	}

	public function testOpenAll()
	{
		// Open all with single primary-key
		$users = User::open_all();
		$this->assertType('array', $users);
		$this->assertEquals(count($users), 7);
		foreach($users as $u)
		$this->assertType('User', $u);

		// Open all with multi primary-key
		$gms = Group_Members::open_all();
		$this->assertType('array', $gms);
		$this->assertEquals(count($gms), 8);
		foreach($gms as $gm)
		$this->assertType('Group_Members', $gm);
	}

	public function testOpenQuery()
	{
		// Open query with single primary-key
		$mq = User::open_query()
		->limit(4);
		$this->assertType('DB_ModelQuery',  $mq);
		$users = $mq->execute();
		$this->assertType('array', $users);
		$this->assertEquals(count($users), 4);
		foreach($users as $u)
		$this->assertType('User', $u);

		// Open all with multi primary-key
		$mq = Group_Members::open_query()
		->limit(3);
		$gms = $mq->execute();
		$this->assertType('array', $gms);
		$this->assertEquals(count($gms), 3);
		foreach($gms as $gm)
		$this->assertType('Group_Members', $gm);

		// Open query with parameters on single pk
		$mq = User::open_query()
		->where('username like ?');
		$this->assertType('DB_ModelQuery',  $mq);
		$users = $mq->execute('user%');
		$this->assertType('array', $users);
		$this->assertEquals(count($users), 6);
		foreach($users as $u)
		$this->assertType('User', $u);

		// Open query with parameters on multi pk
		$mq = Group_Members::open_query()
		->where('username like ?');
		$gms = $mq->execute('user%');
		$this->assertType('array', $gms);
		$this->assertEquals(count($gms), 8);
		foreach($gms as $gm)
		$this->assertType('Group_Members', $gm);
	}

	public function testOpenRawQuery()
	{
		// Raw query with single primary-key
		$mq = User::raw_query()
		->select(User::model()->fields())
		->limit(4);
		$this->assertType('DB_ModelQuery',  $mq);
		$users = $mq->execute();
		$this->assertType('array', $users);
		$this->assertEquals(count($users), 4);
		foreach($users as $u)
		{
			$this->assertType('array', $u);
			$this->assertEquals($u['enabled'], '1');
		}

		// Open all with multi primary-key
		$mq = Group_Members::raw_query()
		->select(Group_Members::model()->fields())
		->limit(3);
		$gms = $mq->execute();
		$this->assertType('array', $gms);
		$this->assertEquals(count($gms), 3);
		foreach($gms as $gm)
		{
			$this->assertType('array', $gm);
			$this->assertType('string', $gm['username']);
			$this->assertType('string', $gm['groupname']);
		}


		// Open query with parameters on single pk
		$mq = User::raw_query()
		->select(User::model()->fields())
		->where('username like ?');
		$this->assertType('DB_ModelQuery',  $mq);
		$users = $mq->execute('user%');
		$this->assertType('array', $users);
		$this->assertEquals(count($users), 6);
		foreach($users as $u)
		{
			$this->assertType('array', $u);
			$this->assertEquals($u['enabled'], '1');
		}

		// Open query with parameters on multi pk
		$mq = Group_Members::raw_query()
		->select(Group_Members::model()->fields())
		->where('username like ?');
		$gms = $mq->execute('user%');
		$this->assertType('array', $gms);
		$this->assertEquals(count($gms), 8);
		foreach($gms as $gm)
		{
			$this->assertType('array', $gm);
			$this->assertType('string', $gm['username']);
			$this->assertType('string', $gm['groupname']);
		}
	}

	public function testDeleteQuery()
	{
		// Delete single pk record
		$u = User::open('user1');
		$this->assertType('User',  $u);
		$this->assertTrue($u->delete());

		// Re delete must fail
		$this->assertFalse($u->delete());

		// Re-open deleted user
		$u = User::open('user1');
		$this->assertFalse($u);

		// Delete multi pk record
		$gm = Group_members::open(array('username' => 'user1', 'groupname' => 'group1'));
		$this->assertType('Group_Members', $gm);
		$this->assertTrue($gm->delete());

		// Re-delete must fail
		$this->assertFalse($gm->delete());

		// Re-open must fail
		// Delete multi pk record
		$gm = Group_members::open(array('username' => 'user1', 'groupname' => 'group1'));
		$this->assertFalse($gm);

		// Recreate Database
		SampleSchema::destroy();
		SampleSchema::build();
	}

	public function testUpdateSinglePK()
	{
		// Open single pk record
		$u = User::open('user1');
		$this->assertType('User',  $u);
		$this->assertEquals($u->username, 'user1');
		$this->assertEquals($u->enabled, 1);

		// Empty save must fail
		$this->assertFalse($u->save());

		// Change and save
		$u->enabled = 0;
		$this->assertEquals($u->enabled, 0);
		$this->assertTrue($u->save());
		$this->assertEquals($u->enabled, 0);

		// Empty save must fail
		$this->assertFalse($u->save());

		// Re open and validate data
		$u = User::open('user1');
		$this->assertType('User',  $u);
		$this->assertEquals($u->username, 'user1');
		$this->assertEquals($u->enabled, 0);

		// Update pk
		$u->username = 'user-new';
		$this->assertEquals($u->username, 'user-new');
		$this->assertTrue($u->save());
		$this->assertEquals($u->username, 'user-new');

		// Create a new record with old primary key
		$data = array(
            'username' => 'user1',
            'password' => 'test',
            'enabled' => 1);
		$u2 = User::create($data);
		$this->assertType('User', $u2);

		// Trying to resave old
		$this->assertFalse($u->save());

		// Recreate Database
		SampleSchema::destroy();
		SampleSchema::build();
	}

	public function testUpdateMultiPK()
	{
		// Open multi pk record
		$gm = Group_Members::open(array('username' => 'user3', 'groupname' => 'group1'));
		$this->assertType('Group_Members',  $gm);
		$this->assertEquals($gm->username, 'user3');
		$this->assertEquals($gm->groupname, 'group1');

		// Empty save must fail
		$this->assertFalse($gm->save());

		// Change and save
		$gm->groupname = 'group4';
		$this->assertEquals($gm->groupname, 'group4');
		$this->assertTrue($gm->save());
		$this->assertEquals($gm->groupname, 'group4');

		// Create a new record with old primary key
		$data = array(
            'username' => 'user3',
            'groupname' => 'group1');
		$gm2 = Group_Members::create($data);
		$this->assertType('Group_Members', $gm2);

		// Empty save must fail
		$this->assertFalse($gm->save());

		// Re open and validate data
		$gm = Group_Members::open(array('username' => 'user3', 'groupname' => 'group4'));
		$this->assertType('Group_Members',  $gm);
		$this->assertEquals($gm->username, 'user3');
		$this->assertEquals($gm->groupname, 'group4');

		// Recreate Database
		SampleSchema::destroy();
		SampleSchema::build();
	}

	public function testCreate()
	{
		// Create single pk-ai record
		$f = Forum::create(array('title' => 'my title'));
		$this->assertType('Forum',  $f);
		$this->assertEquals($f->title, 'my title');
		$this->assertType('integer', $f->id);

		// Open created
		$f2 = Forum::open($f->id);
		$this->assertType('Forum',  $f2);
		$this->assertEquals($f2->title, 'my title');
		$this->assertEquals($f2->id, $f->id);

		// Create single pk-ai with user defined pk
		$f = Forum::create(array('id' => '55', 'title' => 'my title'));
		$this->assertType('Forum',  $f);
		$this->assertEquals($f->title, 'my title');
		$this->assertType('integer', $f->id);
		$this->assertEquals(55, $f->id);

		// Open created
		$f2 = Forum::open($f->id);
		$this->assertType('Forum',  $f2);
		$this->assertEquals($f2->title, 'my title');
		$this->assertEquals($f2->id, 55);

		// Create with default values
		$f = Forum::create();
		$this->assertType('Forum',  $f);
		$this->assertEquals($f->title, 'noname');
		$this->assertType('integer', $f->id);

		// Open created
		$f2 = Forum::open($f->id);
		$this->assertType('Forum',  $f2);
		$this->assertEquals($f2->title, 'noname');
		$this->assertEquals($f2->id, $f->id);

		// Create multi pk record
		$gm = Group_Members::create(array('username' => 'user3' , 'groupname' => 'group4'));
		$this->assertType('Group_Members',  $gm);
		$this->assertEquals($gm->username, 'user3');
		$this->assertEquals($gm->groupname, 'group4');

		// Open record
		$gm = Group_Members::open(array('username' => 'user3' , 'groupname' => 'group4'));
		$this->assertType('Group_Members',  $gm);
		$this->assertEquals($gm->username, 'user3');
		$this->assertEquals($gm->groupname, 'group4');

		// Creating with the same pk must fail
		$gm = @Group_Members::create(array('username' => 'user3' , 'groupname' => 'group4'));
		$this->assertFalse($gm);

		// Recreate Database
		SampleSchema::destroy();
		SampleSchema::build();
	}
}
?>
