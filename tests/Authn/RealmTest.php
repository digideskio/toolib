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


use toolib\DB\Connection;

use toolib\Authn\Realm;
use toolib\Authn\Session as aSession;
use toolib as tb;


require_once __DIR__ .  '/../path.inc.php';
require_once __DIR__ . '/SampleSchema.class.php';


class RealmTest extends PHPUnit_Framework_TestCase
{
    public static $events = array();
    public static $storage;
    public static $auth;

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
        Authn_SampleSchema::build();
        Realm::events()->connect(
            NULL,
            array('RealmTest', 'push_event')
        );
        Authn_SampleSchema::connect();
        self::$storage = new \toolib\Authn\Session\Instance();
        self::$auth = new \toolib\Authn\DB\Backend(array(
            'query_user' => User_md5::openQuery()
                ->where('username = ?'),
            'field_username' => 'username',
            'field_password' => 'password',
            'hash_function' => create_function('$pass, $record','return md5($pass);')
            ));
    }

    public static function tearDownAfterClass()
    {
        Authn_SampleSchema::destroy();
        Realm::events()->disconnect(
        NULL,
        array('RealmTest', 'push_event')
        );
    }



    public function setUp()
    {
        Authn_SampleSchema::connect();
    }

    public function tearDown()
    {
        $this->assertEquals(count(self::$events), 0);
        Connection::disconnect();
    }

    public function check_last_event($type, $name, $check_last)
    {   
        $e = self::pop_event();
        $this->assertInstanceOf('\toolib\Event', $e);
        $this->assertEquals($e->type, $type);
        $this->assertEquals($e->name, $name);
        if ($check_last)
            $this->assertEquals(0, count(self::$events));
        return $e;
    }

    public function check_first_event($type, $name, $check_last)
    {   
        $e = array_shift(self::$events);
        $this->assertInstanceOf('\toolib\Event', $e);
        $this->assertEquals($e->type, $type);
        $this->assertEquals($e->name, $name);
        if ($check_last)
            $this->assertEquals(0, count(self::$events));
        return $e;
    }

    public function testSetters()
    {
        // Check default values
        $this->assertInstanceOf('\toolib\Authn\Session\Native', Realm::getSession());
        $this->assertNull(Realm::getBackend());
        $this->assertFalse(Realm::getIdentity());
        $this->assertFalse(Realm::hasIdentity());

        Realm::setSession(self::$storage);
        $this->assertInstanceOf('\toolib\Authn\Session\Instance', Realm::getSession());
        $this->assertEquals(self::$storage, Realm::getSession());
        $this->assertFalse(Realm::getIdentity());
        $this->assertFalse(Realm::hasIdentity());

        Realm::setBackend(self::$auth);
        $this->assertInstanceOf('\toolib\Authn\DB\Backend', Realm::getBackend());
        $this->assertEquals(self::$auth, Realm::getBackend());
        $this->assertFalse(Realm::getIdentity());
        $this->assertFalse(Realm::hasIdentity());
    }

    public function testAuthnenticate()
    {
        Realm::setSession(self::$storage);
        Realm::setBackend(self::$auth);
        $this->assertFalse(Realm::getIdentity());
        $this->assertFalse(Realm::hasIdentity());

        // False clear identity
        Realm::clearIdentity();

        // False authentication
        $res = Realm::authenticate('user1', 'false password');
        $this->assertFalse($res);
        $this->assertFalse(Realm::getIdentity());
        $this->assertFalse(Realm::hasIdentity());
        self::check_first_event('notify', 'auth.error', true);

        // Successful authentication
        $res = Realm::authenticate('user1', 'password1');
        $this->assertInstanceOf('toolib\Authn\DB\Identity', $res);
        $this->assertEquals($res, Realm::getIdentity());
        $this->assertTrue(Realm::hasIdentity());
        self::check_first_event('notify', 'auth.successful', true);

        // Reauthenticate with new user
        $res = Realm::authenticate('user2', 'password2 #');
        $this->assertInstanceOf('toolib\Authn\DB\Identity', $res);
        $this->assertEquals($res, Realm::getIdentity());
        $this->assertTrue(Realm::hasIdentity());
        self::check_first_event('notify', 'ident.clear', false);
        self::check_first_event('notify', 'auth.successful', true);
    }
}
