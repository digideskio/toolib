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

class Record_EventsTest extends PHPUnit_Framework_TestCase
{
	public static $events = array();

	public static function pop_event()
	{
		return array_pop(self::$events);
	}

	public static function push_event($e)
	{
		// Close reference
		$d = clone $e;
		unset($d->filtered_value);
		$d->filtered_value = $e->filtered_value;
		array_push(self::$events, $d);
	}

	public static function setUpBeforeClass()
	{
		SampleSchema::build();

		Forum::events()->connect(
		NULL,
		array('Record_EventsTest', 'push_event')
		);

		Group_Members::events()->connect(
		NULL,
		array('Record_EventsTest', 'push_event')
		);
	}

	public static function tearDownAfterClass()
	{
		SampleSchema::destroy();
	}

	public function setUp()
	{
		SampleSchema::connect();
		self::$events = array();
	}

	public function tearDown()
	{
		$this->assertEquals(count(self::$events), 0);
		Connection::disconnect();
	}

	public function check_last_event($type, $name, $check_last)
	{
		$e = self::pop_event();
		$this->assertInstanceOf('toolib\Event', $e);
		$this->assertEquals($e->type, $type);
		$this->assertEquals($e->name, $name);
		if ($check_last)
		$this->assertEquals(0, count(self::$events));
		return $e;
	}

	public function check_first_event($type, $name, $check_last)
	{
		$e = array_shift(self::$events);
		$this->assertInstanceOf('toolib\Event', $e);
		$this->assertEquals($e->type, $type);
		$this->assertEquals($e->name, $name);
		if ($check_last)
		$this->assertEquals(0, count(self::$events));
		return $e;
	}

	public function testOpenEvents()
	{
		// Open() 1PK
		$res = Forum::open(1);
		// Pre-Open
		$e = self::check_first_event('filter', 'pre-open', false);
		$this->assertEquals($e->arguments['model'], 'Forum');
		$this->assertEquals($e->filtered_value, 1);
		// Post-Open
		$e = self::check_last_event('notify', 'post-open', true);
		$this->assertInternalType('array', $e->arguments['records']);
		$this->assertEquals(count($e->arguments['records']), 1);
		$this->assertInstanceOf('Forum', $e->arguments['records'][0]);
		$this->assertEquals($e->arguments['model'], 'Forum');

		// openAll() 1PK
		$res = Forum::openAll();
		// Post-Open
		$e = self::check_last_event('notify', 'post-open', true);
		$this->assertInternalType('array', $e->arguments['records']);
		$this->assertEquals(count($e->arguments['records']), 3);
		$this->assertEquals($e->arguments['model'], 'Forum');
		$this->assertEquals($e->arguments['records'], $res);
		$this->assertInstanceOf('Forum', $e->arguments['records'][0]);

		// Open() 2PK
		$res = Group_Members::open(array('username' => 'user1','groupname' => 'group1'));
		// Pre-Open
		$e = self::check_first_event('filter', 'pre-open', false);
		$this->assertEquals($e->arguments['model'], 'Group_Members');
		$this->assertEquals($e->filtered_value, array('username' => 'user1','groupname' => 'group1'));
		// Post-Open
		$e = self::check_last_event('notify', 'post-open', true);
		$this->assertInternalType('array', $e->arguments['records']);
		$this->assertEquals(count($e->arguments['records']), 1);
		$this->assertInstanceOf('Group_Members', $e->arguments['records'][0]);
		$this->assertEquals($e->arguments['model'], 'Group_Members');

		// openAll() 2PK
		$res = Group_Members::openAll();
		// Post-Open
		$e = self::check_last_event('notify', 'post-open', true);
		$this->assertInternalType('array', $e->arguments['records']);
		$this->assertEquals(count($e->arguments['records']), 8);
		$this->assertEquals($e->arguments['model'], 'Group_Members');
		$this->assertEquals($e->arguments['records'], $res);
		$this->assertInstanceOf('Group_Members', $e->arguments['records'][0]);

	}

