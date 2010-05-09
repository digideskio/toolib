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

class Conn_ConnectedTest extends PHPUnit_Framework_TestCase
{
    public static $events = array();

    public static function pop_event()
    {   return array_pop(self::$events);    }

    public static function push_event($e)
    {   array_push(self::$events, $e);   }

    public static function setUpBeforeClass()
    {
        SampleSchema::build();

        // Connect listener
        DB_Conn::events()->connect(
        NULL,
        array('Conn_ConnectedTest', 'push_event')
        );
    }

    public static function tearDownAfterClass()
    {   SampleSchema::destroy();
    }

    public function setUp()
    {   SampleSchema::connect();

    // Clean up events
    while(self::pop_event());
    }
    public function tearDown()
    {
        $this->assertEquals(count(self::$events), 0);
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

    public function testQuery()
    {   $mres = DB_Conn::query('SELECT * FROM forums');
    $this->assertType('mysqli_result', $mres);

    $res = array();
    while($row = $mres->fetch_array())
    $res[] = $row;

    $this->assertEquals(count($res), 3);
    $this->assertEquals(count($res[0]), 4);
    $this->assertEquals($res[0][1], 'The first');
    $this->assertEquals($res[0]['title'], 'The first');

    $this->check_last_event('notify', 'query', true);
    }

    public function testQueryFetchAll()
    {   $res = DB_Conn::query_fetch_all('SELECT * FROM forums');
    $this->assertType('array', $res);

    $this->assertEquals(count($res), 3);
    $this->assertEquals(count($res[0]), 4);
    $this->assertEquals($res[0][1], 'The first');
    $this->assertEquals($res[0]['title'], 'The first');

    $this->check_last_event('notify', 'query', true);
    }

    public function testQueryWrong()
    {   $res = @DB_Conn::query('SELECT * FROM forums_notexisting');
    $this->assertFalse($res);

    $res = @DB_Conn::query('-k- ');
    $this->assertFalse($res);

    $res = @DB_Conn::query_fetch_all('SELECT * FROM forums_notexisting');
    $this->assertFalse($res);

    $res = @DB_Conn::query_fetch_all('-k- ');
    $this->assertFalse($res);

    // Last 4 events must be errors
    $this->check_last_event('notify', 'error', false);
    $this->check_last_event('notify', 'error', false);
    $this->check_last_event('notify', 'error', false);
    $this->check_last_event('notify', 'error', true);
    }

    public function testPrepareDelayed()
    {
        // Check has key
        $this->assertFalse(DB_Conn::is_key_used('mynick'));

        // False preparation
        $res = DB_Conn::prepare('mynick', 'SELECT * FROM forums');
        $this->assertTrue($res);
        $this->check_last_event('notify', 'stmt.declared', true);

        // Check has key
        $this->assertTrue(DB_Conn::is_key_used('mynick'));

        // Retry same nick
        $res = @DB_Conn::prepare('mynick', 'SELECT * FROM forums');
        $this->assertFalse($res);
        $this->check_last_event('notify', 'error', true);

        // Check has key
        $this->assertTrue(DB_Conn::is_key_used('mynick'));

        // Execute unprepared statement
        $res = DB_Conn::execute('mynick');
        $this->assertType('mysqli_stmt', $res);
        $this->check_first_event('notify', 'stmt.prepared', false);
        $this->check_first_event('notify', 'stmt.executed', false);

        // Check has key
        $this->assertTrue(DB_Conn::is_key_used('mynick'));
    }

    public function testPrepareDelayedWrong()
    {
        // Check has key
        $this->assertFalse(DB_Conn::is_key_used('mynick'));

        // False preparation
        $res = DB_Conn::prepare('mynick', 'SELECT * FROM forums_notexisting');
        $this->assertTrue($res);
        $this->check_last_event('notify', 'stmt.declared', true);

        // Check has key
        $this->assertTrue(DB_Conn::is_key_used('mynick'));

        // Retry same nick
        $res = @DB_Conn::prepare('mynick', 'SELECT * FROM forums');
        $this->assertFalse($res);
        $this->check_last_event('notify', 'error', true);

        // Check has key
        $this->assertTrue(DB_Conn::is_key_used('mynick'));

        // Execute false unprepared statement
        $res = @DB_Conn::execute('mynick');
        $this->assertFalse($res);
        $this->check_last_event('notify', 'error', true);

        // Check has key
        $this->assertFalse(DB_Conn::is_key_used('mynick'));

        // Execute false unprepared statement
        $res = @DB_Conn::execute('mynick');
        $this->assertFalse($res);
        $this->check_last_event('notify', 'error', true);

        // Check has key
        $this->assertFalse(DB_Conn::is_key_used('mynick'));
    }

    public function testExecuteDelayed()
    {   // Execute unknown prepared statement
        $res = @DB_Conn::execute('not-existsing');
        $this->assertFalse($res);
        $this->check_last_event('notify', 'error', true);

        // Check has key
        $this->assertFalse(DB_Conn::is_key_used('mynick'));

        // Prepare a stement
        $res = DB_Conn::prepare('mynick', 'SELECT * FROM forums');
        $this->assertTrue($res);
        $this->check_last_event('notify', 'stmt.declared', true);

        // Check has key
        $this->assertTrue(DB_Conn::is_key_used('mynick'));

        // Execute and fetch the same prepared statement
        $res = DB_Conn::execute_fetch_all('mynick');
        $this->assertType('array', $res);
        $this->assertEquals(count($res), 3);
        $this->assertEquals(count($res[0]), 4);
        $this->assertEquals($res[0][1], 'The first');
        $this->assertEquals($res[0]['title'], 'The first');
        $this->check_first_event('notify', 'stmt.prepared', false);
        $this->check_first_event('notify', 'stmt.executed', false);

        // Check has key
        $this->assertTrue(DB_Conn::is_key_used('mynick'));
    }

    public function testExecuteNonDelayed()
    {   // Reconnect with no delayed prepartion
        SampleSchema::connect(false);
        $this->check_first_event('notify', 'disconnected', false);
        $this->check_first_event('notify', 'connected', true);

        // Check has key
        $this->assertFalse(DB_Conn::is_key_used('mynick'));

        // False preparation
        $res = @DB_Conn::prepare('mynick', 'SELECT * FROM forums_notexisting');
        $this->assertFalse($res);
        $this->check_first_event('notify', 'stmt.declared', false);
        $this->check_first_event('notify', 'error', true);

        // Check has key
        $this->assertFalse(DB_Conn::is_key_used('mynick'));

        // Retry same nick with correct format
        $res = DB_Conn::prepare('mynick', 'SELECT * FROM forums');
        $this->assertTrue($res);
        $this->check_first_event('notify', 'stmt.declared', false);
        $this->check_first_event('notify', 'stmt.prepared', true);

        // Check has key
        $this->assertTrue(DB_Conn::is_key_used('mynick'));

        // Execute prepared statement
        $res = DB_Conn::execute('mynick');
        $this->assertType('mysqli_stmt', $res);
        $this->check_last_event('notify', 'stmt.executed', true);

        // Check has key
        $this->assertTrue(DB_Conn::is_key_used('mynick'));

        // Execute and fetch the same prepared statement
        $res = DB_Conn::execute_fetch_all('mynick');
        $this->assertType('array', $res);
        $this->assertEquals(count($res), 3);
        $this->assertEquals(count($res[0]), 4);
        $this->assertEquals($res[0][1], 'The first');
        $this->assertEquals($res[0]['title'], 'The first');
        $this->check_first_event('notify', 'stmt.executed', false);

        // Check has key
        $this->assertTrue(DB_Conn::is_key_used('mynick'));
    }

    public function testReleaseDelayed()
    {   // Check has key
        $this->assertFalse(DB_Conn::is_key_used('mynick'));

        // Release
        $res = @DB_Conn::release('mynick');
        $this->assertFalse($res);
        $this->check_last_event('notify', 'error', true);

        // False preparation
        $res = DB_Conn::prepare('mynick', 'SELECT * FROM forums_notexisting');
        $this->assertTrue($res);
        $this->check_last_event('notify', 'stmt.declared', true);

        // Check has key
        $this->assertTrue(DB_Conn::is_key_used('mynick'));

        // Release
        $res = DB_Conn::release('mynick');
        $this->assertTrue($res);
        $this->check_last_event('notify', 'stmt.released', true);

        // Check has key
        $this->assertFalse(DB_Conn::is_key_used('mynick'));

        // Prepare and execute
        DB_Conn::prepare('mynick', 'SELECT * FROM forums');
        DB_Conn::execute_fetch_all('mynick');
        $this->check_first_event('notify', 'stmt.declared', false);
        $this->check_first_event('notify', 'stmt.prepared', false);
        $this->check_first_event('notify', 'stmt.executed', true);

        // Check has key
        $this->assertTrue(DB_Conn::is_key_used('mynick'));

        // Release
        $res = DB_Conn::release('mynick');
        $this->assertTrue($res);
        $this->check_last_event('notify', 'stmt.released', true);

        // Check has key
        $this->assertFalse(DB_Conn::is_key_used('mynick'));

    }
}
?>
