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

class Authn_RealmTest extends PHPUnit_Framework_TestCase
{
    public static $events = array();
    public static $storage;
    public static $auth;

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
        Authn_SampleSchema::build();
        Authn_Realm::events()->connect(
            NULL,
            array('Authn_RealmTest', 'push_event')
        );
        Authn_SampleSchema::connect();
        self::$storage = new Authn_Session_Instance();
        self::$auth = new Authn_Backend_DB(array(
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
        Authn_Realm::events()->disconnect(
        NULL,
        array('Authn_RealmTest', 'push_event')
        );
    }



    public function setUp()
    {
        Authn_SampleSchema::connect();
    }

    public function tearDown()
    {
        $this->assertEquals(count(self::$events), 0);
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

    public function testSetters()
    {
        // Check default values
        $this->assertType('Authn_Session_Native', Authn_Realm::get_session());
        $this->assertNull(Authn_Realm::get_backend());
        $this->assertFalse(Authn_Realm::get_identity());
        $this->assertFalse(Authn_Realm::has_identity());

        Authn_Realm::set_session(self::$storage);
        $this->assertType('Authn_Session_Instance', Authn_Realm::get_session());
        $this->assertEquals(self::$storage, Authn_Realm::get_session());
        $this->assertFalse(Authn_Realm::get_identity());
        $this->assertFalse(Authn_Realm::has_identity());

        Authn_Realm::set_backend(self::$auth);
        $this->assertType('Authn_Backend_DB', Authn_Realm::get_backend());
        $this->assertEquals(self::$auth, Authn_Realm::get_backend());
        $this->assertFalse(Authn_Realm::get_identity());
        $this->assertFalse(Authn_Realm::has_identity());
    }

    public function testAuthnenticate()
    {
        Authn_Realm::set_session(self::$storage);
        Authn_Realm::set_backend(self::$auth);
        $this->assertFalse(Authn_Realm::get_identity());
        $this->assertFalse(Authn_Realm::has_identity());

        // False clear identity
        Authn_Realm::clear_identity();

        // False authentication
        $res = Authn_Realm::authenticate('user1', 'false password');
        $this->assertFalse($res);
        $this->assertFalse(Authn_Realm::get_identity());
        $this->assertFalse(Authn_Realm::has_identity());
        self::check_first_event('notify', 'auth.error', true);

        // Successful authentication
        $res = Authn_Realm::authenticate('user1', 'password1');
        $this->assertType('Authn_Identity_DB', $res);
        $this->assertEquals($res, Authn_Realm::get_identity());
        $this->assertTrue(Authn_Realm::has_identity());
        self::check_first_event('notify', 'auth.successful', true);

        // Reauthenticate with new user
        $res = Authn_Realm::authenticate('user2', 'password2 #');
        $this->assertType('Authn_Identity_DB', $res);
        $this->assertEquals($res, Authn_Realm::get_identity());
        $this->assertTrue(Authn_Realm::has_identity());
        self::check_first_event('notify', 'ident.clear', false);
        self::check_first_event('notify', 'auth.successful', true);
    }
}
?>