	public function testCreateEvents()
	{
		// Create() 1PK
		$f = Forum::create(array('title' => 'test'));
		// Pre-Create
		$e = self::check_first_event('filter', 'pre-create', false);
		$this->assertEquals($e->arguments['model'], 'Forum');
		$this->assertEquals($e->filtered_value, array('title' => 'test'));
		// Post-Create
		$e = self::check_last_event('notify', 'post-create', true);
		$this->assertInstanceOf('Forum', $e->arguments['record']);
		$this->assertEquals($e->arguments['record'], $f);

		// Create() 2PK
		$gm = Group_Members::create(array('username' => 'user5', 'groupname' => 'group1'));
		// Pre-Create
		$e = self::check_first_event('filter', 'pre-create', false);
		$this->assertEquals($e->arguments['model'], 'Group_Members');
		$this->assertEquals($e->filtered_value, array('username' => 'user5', 'groupname' => 'group1'));
		// Post-Create
		$e = self::check_last_event('notify', 'post-create', true);
		$this->assertInstanceOf('Group_Members', $e->arguments['record']);
		$this->assertEquals($e->arguments['record'], $gm);

		// Recreate Database
		SampleSchema::destroy();
		SampleSchema::build();
	}

	public function testUpdateEvents()
	{
		// Update() 1PK
		$f = Forum::open(1);
		self::check_first_event('filter', 'pre-open', false);
		self::check_first_event('notify', 'post-open', true);
		$f->update();
		$this->assertEquals(self::$events, array());
		$f->title = 'new title';
		$res = $f->update();
		// Pre-Update
		$e = self::check_first_event('filter', 'pre-update', false);
		$this->assertEquals($e->arguments['model'], 'Forum');
		$this->assertEquals($e->arguments['record'], $f);
		$this->assertEquals($e->arguments['old_values'], array('title' => 'The first'));
		$this->assertEquals($e->filtered_value, false);
		// Post-Update
		$e = self::check_last_event('notify', 'post-update', true);
		$this->assertInstanceOf('Forum', $e->arguments['record']);
		$this->assertEquals($e->arguments['record'], $f);
		$this->assertTrue($res);

		// Update() 2PK
		$gm = Group_Members::open(array('username' => 'user1','groupname' => 'group1'));
		self::check_first_event('filter', 'pre-open', false);
		self::check_first_event('notify', 'post-open', true);
		$gm->update();
		$this->assertEquals(self::$events, array());
		$gm->groupname = 'group3';
		$res = $gm->update();
		// Pre-update
		$e = self::check_first_event('filter', 'pre-update', false);
		$this->assertEquals($e->arguments['model'], 'Group_Members');
		$this->assertEquals($e->arguments['record'], $gm);
		$this->assertEquals($e->arguments['old_values'], array('groupname' => 'group1'));
		$this->assertEquals($e->filtered_value, false);
		// Post-Update
		$e = self::check_last_event('notify', 'post-update', true);
		$this->assertInstanceOf('Group_Members', $e->arguments['record']);
		$this->assertEquals($e->arguments['record'], $gm);
		$this->assertTrue($res);

		// Recreate Database
		SampleSchema::destroy();
		SampleSchema::build();
	}

	public function testDeleteEvents()
	{
		// delete() 1PK
		$f = Forum::open(1);
		self::check_first_event('filter', 'pre-open', false);
		self::check_first_event('notify', 'post-open', true);
		$res = $f->delete();
		// Pre-Delete
		$e = self::check_first_event('filter', 'pre-delete', false);
		$this->assertEquals($e->arguments['model'], 'Forum');
		$this->assertEquals($e->arguments['record'], $f);
		$this->assertEquals($e->filtered_value, false);
		// Post-delete
		$e = self::check_last_event('notify', 'post-delete', true);
		$this->assertInstanceOf('Forum', $e->arguments['record']);
		$this->assertEquals($e->arguments['record'], $f);
		$this->assertTrue($res);

		// delete() 2PK
		$gm = Group_Members::open(array('username' => 'user1','groupname' => 'group1'));
		self::check_first_event('filter', 'pre-open', false);
		self::check_first_event('notify', 'post-open', true);
		$res = $gm->delete();
		// Pre-Delete
		$e = self::check_first_event('filter', 'pre-delete', false);
		$this->assertEquals($e->arguments['model'], 'Group_Members');
		$this->assertEquals($e->arguments['record'], $gm);
		$this->assertEquals($e->filtered_value, false);
		// Post-delete
		$e = self::check_last_event('notify', 'post-delete', true);
		$this->assertInstanceOf('Group_Members', $e->arguments['record']);
		$this->assertEquals($e->arguments['record'], $gm);
		$this->assertTrue($res);

		// Recreate Database
		SampleSchema::destroy();
		SampleSchema::build();
	}

