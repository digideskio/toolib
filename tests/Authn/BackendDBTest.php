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

use toolib\Authn\DB\Backend;
use toolib\DB\Connection;

require_once __DIR__ .  '/../path.inc.php';
require_once __DIR__ . '/SampleSchema.class.php';

class Authn_BackendDBTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Authn_SampleSchema::build();
    }

    public static function tearDownAfterClass()
    {
        Authn_SampleSchema::destroy();
    }

    public function setUp()
    {
        Authn_SampleSchema::connect();
    }

    public function tearDown()
    {
        Connection::disconnect();
    }

    public function dataUsers()
    {
        return Authn_SampleSchema::$test_users ;
    }

    public function testPlainReUse()
    {
        $auth = new Backend(array(
            'query_user' => User_plain::openQuery()
                ->where('username = ?'),
            'field_username' => 'username',
            'field_password' => 'password'
        ));

        $res = $auth->authenticate('user1', 'false password');
        $this->assertFalse($res);

        $res = $auth->authenticate('unknown', 'false password');
        $this->assertFalse($res);

        $res = $auth->authenticate('user1', 'password1');
        $this->assertType('toolib\Authn\DB\Identity', $res);
        $this->assertEquals($res->id(), 'user1');
        $this->assertEquals($res->getRecord(), User_plain::open('user1'));
    }

    public function testPlainIdReUse()
    {
        $auth = new Backend(array(
            'query_user' => User_id::openQuery()
                ->where('username = ?'),
            'field_username' => 'username',
            'field_password' => 'password'
        ));

        $res = $auth->authenticate('user1', 'false password');
        $this->assertFalse($res);

        $res = $auth->authenticate('unknown', 'false password');
        $this->assertFalse($res);
        //exit;

        $res = $auth->authenticate('user1', 'password1');
        $this->assertType('toolib\Authn\DB\Identity', $res);
        $this->assertEquals($res->id(), 'user1');
        $this->assertEquals($res->getRecord(), User_id::open(1));
    }

    public function testMd5User()
    {
        $auth = new Backend(array(
            'query_user' => User_md5::openQuery()
                ->where('username = ?'),
            'field_username' => 'username',
            'field_password' => 'password',
            'hash_function' => create_function('$pass, $record','return md5($pass);')
        ));

        $res = $auth->authenticate('user1', 'false password');
        $this->assertFalse($res);

        $res = $auth->authenticate('unknown', 'false password');
        $this->assertFalse($res);

        $res = $auth->authenticate('user1', 'password1');
        $this->assertType('toolib\Authn\DB\Identity', $res);
        $this->assertEquals($res->id(), 'user1');
        $this->assertEquals($res->getRecord(), User_md5::open('user1'));
    }

    public function testSha1User()
    {
        $auth = new Backend(array(
            'query_user' => User_sha1::openQuery()
                ->where('username = ?'),
            'field_username' => 'username',
            'field_password' => 'password',
            'hash_function' => create_function('$pass, $record','return sha1($pass);')
        ));

        $res = $auth->authenticate('user1', 'false password');
        $this->assertFalse($res);

        $res = $auth->authenticate('unknown', 'false password');
        $this->assertFalse($res);

        $res = $auth->authenticate('user1', 'password1');
        $this->assertType('toolib\Authn\DB\Identity', $res);
        $this->assertEquals($res->id(), 'user1');
        $this->assertEquals($res->getRecord(), User_sha1::open('user1'));
    }

    /**
     * @dataProvider dataUsers
     */
    public function testPlainForce($username, $password, $enabled)
    {   
        static $auth = NULL;
        if (!$auth)
        $auth = new Backend(array(
            'query_user' => User_plain::openQuery()
                ->where('username = ?'),
            'field_username' => 'username',
            'field_password' => 'password',
        ));

        $res = $auth->authenticate($username, $password);
        $this->assertType('toolib\Authn\DB\Identity', $res);
        $this->assertEquals($res->id(), $username);
        $this->assertEquals($res->getRecord(), User_plain::open($username));
    }

    /**
     * @dataProvider dataUsers
     */
    public function testPlainIdForce($username, $password, $enabled)
    {   
        static $auth = NULL;
        static $count = 0;
        $count +=  1;
        if (!$auth)
            $auth = new Backend(array(
                'query_user' => User_id::openQuery()
                    ->where('username = ?'),
                'field_username' => 'username',
                'field_password' => 'password',
            ));

        $res = $auth->authenticate($username, $password);
        $this->assertType('toolib\Authn\DB\Identity', $res);
        $this->assertEquals($res->id(), $username);
        $this->assertEquals($res->getRecord(), User_id::open($count));
    }


    /**
     * @dataProvider dataUsers
     */
    public function testMd5Force($username, $password, $enabled)
    {   
        static $auth = NULL;
        if (!$auth)
            $auth = new Backend(array(
                'query_user' => User_md5::openQuery()
                    ->where('username = ?'),
                'field_username' => 'username',
                'field_password' => 'password',
                'hash_function' => create_function('$pass, $record','return md5($pass);')
            ));

        $res = $auth->authenticate($username, $password);
        $this->assertType('toolib\Authn\DB\Identity', $res);
        $this->assertEquals($res->id(), $username);
        $this->assertEquals($res->getRecord(), User_md5::open($username));
    }

    /**
     * @dataProvider dataUsers
     */
    public function testSha1Force($username, $password, $enabled)
    {   
        static $auth = NULL;
        if (!$auth)
            $auth = new Backend(array(
                'query_user' => User_sha1::openQuery()
                    ->where('username = ?'),
                'field_username' => 'username',
                'field_password' => 'password',
                'hash_function' => create_function('$pass, $record','return sha1($pass);')
            ));

        $res = $auth->authenticate($username, $password);
        $this->assertType('toolib\Authn\DB\Identity', $res);
        $this->assertEquals($res->id(), $username);
        $this->assertEquals($res->getRecord(), User_sha1::open($username));
    }

    /**
     * @dataProvider dataUsers
     */
    public function testPlainEnabledForce($username, $password, $enabled)
    {   
        static $auth = NULL;
        if (!$auth)
            $auth = new Backend(array(
                'query_user' => User_plain::openQuery()
                    ->where('enabled = ?')
                    ->pushExecParam('1')
                    ->where('username = ?'),
                'field_username' => 'username',
                'field_password' => 'password',
            ));

        $res = $auth->authenticate($username, $password);
        if (!$enabled)
        {
            $this->assertFalse($res);
            return;
        }
        $this->assertType('toolib\Authn\DB\Identity', $res);
        $this->assertEquals($res->id(), $username);
        $this->assertEquals($res->getRecord(), User_plain::open($username));
    }

    /**
     * @dataProvider dataUsers
     */
    public function testPlainEnabledMd5($username, $password, $enabled)
    {   
        static $auth = NULL;
        if (!$auth)
            $auth = new Backend(array(
                'query_user' => User_md5::openQuery()
                    ->where('enabled = ?')
                    ->pushExecParam('1')
                    ->where('username = ?'),
                'field_username' => 'username',
                'field_password' => 'password',
                'hash_function' => create_function('$pass, $record','return md5($pass);')
            ));
        $res = $auth->authenticate($username, $password);
        if (!$enabled)
        {
            $this->assertFalse($res);
            return;
        }
        $this->assertType('toolib\Authn\DB\Identity', $res);
        $this->assertEquals($res->id(), $username);
        $this->assertEquals($res->getRecord(), User_md5::open($username));
    }

    public function testResetPlainPwd()
    {
        $auth = new Backend(array(
            'query_user' => User_plain::openQuery()
                    ->where('username = ?'),
            'field_username' => 'username',
            'field_password' => 'password'
        ));

        $identity = $auth->authenticate('user1', 'password1');
        $this->assertType('toolib\Authn\DB\Identity', $identity);
        $this->assertTrue($identity->resetPassword('passwordnew'));

        // Check same password
        $this->assertFalse($auth->authenticate('user1', 'password1'));
        $res = $auth->authenticate('user1', 'password1');

        // Check with new password
        $res = $auth->authenticate('user1', 'passwordnew');
        $this->assertType('toolib\Authn\DB\Identity', $res);
        $this->assertEquals($res->id(), 'user1');
        $this->assertEquals($res->getRecord(), User_plain::open('user1'));

        // Rebuild
        Authn_SampleSchema::destroy();
        Authn_SampleSchema::build();
    }

    public function testResetPlainIdPwd()
    {
        $auth = new Backend(array(
            'query_user' => User_id::openQuery()
                    ->where('username = ?'),
            'field_username' => 'username',
            'field_password' => 'password'
        ));

        $identity = $auth->authenticate('user1', 'password1');
        $this->assertType('toolib\Authn\DB\Identity', $identity);
        $this->assertTrue($identity->resetPassword('passwordnew'));

        // Check same password
        $this->assertFalse($auth->authenticate('user1', 'password1'));
        $res = $auth->authenticate('user1', 'password1');

        // Check with new password
        $res = $auth->authenticate('user1', 'passwordnew');
        $this->assertType('toolib\Authn\DB\Identity', $res);
        $this->assertEquals($res->id(), 'user1');
        $this->assertEquals($res->getRecord(), User_id::open(1));

        // Rebuild
        Authn_SampleSchema::destroy();
        Authn_SampleSchema::build();
    }


    public function testResetMd5Pwd()
    {
        $auth = new Backend(array(
            'query_user' => User_md5::openQuery()
                    ->where('username = ?'),
            'field_username' => 'username',
            'field_password' => 'password',
            'hash_function' => create_function('$pass, $record','return md5($pass);')
        ));

        $identity = $auth->authenticate('user1', 'password1');
        $this->assertType('toolib\Authn\DB\Identity', $identity);
        $this->assertTrue($identity->resetPassword('passwordnew'));

        // Check same password
        $this->assertFalse($auth->authenticate('user1', 'password1'));
        $res = $auth->authenticate('user1', 'password1');

        // Check with new password
        $res = $auth->authenticate('user1', 'passwordnew');
        $this->assertType('toolib\Authn\DB\Identity', $res);
        $this->assertEquals($res->id(), 'user1');
        $this->assertEquals($res->getRecord(), User_md5::open('user1'));

        // Rebuild
        Authn_SampleSchema::destroy();
        Authn_SampleSchema::build();
    }

    public function testResetSha1Pwd()
    {
        $auth = new Backend(array(
            'query_user' => User_sha1::openQuery()
                    ->where('username = ?'),
            'field_username' => 'username',
            'field_password' => 'password',
            'hash_function' => create_function('$pass, $record','return sha1($pass);')
        ));

        $identity = $auth->authenticate('user1', 'password1');
        $this->assertType('toolib\Authn\DB\Identity', $identity);
        $this->assertTrue($identity->resetPassword('passwordnew'));

        // Check same password
        $this->assertFalse($auth->authenticate('user1', 'password1'));
        $res = $auth->authenticate('user1', 'password1');

        // Check with new password
        $res = $auth->authenticate('user1', 'passwordnew');
        $this->assertType('toolib\Authn\DB\Identity', $res);
        $this->assertEquals($res->id(), 'user1');
        $this->assertEquals($res->getRecord(), User_sha1::open('user1'));

        // Rebuild
        Authn_SampleSchema::destroy();
        Authn_SampleSchema::build();
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testMissArgument1()
    {
        $auth = new Backend();
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testMissArgument2()
    {
        $auth = new Backend(array());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testMissArgument3()
    {
        $auth = new Backend(array('model_user' => 'User_plain'));
    }
}
