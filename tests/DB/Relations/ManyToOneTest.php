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

class Record_ManyToOneTest extends PHPUnit_Framework_TestCase
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
		$this->assertInstanceOf('Event', $e);
		$this->assertEquals($e->type, $type);
		$this->assertEquals($e->name, $name);
		if ($check_last)
		$this->assertEquals(0, count(self::$events));
		return $e;
	}

	public function check_first_event($type, $name, $check_last)
	{
		$e = array_shift(self::$events);
		$this->assertInstanceOf('Event', $e);
		$this->assertEquals($e->type, $type);
		$this->assertEquals($e->name, $name);
		if ($check_last)
		$this->assertEquals(0, count(self::$events));
		return $e;
	}

	public function testAll()
	{
		// Forum (1) => (M)Threads
		$threads = Forum::open(1)->threads->all();	
		$this->assertEquals(count($threads), 3);
		$this->assertEquals($threads[0]->title, 'First thread');
		$this->assertEquals($threads[1]->title, 'Second thread');
		$this->assertEquals($threads[2]->title, 'Third thread');

		// Threads (1) => (M) Posts
		$posts = Thread::open(1)->posts->all();	
		$this->assertEquals(count($posts), 3);
		$this->assertEquals($posts[0]->post, 'Bla bla bla post');
		$this->assertEquals($posts[1]->post, 'Second post');
		$this->assertEquals($posts[2]->post, 'Third post');
		
		// Thread (M) => (1) Forum
		$forum = Thread::open(4)->forum;		
		$this->assertInstanceOf('Forum', $forum);
		$this->assertEquals($forum->id, 2);
		
		$forum = Thread::open(1)->forum;
		$this->assertInstanceOf('Forum', $forum);
		$this->assertEquals($forum->id, 1);
		
		// Post (M) => (1) Thread
		$thread = Post::open(4)->thread;
		$this->assertInstanceOf('Thread', $thread);
		$this->assertEquals($thread->id, 2);
		
		$thread = Post::open(2)->thread;
		$this->assertInstanceOf('Thread', $thread);
		$this->assertEquals($thread->id, 1);
	}
	
	public function testSubquery()
	{
		// Simple execute
		$threads = Forum::open(1)->threads->subquery()
			->execute();
		$this->assertEquals(count($threads), 3);
		$this->assertEquals($threads[0]->title, 'First thread');
		$this->assertEquals($threads[1]->title, 'Second thread');
		$this->assertEquals($threads[2]->title, 'Third thread');
		
		// Where with like
		$threads = Forum::open(1)->threads->subquery()
			->where('title like ?')	
			->execute('%ir%');
		$this->assertEquals(count($threads), 2);
		$this->assertEquals($threads[0]->title, 'First thread');		
		$this->assertEquals($threads[1]->title, 'Third thread');
		
		// Order by (Reverse order)
		$threads = Forum::open(1)->threads->subquery()
			->orderBy('id', 'DESC')
			->execute();
		$this->assertEquals(count($threads), 3);
		$this->assertEquals($threads[2]->title, 'First thread');
		$this->assertEquals($threads[1]->title, 'Second thread');
		$this->assertEquals($threads[0]->title, 'Third thread');		
		
		// Limit result
		$threads = Forum::open(1)->threads->subquery()
			->orderBy('id', 'DESC')
			->limit(1)
			->execute();
		$this->assertEquals(count($threads), 1);
		$this->assertEquals($threads[0]->title, 'Third thread');		
	}
	
	public function testGet()
	{
		$this->assertInstanceOf('Thread', $thread = Forum::open(1)->threads->get(1));
		$this->AssertEquals(1, $thread->id);
		
		$this->assertInstanceOf('Thread', $thread = Forum::open(1)->threads->get(2));
		$this->AssertEquals(2, $thread->id);
		
		$this->AssertNull(Forum::open(1)->threads->get(4));
		$this->AssertNull(Forum::open(1)->threads->get(5));
	}
}

