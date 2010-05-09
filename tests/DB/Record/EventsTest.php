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
require_once __DIR__ .  '/../../path.inc.php';
require_once __DIR__ .  '/../SampleSchema.class.php';
require_once __DIR__ .  '/../SampleModels.inc.php';

class Record_EventsTest extends PHPUnit_Framework_TestCase
{
    public static $events = array();

    public static function pop_event()
    {   return array_pop(self::$events);    }

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
    {   $this->assertEquals(count(self::$events), 0);
    DB_Conn::disconnect();
    }

    public function check_last_event($type, $name, $check_last)
    {   $e = self::pop_event();
    $this->assertType('Event', $e);
    $this->assertEquals($e->type, $type);
    $this->assertEquals($e->name, $name);
    if ($check_last)
    $this->assertEquals(0, count(self::$events));
    return $e;
    }

    public function check_first_event($type, $name, $check_last)
    {   $e = array_shift(self::$events);
    $this->assertType('Event', $e);
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
        $e = self::check_first_event('filter', 'op.pre.open', false);
        $this->assertEquals($e->arguments['model'], 'Forum');
        $this->assertEquals($e->filtered_value, 1);
        // Post-Open
        $e = self::check_last_event('notify', 'op.post.open', true);
        $this->assertType('array', $e->arguments['records']);
        $this->assertEquals(count($e->arguments['records']), 1);
        $this->assertType('Forum', $e->arguments['records'][0]);
        $this->assertEquals($e->arguments['model'], 'Forum');

        // Open_all() 1PK
        $res = Forum::open_all();
        // Post-Open
        $e = self::check_last_event('notify', 'op.post.open', true);
        $this->assertType('array', $e->arguments['records']);
        $this->assertEquals(count($e->arguments['records']), 3);
        $this->assertEquals($e->arguments['model'], 'Forum');
        $this->assertEquals($e->arguments['records'], $res);
        $this->assertType('Forum', $e->arguments['records'][0]);

        // Open() 2PK
        $res = Group_Members::open(array('username' => 'user1','groupname' => 'group1'));
        // Pre-Open
        $e = self::check_first_event('filter', 'op.pre.open', false);
        $this->assertEquals($e->arguments['model'], 'Group_Members');
        $this->assertEquals($e->filtered_value, array('username' => 'user1','groupname' => 'group1'));
        // Post-Open
        $e = self::check_last_event('notify', 'op.post.open', true);
        $this->assertType('array', $e->arguments['records']);
        $this->assertEquals(count($e->arguments['records']), 1);
        $this->assertType('Group_Members', $e->arguments['records'][0]);
        $this->assertEquals($e->arguments['model'], 'Group_Members');

        // Open_all() 2PK
        $res = Group_Members::open_all();
        // Post-Open
        $e = self::check_last_event('notify', 'op.post.open', true);
        $this->assertType('array', $e->arguments['records']);
        $this->assertEquals(count($e->arguments['records']), 8);
        $this->assertEquals($e->arguments['model'], 'Group_Members');
        $this->assertEquals($e->arguments['records'], $res);
        $this->assertType('Group_Members', $e->arguments['records'][0]);

    }

    public function testCreateEvents()
    {
        // Create() 1PK
        $f = Forum::create(array('title' => 'test'));
        // Pre-Create
        $e = self::check_first_event('filter', 'op.pre.create', false);
        $this->assertEquals($e->arguments['model'], 'Forum');
        $this->assertEquals($e->filtered_value, array('title' => 'test'));
        // Post-Create
        $e = self::check_last_event('notify', 'op.post.create', true);
        $this->assertType('Forum', $e->arguments['record']);
        $this->assertEquals($e->arguments['record'], $f);

        // Create() 2PK
        $gm = Group_Members::create(array('username' => 'user5', 'groupname' => 'group1'));
        // Pre-Create
        $e = self::check_first_event('filter', 'op.pre.create', false);
        $this->assertEquals($e->arguments['model'], 'Group_Members');
        $this->assertEquals($e->filtered_value, array('username' => 'user5', 'groupname' => 'group1'));
        // Post-Create
        $e = self::check_last_event('notify', 'op.post.create', true);
        $this->assertType('Group_Members', $e->arguments['record']);
        $this->assertEquals($e->arguments['record'], $gm);

        // Recreate Database
        SampleSchema::destroy();
        SampleSchema::build();
    }

