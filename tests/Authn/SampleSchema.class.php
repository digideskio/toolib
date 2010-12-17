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

use toolib\DB\Record;
use toolib\DB\Connection;
require_once __DIR__ .  '/../path.inc.php';

class User_plain extends Record
{
    static public $table = 'users_plain';
    static public $fields = array(
        'username' => array('pk' => true),
        'password',
        'enabled'
        );
}

class User_id extends Record
{
    static public $table = 'users_id';
    static public $fields = array(
        'id' => array('pk' => true, 'ai' => true),
        'username',
        'password',
        'enabled'
        );
}
class User_md5 extends Record
{
    static public $table = 'users_md5';
    static public $fields = array(
        'username' => array('pk' => true),
        'password',
        'enabled'
        );
}

class User_sha1 extends Record
{
    static public $table = 'users_sha1';
    static public $fields = array(
        'username' => array('pk' => true),
        'password',
        'enabled'
        );
}

//! Create a Sample schema
class Authn_SampleSchema
{
    static public $conn_params = array(
        'host' => 'localhost',
        'username' => 'root',
        'password' => 'root',
        'schema' => 'phplibs-unittest-auth'
        );

    static public $test_users = array(
        array('user1', 'password1', 1),
        array('user2', 'password2 #', 1),
        array('user3', 'Pword1 #', 1),
        array('user4', ' ', 1),
        array('user5', 'password1', 0),
        array('user6', 'password1', 0)
    );

    static public function connect($delayed = true)
    {
        return Connection::connect(
            self::$conn_params['host'],
            self::$conn_params['username'],
            self::$conn_params['password'],
            self::$conn_params['schema'],
            $delayed
        );
    }

    static public function insert_user($table, $username, $password, $enabled)
    {   
        Connection::query("INSERT INTO {$table} (username, password, enabled) VALUES " .
            "( '" . Connection::getLink()->real_escape_string($username) . "', " .
            " '" .  Connection::getLink()->real_escape_string($password) . "', " .
            " '" . $enabled . "')");
    }

    static public function build()
    {   
        self::destroy();
        Connection::connect(
            self::$conn_params['host'],
            self::$conn_params['username'],
            self::$conn_params['password'],
            'mysql'
        );

        Connection::query('CREATE DATABASE IF NOT EXISTS `' . self::$conn_params['schema']. '` ;');
        Connection::connect(
            self::$conn_params['host'],
            self::$conn_params['username'],
            self::$conn_params['password'],
            self::$conn_params['schema']
        );

        // Create schema
        Connection::query('
        CREATE TABLE `users_plain` (
            username varchar(15),
            password varchar(255),
            enabled TINYINT DEFAULT 1,
            PRIMARY KEY(username)
        );
        ');

        Connection::query('
        CREATE TABLE `users_id` (
            id INT auto_increment NOT NULL,
            username varchar(15),
            password varchar(255),
            enabled TINYINT DEFAULT 1,
            PRIMARY KEY(id),
            UNIQUE KEY(username)
        );
        ');

        Connection::query('
        CREATE TABLE `users_md5` (
            username varchar(15),
            password CHAR(32),
            enabled TINYINT DEFAULT 1,
            PRIMARY KEY(username)
        );
        ');

        Connection::query('
        CREATE TABLE `users_sha1` (
            username varchar(15),
            password CHAR(40),
            enabled TINYINT DEFAULT 1,
            PRIMARY KEY(username)
        );
        ');

        foreach(self::$test_users as $record)
        {   
            list($username, $password, $enabled) = $record;
            self::insert_user('users_plain', $username, $password, $enabled);
        }

        foreach(self::$test_users as $record)
        {   
            list($username, $password, $enabled) = $record;
            self::insert_user('users_id', $username, $password, $enabled);
        }

        foreach(self::$test_users as $record)
        {   
            list($username, $password, $enabled) = $record;
            self::insert_user('users_md5', $username, md5($password), $enabled);
        }

        foreach(self::$test_users as $record)
        {   
            list($username, $password, $enabled) = $record;
            self::insert_user('users_sha1', $username, sha1($password), $enabled);
        }
    }

    static public function destroy()
    {
        Connection::connect(
            self::$conn_params['host'],
            self::$conn_params['username'],
            self::$conn_params['password'],
            'mysql'
        );
        @Connection::query('DROP DATABASE `' . self::$conn_params['schema']. '`;');
        Connection::disconnect();
    }
}
