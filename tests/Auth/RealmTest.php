<?php
require_once 'PHPUnit/Framework.php';
require_once __DIR__ .  '/../path.inc.php';
require_once __DIR__ .  '/SampleSchema.class.php';

class Auth_RealmTest extends PHPUnit_Framework_TestCase
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
        Auth_SampleSchema::build();
        Auth_Realm::events()->connect(
            NULL,
            array('Auth_RealmTest', 'push_event')
        );
        Auth_SampleSchema::connect();
        self::$storage = new Auth_Storage_Instance();
        self::$auth = new Auth_Backend_DB(array(
            'model_user' => 'User_md5',
            'field_username' => 'username',
            'field_password' => 'password',
            'hash_function' => 'md5'
        ));
    }

    public static function tearDownAfterClass()
    {   
        Auth_SampleSchema::destroy();
        Auth_Realm::events()->disconnect(
            NULL,
            array('Auth_RealmTest', 'push_event')
        );
    }


    
    public function setUp()
    {   
        Auth_SampleSchema::connect();
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

    public function testSetters()
    {   
        // Check default values
        $this->assertType('Auth_Storage_Session', Auth_Realm::get_storage());
        $this->assertNull(Auth_Realm::get_backend());
        $this->assertFalse(Auth_Realm::get_identity());
        $this->assertFalse(Auth_Realm::has_identity());
        
        Auth_Realm::set_storage(self::$storage);
        $this->assertType('Auth_Storage_Instance', Auth_Realm::get_storage());
        $this->assertEquals(self::$storage, Auth_Realm::get_storage());
        $this->assertFalse(Auth_Realm::get_identity());
        $this->assertFalse(Auth_Realm::has_identity());
        
        Auth_Realm::set_backend(self::$auth);
        $this->assertType('Auth_Backend_DB', Auth_Realm::get_backend());
        $this->assertEquals(self::$auth, Auth_Realm::get_backend());
        $this->assertFalse(Auth_Realm::get_identity());
        $this->assertFalse(Auth_Realm::has_identity());
    }

    public function testAuthenticate()
    {
        Auth_Realm::set_storage(self::$storage);
        Auth_Realm::set_backend(self::$auth);
        $this->assertFalse(Auth_Realm::get_identity());
        $this->assertFalse(Auth_Realm::has_identity());

        // False clear identity
        Auth_Realm::clear_identity();
        
        // False authentication
        $res = Auth_Realm::authenticate('user1', 'false password');
        $this->assertFalse($res);
        $this->assertFalse(Auth_Realm::get_identity());
        $this->assertFalse(Auth_Realm::has_identity());
        self::check_first_event('notify', 'auth.error', true);

        // Successful authentication
        $res = Auth_Realm::authenticate('user1', 'password1');
        $this->assertType('Auth_Identity_DB', $res);
        $this->assertEquals($res, Auth_Realm::get_identity());
        $this->assertTrue(Auth_Realm::has_identity());
        self::check_first_event('notify', 'auth.successful', true);

        // Reauthenticate with new user
        $res = Auth_Realm::authenticate('user2', 'password2 #');
        $this->assertType('Auth_Identity_DB', $res);
        $this->assertEquals($res, Auth_Realm::get_identity());
        $this->assertTrue(Auth_Realm::has_identity());
        self::check_first_event('notify', 'ident.clear', false);
        self::check_first_event('notify', 'auth.successful', true);
    }
}
?>