	public function testFilterOpen()
	{
		$filter_open_cancel = create_function('$e', '$e->filtered_value = false;');
		$filter_open_set_2 = create_function('$e', '$e->filtered_value = 2;');

		// Filter false for forum
		Forum::events()->connect('pre-open', $filter_open_cancel);
		$res = Forum::open(1);
		self::check_first_event('filter', 'pre-open', true);
		$this->assertFalse($res);

		// Group_Members pre-open should be left intact
		$gm = Group_Members::open(array('username' => 'user1','groupname' => 'group1'));
		self::check_first_event('filter', 'pre-open', false);
		self::check_first_event('notify', 'post-open', true);
		$this->assertInstanceOf('Group_Members', $gm);

		Forum::events()->disconnect('pre-open', $filter_open_cancel);

		// Filter open 2nd for forum
		Forum::events()->connect('pre-open', $filter_open_set_2);
		$res = Forum::open(1);
		self::check_first_event('filter', 'pre-open', false);
		self::check_first_event('notify', 'post-open', true);
		$this->assertInstanceOf('Forum', $res);
		$this->assertEquals($res->id, 2);

		// Group_Members pre-open should be left intact
		$gm = Group_Members::open(array('username' => 'user1','groupname' => 'group1'));
		self::check_first_event('filter', 'pre-open', false);
		self::check_first_event('notify', 'post-open', true);
		$this->assertInstanceOf('Group_Members', $gm);

		Forum::events()->disconnect('pre-open', $filter_open_set_2);
	}

	public function testFilterUpdate()
	{
		$filter_update_cancel = create_function('$e', '$e->filtered_value = true;');
		$filter_update_set_2 = create_function('$e', '$e->arguments[\'record\']->title = 2;');

		// Filter cancel for forum
		Forum::events()->connect('pre-update', $filter_update_cancel);
		$f = Forum::open(1);
		self::check_first_event('filter', 'pre-open', false);
		self::check_first_event('notify', 'post-open', true);
		$res = $f->update();
		$this->assertFalse($res);
		$f->title = 'update-1';
		$this->assertFalse($f->update());
		self::check_first_event('filter', 'pre-update', false);

		Forum::events()->disconnect('pre-update', $filter_update_cancel);

		// Filter change title for forum
		Forum::events()->connect('pre-update', $filter_update_set_2);
		$f = Forum::open(1);
		self::check_first_event('filter', 'pre-open', false);
		self::check_first_event('notify', 'post-open', true);
		$res = $f->update();
		$this->assertFalse($res);
		$f->title = 'update-1';
		$this->assertTrue($f->update());
		$this->assertEquals($f->title, 2);
		self::check_first_event('filter', 'pre-update', false);
		self::check_first_event('notify', 'post-update', true);

		Forum::events()->disconnect('pre-update', $filter_update_set_2);

		// Recreate Database
		SampleSchema::destroy();
		SampleSchema::build();
	}

	public function testFilterDelete()
	{
		$filter_delete_cancel = create_function('$e', '$e->filtered_value = true;');

		// Filter cancel for forum
		Forum::events()->connect('pre-delete', $filter_delete_cancel);
		$f = Forum::open(1);
		self::check_first_event('filter', 'pre-open', false);
		self::check_first_event('notify', 'post-open', true);
		$res = $f->delete();
		$this->assertFalse($res);
		self::check_first_event('filter', 'pre-delete', false);

		Forum::events()->disconnect('pre-delete', $filter_delete_cancel);

		// Recreate Database
		SampleSchema::destroy();
		SampleSchema::build();
	}

	public function testFilterCreate()
	{
		$filter_create_cancel = create_function('$e', '$e->filtered_value = false;');
		$filter_create_set_test5 = create_function('$e', '$e->filtered_value = array("title" => "5");');
		// Filter cancel for forum
		Forum::events()->connect('pre-create', $filter_create_cancel);
		$f = Forum::create(array('title' => 'test'));
		$this->assertFalse($f);
		self::check_first_event('filter', 'pre-create', true);

		Forum::events()->disconnect('pre-create',$filter_create_cancel);

		// Filter cancel for forum
		Forum::events()->connect('pre-create', $filter_create_set_test5);
		$f = Forum::create(array('title' => 'test'));
		$this->assertInstanceOf('Forum',  $f);
		$this->assertEquals($f->title, '5');
		self::check_first_event('filter', 'pre-create', false);
		self::check_first_event('notify', 'post-create', true);

		Forum::events()->disconnect('pre-create', $filter_create_set_test5);
		// Recreate Database
		SampleSchema::destroy();
		SampleSchema::build();
	}
}