    public function testSaveEvents()
    {
        // Save() 1PK
        $f = Forum::open(1);
        self::check_first_event('filter', 'op.pre.open', false);
        self::check_first_event('notify', 'op.post.open', true);
        $f->save();
        $this->assertEquals(self::$events, array());
        $f->title = 'new title';
        $res = $f->save();
        // Pre-Save
        $e = self::check_first_event('filter', 'op.pre.save', false);
        $this->assertEquals($e->arguments['model'], 'Forum');
        $this->assertEquals($e->arguments['record'], $f);
        $this->assertEquals($e->arguments['old_values'], array('title' => 'The first'));
        $this->assertEquals($e->filtered_value, false);
        // Post-Save
        $e = self::check_last_event('notify', 'op.post.save', true);
        $this->assertType('Forum', $e->arguments['record']);
        $this->assertEquals($e->arguments['record'], $f);
        $this->assertTrue($res);

        // Save() 2PK
        $gm = Group_Members::open(array('username' => 'user1','groupname' => 'group1'));
        self::check_first_event('filter', 'op.pre.open', false);
        self::check_first_event('notify', 'op.post.open', true);
        $gm->save();
        $this->assertEquals(self::$events, array());
        $gm->groupname = 'group3';
        $res = $gm->save();
        // Pre-Save
        $e = self::check_first_event('filter', 'op.pre.save', false);
        $this->assertEquals($e->arguments['model'], 'Group_Members');
        $this->assertEquals($e->arguments['record'], $gm);
        $this->assertEquals($e->arguments['old_values'], array('groupname' => 'group1'));
        $this->assertEquals($e->filtered_value, false);
        // Post-Save
        $e = self::check_last_event('notify', 'op.post.save', true);
        $this->assertType('Group_Members', $e->arguments['record']);
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
        self::check_first_event('filter', 'op.pre.open', false);
        self::check_first_event('notify', 'op.post.open', true);
        $res = $f->delete();
        // Pre-Delete
        $e = self::check_first_event('filter', 'op.pre.delete', false);
        $this->assertEquals($e->arguments['model'], 'Forum');
        $this->assertEquals($e->arguments['record'], $f);
        $this->assertEquals($e->filtered_value, false);
        // Post-delete
        $e = self::check_last_event('notify', 'op.post.delete', true);
        $this->assertType('Forum', $e->arguments['record']);
        $this->assertEquals($e->arguments['record'], $f);
        $this->assertTrue($res);

        // delete() 2PK
        $gm = Group_Members::open(array('username' => 'user1','groupname' => 'group1'));
        self::check_first_event('filter', 'op.pre.open', false);
        self::check_first_event('notify', 'op.post.open', true);
        $res = $gm->delete();
        // Pre-Delete
        $e = self::check_first_event('filter', 'op.pre.delete', false);
        $this->assertEquals($e->arguments['model'], 'Group_Members');
        $this->assertEquals($e->arguments['record'], $gm);
        $this->assertEquals($e->filtered_value, false);
        // Post-delete
        $e = self::check_last_event('notify', 'op.post.delete', true);
        $this->assertType('Group_Members', $e->arguments['record']);
        $this->assertEquals($e->arguments['record'], $gm);
        $this->assertTrue($res);

        // Recreate Database
        SampleSchema::destroy();
        SampleSchema::build();
    }

