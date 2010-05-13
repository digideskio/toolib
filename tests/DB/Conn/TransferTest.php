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

class Conn_TransferTest extends PHPUnit_Framework_TestCase
{
    public static $events = array();

    public static function push_event($e)
    {   array_push(self::$events, $e);   }

    public static function setUpBeforeClass()
    {
        SampleSchema::build();

        // Connect listener
        DB_Conn::events()->connect(
        NULL,
        array('Conn_TransferTest', 'push_event')
        );
    }

    public static function tearDownAfterClass()
    {   SampleSchema::destroy();
    }

    public function setUp()
    {   SampleSchema::connect();

    DB_Conn::prepare('insert-forum', 'INSERT INTO forums (titles) VALUES (?)');
    DB_Conn::prepare('inser-post', 'INSERT INTO posts (titles) VALUES (?)');
    // Clean up events
    self::$events = array();
    }
    public function tearDown()
    {
        DB_Conn::disconnect();
    }

    public function check_last_event($type, $name, $check_last)
    {   $e = array_pop(self::$events);
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

    public function testQueryFetch()
    {
        $data = DB_Conn::query_fetch_all('SELECT * from forums LIMIT 1');
        $this->assertType('array', $data);
        $this->assertEquals(count($data), 1);
        $this->assertEquals($data, array(
        array(
        0 => '1',
                'id' => '1',
        1 => 'The first',
                'title' => 'The first'
                )
                ));
    }

    public function testExecuteNoParamFetch()
    {
        DB_Conn::prepare('test', 'SELECT * from forums LIMIT 1');
        $data = DB_Conn::execute_fetch_all('test');
        $this->assertType('array', $data);
        $this->assertEquals(count($data), 1);
        $this->assertEquals($data, array(
        array(
        0 => '1',
                'id' => '1',
        1 => 'The first',
                'title' => 'The first'
                )
                ));
    }

    public function testExecutePassParamFetch()
    {
        DB_Conn::prepare('test', 'SELECT * from forums LIMIT ?,?');
        $data = DB_Conn::execute_fetch_all('test', array(0,1));
        $this->assertType('array', $data);
        $this->assertEquals(count($data), 1);
        $this->assertEquals($data, array(
        array(
        0 => '1',
                'id' => '1',
        1 => 'The first',
                'title' => 'The first'
                )
                ));

                // Re run with other parameters
                $data = DB_Conn::execute_fetch_all('test', array(1, 1));
                $this->assertType('array', $data);
                $this->assertEquals(count($data), 1);
                $this->assertEquals($data, array(
                array(
                0 => '2',
                'id' => '2',
                1 => 'The second',
                'title' => 'The second'
                )
                ));
    }

    public function testExecutePassParamTypeFetch()
    {
        DB_Conn::prepare('test', 'SELECT * from forums LIMIT ?,?');
        $data = DB_Conn::execute_fetch_all('test', array(0,1), array('i', 's'));
        $this->assertType('array', $data);
        $this->assertEquals(count($data), 1);
        $this->assertEquals($data, array(
        array(
        0 => '1',
                'id' => '1',
        1 => 'The first',
                'title' => 'The first'
                )
                ));

                // Re run with other parameters
                $data = DB_Conn::execute_fetch_all('test', array(1,1), array('i', 's'));
                $this->assertType('array', $data);
                $this->assertEquals(count($data), 1);
                $this->assertEquals($data, array(
                array(
                0 => '2',
                'id' => '2',
                1 => 'The second',
                'title' => 'The second'
                )
                ));
    }

    public function testQueryFetchBlob()
    {   $big_post = str_repeat('1234567890', 100000);
    $data = DB_Conn::query_fetch_all('SELECT id, thread_id, post, poster from posts WHERE poster = \'long\'');
    $this->assertType('array', $data);
    $this->assertEquals(count($data), 1);
    $this->assertEquals($data, array(
    array(
    0 => '7',
                'id' => '7',
    1 => '2',
                'thread_id' => '2',
    2 => $big_post,
                'post' => $big_post,
    3 => 'long',
                'poster' => 'long'
                )
                ));
    }

    public function testExecuteFetchBlob()
    {   $big_post = str_repeat('1234567890', 100000);
    DB_Conn::prepare('test', 'SELECT id, thread_id, post, poster from posts WHERE poster = \'long\'');
    $data = DB_Conn::execute_fetch_all('test');
    $this->assertType('array', $data);
    $this->assertEquals(count($data), 1);
    $this->assertEquals($data, array(
    array(
    0 => '7',
                'id' => '7',
    1 => '2',
                'thread_id' => '2',
    2 => $big_post,
                'post' => $big_post,
    3 => 'long',
                'poster' => 'long'
                )
                ));
    }

    public function testExecutePushBlob()
    {   $big_post = str_repeat('1234567890', 100000);

    DB_Conn::prepare('test', 'INSERT posts (thread_id, post, poster) VALUES (?,?,?)');
    $res = DB_Conn::execute('test', array(3, 'boob', 'poster'));
    $this->assertType('mysqli_stmt', $res);

    $res = DB_Conn::execute('test', array(3, $big_post, 'poster'), array('s', 'b', 's'));
    $this->assertType('mysqli_stmt', $res);
    $last_id = DB_Conn::last_insert_id();

    $res = DB_Conn::query_fetch_all("SELECT * FROM posts WHERE id ='{$last_id}'");
    $this->assertEquals($res[0]['post'], $big_post);
    }
}
?>
