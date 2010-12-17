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


require_once __DIR__ .  '/../path.inc.php';
use toolib\DB\Record;
use toolib\DB\Connection;

class Users extends Record
{
    static public $table = 'users';
    static public $fields = array(
        'username' => array('pk' => true),
        'password',
        'enabled'
        );
}

class Membership extends Record
{
    static public $table = 'memberships';
    static public $fields = array(
        'username' => array('pk' => true),
        'groupname' => array('pk' => true),
        );
}

//! Create a Sample schema
class Authz_SampleSchema
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
    
    static public $test_groups = array(
        array('user1', 'group13'),
        array('user2', 'group13'),
        array('user3', 'group13'),
        array('user3', 'group34'),
        array('user4', 'group34'),
        array('user4', 'group46'),
        array('user5', 'group46'),
        array('user6', 'group46'),
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
        CREATE TABLE `users` (
            username varchar(15),
            password varchar(255),
            enabled TINYINT DEFAULT 1,
            PRIMARY KEY(username)
        );
        ');

        Connection::query('
        CREATE TABLE `memberships` (
            username varchar(15),
            groupname varchar(255),
            PRIMARY KEY(username, groupname)
        );
        ');

        foreach(self::$test_users as $record)
        {   
            list($username, $password, $enabled) = $record;
            Connection::query("INSERT INTO `users` (username, password, enabled) VALUES " .
                "( '" . Connection::getLink()->real_escape_string($username) . "', " .
                " '" .  Connection::getLink()->real_escape_string($password) . "', " .
                " '" . $enabled . "')");
        }
        
        foreach(self::$test_groups as $record)
        {   
            list($user, $group) = $record;
            Connection::query("INSERT INTO `memberships` (username, groupname) VALUES " .
                "( '" . Connection::getLink()->real_escape_string($user) . "', " .
                " '" .  Connection::getLink()->real_escape_string($group) . "')");

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