    public function testFilterOpen()
    {   $filter_open_cancel = create_function('$e', '$e->filtered_value = false;');
    $filter_open_set_2 = create_function('$e', '$e->filtered_value = 2;');

    // Filter false for forum
    Forum::events()->connect('op.pre.open', $filter_open_cancel);
    $res = Forum::open(1);
    self::check_first_event('filter', 'op.pre.open', true);
    $this->assertFalse($res);

    // Group_Members op.pre.open should be left intact
    $gm = Group_Members::open(array('username' => 'user1','groupname' => 'group1'));
    self::check_first_event('filter', 'op.pre.open', false);
    self::check_first_event('notify', 'op.post.open', true);
    $this->assertType('Group_Members', $gm);

    Forum::events()->disconnect('op.post.open', $filter_open_cancel);

    // Filter open 2nd for forum
    Forum::events()->connect('op.pre.open', $filter_open_set_2);
    $res = Forum::open(1);
    self::check_first_event('filter', 'op.pre.open', false);
    self::check_first_event('notify', 'op.post.open', true);
    $this->assertType('Forum', $res);
    $this->assertEquals($res->id, 2);

    // Group_Members op.pre.open should be left intact
    $gm = Group_Members::open(array('username' => 'user1','groupname' => 'group1'));
    self::check_first_event('filter', 'op.pre.open', false);
    self::check_first_event('notify', 'op.post.open', true);
    $this->assertType('Group_Members', $gm);

    Forum::events()->disconnect('op.post.open', $filter_open_set_2);
    }

    public function testFilterSave()
    {   $filter_save_cancel = create_function('$e', '$e->filtered_value = true;');
    $filter_save_set_2 = create_function('$e', '$e->arguments[\'record\']->title = 2;');

    // Filter cancel for forum
    Forum::events()->connect('op.pre.save', $filter_save_cancel);
    $f = Forum::open(1);
    self::check_first_event('filter', 'op.pre.open', false);
    self::check_first_event('notify', 'op.post.open', true);
    $res = $f->save();
    $this->assertFalse($res);
    $f->title = 'save-1';
    $this->assertFalse($f->save());
    self::check_first_event('filter', 'op.pre.save', false);

    Forum::events()->disconnect('op.pre.save', $filter_save_cancel);

    // Filter change title for forum
    Forum::events()->connect('op.pre.save', $filter_save_set_2);
    $f = Forum::open(1);
    self::check_first_event('filter', 'op.pre.open', false);
    self::check_first_event('notify', 'op.post.open', true);
    $res = $f->save();
    $this->assertFalse($res);
    $f->title = 'save-1';
    $this->assertTrue($f->save());
    $this->assertEquals($f->title, 2);
    self::check_first_event('filter', 'op.pre.save', false);
    self::check_first_event('notify', 'op.post.save', true);

    Forum::events()->disconnect('op.pre.save', $filter_save_set_2);

    // Recreate Database
    SampleSchema::destroy();
    SampleSchema::build();
    }

    public function testFilterDelete()
    {   $filter_delete_cancel = create_function('$e', '$e->filtered_value = true;');

    // Filter cancel for forum
    Forum::events()->connect('op.pre.delete', $filter_delete_cancel);
    $f = Forum::open(1);
    self::check_first_event('filter', 'op.pre.open', false);
    self::check_first_event('notify', 'op.post.open', true);
    $res = $f->delete();
    $this->assertFalse($res);
    self::check_first_event('filter', 'op.pre.delete', false);

    Forum::events()->disconnect('op.pre.delete', $filter_delete_cancel);

    // Recreate Database
    SampleSchema::destroy();
    SampleSchema::build();
    }

    public function testFilterCreate()
    {   $filter_create_cancel = create_function('$e', '$e->filtered_value = false;');
    $filter_create_set_test5 = create_function('$e', '$e->filtered_value = array("title" => "5");');
    // Filter cancel for forum
    Forum::events()->connect('op.pre.create', $filter_create_cancel);
    $f = Forum::create(array('title' => 'test'));
    $this->assertFalse($f);
    self::check_first_event('filter', 'op.pre.create', true);

    Forum::events()->disconnect('op.pre.create',$filter_create_cancel);

    // Filter cancel for forum
    Forum::events()->connect('op.pre.create', $filter_create_set_test5);
    $f = Forum::create(array('title' => 'test'));
    $this->assertType('Forum',  $f);
    $this->assertEquals($f->title, '5');
    self::check_first_event('filter', 'op.pre.create', false);
    self::check_first_event('notify', 'op.post.create', true);

    Forum::events()->disconnect('op.pre.create', $filter_create_set_test5);
    // Recreate Database
    SampleSchema::destroy();
    SampleSchema::build();
    }
}
?>
