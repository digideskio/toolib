<?php
require_once 'PHPUnit/Framework.php';
require_once __DIR__ .  '/../path.inc.php';
require_once __DIR__ . '/SampleSchema.class.php';

class Auth_BackendDBTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Auth_SampleSchema::build();
    }

    public static function tearDownAfterClass()
    {
        Auth_SampleSchema::destroy();
    }

    public function setUp()
    {
        Auth_SampleSchema::connect();
    }

    public function tearDown()
    {
        DB_Conn::disconnect();
    }

    public function dataUsers()
    {
        return AutH_SampleSchema::$test_users ;
    }

    public function testPlainReUse()
    {
        $auth = new Auth_Backend_DB(array(
            'model_user' => 'User_plain',
            'field_username' => 'username',
            'field_password' => 'password'
            ));

            $res = $auth->authenticate('user1', 'false password');
            $this->assertFalse($res);

            $res = $auth->authenticate('unknown', 'false password');
            $this->assertFalse($res);

            $res = $auth->authenticate('user1', 'password1');
            $this->assertType('Auth_Identity_DB', $res);
            $this->assertEquals($res->id(), 'user1');
            $this->assertEquals($res->get_record(), User_plain::open('user1'));
    }

    public function testPlainIdReUse()
    {
        $auth = new Auth_Backend_DB(array(
            'model_user' => 'User_id',
            'field_username' => 'username',
            'field_password' => 'password'
            ));

            $res = $auth->authenticate('user1', 'false password');
            $this->assertFalse($res);

            $res = $auth->authenticate('unknown', 'false password');
            $this->assertFalse($res);
            //exit;

            $res = $auth->authenticate('user1', 'password1');
            $this->assertType('Auth_Identity_DB', $res);
            $this->assertEquals($res->id(), 'user1');
            $this->assertEquals($res->get_record(), User_id::open(1));
    }

    public function testMd5User()
    {
        $auth = new Auth_Backend_DB(array(
            'model_user' => 'User_md5',
            'field_username' => 'username',
            'field_password' => 'password',
            'hash_function' => 'md5'
            ));

            $res = $auth->authenticate('user1', 'false password');
            $this->assertFalse($res);

            $res = $auth->authenticate('unknown', 'false password');
            $this->assertFalse($res);

            $res = $auth->authenticate('user1', 'password1');
            $this->assertType('Auth_Identity_DB', $res);
            $this->assertEquals($res->id(), 'user1');
            $this->assertEquals($res->get_record(), User_md5::open('user1'));
    }

    public function testSha1User()
    {
        $auth = new Auth_Backend_DB(array(
            'model_user' => 'User_sha1',
            'field_username' => 'username',
            'field_password' => 'password',
            'hash_function' => 'sha1'
            ));

            $res = $auth->authenticate('user1', 'false password');
            $this->assertFalse($res);

            $res = $auth->authenticate('unknown', 'false password');
            $this->assertFalse($res);

            $res = $auth->authenticate('user1', 'password1');
            $this->assertType('Auth_Identity_DB', $res);
            $this->assertEquals($res->id(), 'user1');
            $this->assertEquals($res->get_record(), User_sha1::open('user1'));
    }

    /**
     * @dataProvider dataUsers
     */
    public function testPlainForce($username, $password, $enabled)
    {   static $auth = NULL;
    if (!$auth)
    $auth = new Auth_Backend_DB(array(
                'model_user' => 'User_plain',
                'field_username' => 'username',
                'field_password' => 'password'
                ));

                $res = $auth->authenticate($username, $password);
                $this->assertType('Auth_Identity_DB', $res);
                $this->assertEquals($res->id(), $username);
                $this->assertEquals($res->get_record(), User_plain::open($username));
    }

    /**
     * @dataProvider dataUsers
     */
    public function testPlainIdForce($username, $password, $enabled)
    {   static $auth = NULL;
    static $count = 0;
    $count +=  1;
    if (!$auth)
    $auth = new Auth_Backend_DB(array(
                'model_user' => 'User_id',
                'field_username' => 'username',
                'field_password' => 'password'
                ));

                $res = $auth->authenticate($username, $password);
                $this->assertType('Auth_Identity_DB', $res);
                $this->assertEquals($res->id(), $username);
                $this->assertEquals($res->get_record(), User_id::open($count));
    }


    /**
     * @dataProvider dataUsers
     */
    public function testMd5Force($username, $password, $enabled)
    {   static $auth = NULL;
    if (!$auth)
    $auth = new Auth_Backend_DB(array(
                'model_user' => 'User_md5',
                'field_username' => 'username',
                'field_password' => 'password',
                'hash_function' => 'md5'
                ));

                $res = $auth->authenticate($username, $password);
                $this->assertType('Auth_Identity_DB', $res);
                $this->assertEquals($res->id(), $username);
                $this->assertEquals($res->get_record(), User_md5::open($username));
    }

    /**
     * @dataProvider dataUsers
     */
    public function testSha1Force($username, $password, $enabled)
    {   static $auth = NULL;
    if (!$auth)
    $auth = new Auth_Backend_DB(array(
                'model_user' => 'User_sha1',
                'field_username' => 'username',
                'field_password' => 'password',
                'hash_function' => 'sha1'
                ));

                $res = $auth->authenticate($username, $password);
                $this->assertType('Auth_Identity_DB', $res);
                $this->assertEquals($res->id(), $username);
                $this->assertEquals($res->get_record(), User_sha1::open($username));
    }

    /**
     * @dataProvider dataUsers
     */
    public function testPlainEnabledForce($username, $password, $enabled)
    {   static $auth = NULL;
    if (!$auth)
    $auth = new Auth_Backend_DB(array(
                'model_user' => 'User_plain',
                'field_username' => 'username',
                'field_password' => 'password',
                'where_conditions' => array('enabled = 1')
    ));

    $res = $auth->authenticate($username, $password);
    if (!$enabled)
    {   $this->assertFalse($res);
    return;
    }
    $this->assertType('Auth_Identity_DB', $res);
    $this->assertEquals($res->id(), $username);
    $this->assertEquals($res->get_record(), User_plain::open($username));
    }

    /**
     * @dataProvider dataUsers
     */
    public function testPlainEnabledMd5($username, $password, $enabled)
    {   static $auth = NULL;
    if (!$auth)
    $auth = new Auth_Backend_DB(array(
                'model_user' => 'User_md5',
                'field_username' => 'username',
                'field_password' => 'password',
                'hash_function' => 'md5',
                'where_conditions' => array('enabled = 1')
    ));

    $res = $auth->authenticate($username, $password);
    if (!$enabled)
    {   $this->assertFalse($res);
    return;
    }
    $this->assertType('Auth_Identity_DB', $res);
    $this->assertEquals($res->id(), $username);
    $this->assertEquals($res->get_record(), User_md5::open($username));
    }

    public function testResetPlainPwd()
    {
        $auth = new Auth_Backend_DB(array(
            'model_user' => 'User_plain',
            'field_username' => 'username',
            'field_password' => 'password'
            ));

            $identity = $auth->authenticate('user1', 'password1');
            $this->assertType('Auth_Identity_DB', $identity);
            $this->assertTrue($identity->reset_password('passwordnew'));

            // Check same password
            $this->assertFalse($auth->authenticate('user1', 'password1'));
            $res = $auth->authenticate('user1', 'password1');

            // Check with new password
            $res = $auth->authenticate('user1', 'passwordnew');
            $this->assertType('Auth_Identity_DB', $res);
            $this->assertEquals($res->id(), 'user1');
            $this->assertEquals($res->get_record(), User_plain::open('user1'));

            // Rebuild
            Auth_SampleSchema::destroy();
            Auth_SampleSchema::build();
    }

    public function testResetPlainIdPwd()
    {
        $auth = new Auth_Backend_DB(array(
            'model_user' => 'User_id',
            'field_username' => 'username',
            'field_password' => 'password'
            ));

            $identity = $auth->authenticate('user1', 'password1');
            $this->assertType('Auth_Identity_DB', $identity);
            $this->assertTrue($identity->reset_password('passwordnew'));

            // Check same password
            $this->assertFalse($auth->authenticate('user1', 'password1'));
            $res = $auth->authenticate('user1', 'password1');

            // Check with new password
            $res = $auth->authenticate('user1', 'passwordnew');
            $this->assertType('Auth_Identity_DB', $res);
            $this->assertEquals($res->id(), 'user1');
            $this->assertEquals($res->get_record(), User_id::open(1));

            // Rebuild
            Auth_SampleSchema::destroy();
            Auth_SampleSchema::build();
    }


    public function testResetMd5Pwd()
    {
        $auth = new Auth_Backend_DB(array(
            'model_user' => 'User_md5',
            'field_username' => 'username',
            'field_password' => 'password',
            'hash_function' => 'md5',
        ));

        $identity = $auth->authenticate('user1', 'password1');
        $this->assertType('Auth_Identity_DB', $identity);
        $this->assertTrue($identity->reset_password('passwordnew'));

        // Check same password
        $this->assertFalse($auth->authenticate('user1', 'password1'));
        $res = $auth->authenticate('user1', 'password1');

        // Check with new password
        $res = $auth->authenticate('user1', 'passwordnew');
        $this->assertType('Auth_Identity_DB', $res);
        $this->assertEquals($res->id(), 'user1');
        $this->assertEquals($res->get_record(), User_md5::open('user1'));

        // Rebuild
        Auth_SampleSchema::destroy();
        Auth_SampleSchema::build();
    }

    public function testResetSha1Pwd()
    {
        $auth = new Auth_Backend_DB(array(
            'model_user' => 'User_sha1',
            'field_username' => 'username',
            'field_password' => 'password',
            'hash_function' => 'sha1',
        ));

        $identity = $auth->authenticate('user1', 'password1');
        $this->assertType('Auth_Identity_DB', $identity);
        $this->assertTrue($identity->reset_password('passwordnew'));

        // Check same password
        $this->assertFalse($auth->authenticate('user1', 'password1'));
        $res = $auth->authenticate('user1', 'password1');

        // Check with new password
        $res = $auth->authenticate('user1', 'passwordnew');
        $this->assertType('Auth_Identity_DB', $res);
        $this->assertEquals($res->id(), 'user1');
        $this->assertEquals($res->get_record(), User_sha1::open('user1'));

        // Rebuild
        Auth_SampleSchema::destroy();
        Auth_SampleSchema::build();
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testMissArgument1()
    {
        $auth = new Auth_Backend_DB();
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testMissArgument2()
    {
        $auth = new Auth_Backend_DB(array());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testMissArgument3()
    {
        $auth = new Auth_Backend_DB(array('model_user' => 'User_plain'));
    }
}
?>
