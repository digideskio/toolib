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


require_once dirname(__FILE__) .  '/../path.inc.php';

//! Create a Sample schema
class SampleSchema
{
    static public $conn_params = array(
        'host' => 'localhost',
        'username' => 'root',
        'password' => 'root',
        'schema' => 'phplibs-unittest'
        );

        static public function connect($delayed_prep = true, $delayed_conn = false)
        {
            return DB_Conn::connect(
            self::$conn_params['host'],
            self::$conn_params['username'],
            self::$conn_params['password'],
            self::$conn_params['schema'],
            $delayed_prep,
            $delayed_conn
            );
        }

        static public function build()
        {   
            self::destroy();
            DB_Conn::connect(
                self::$conn_params['host'],
                self::$conn_params['username'],
                self::$conn_params['password'],
                'mysql'
            );
            DB_Conn::query('CREATE DATABASE IF NOT EXISTS `' . self::$conn_params['schema']. '` ;');
            DB_Conn::connect(
                self::$conn_params['host'],
                self::$conn_params['username'],
                self::$conn_params['password'],
                self::$conn_params['schema']
            );
            DB_Conn::get_link()->autocommit(false);

            // Create schema
            DB_Conn::query('
                CREATE TABLE `users` (
                    username varchar(15),
                    password varchar(255),
                    enabled TINYINT DEFAULT 1,
                    PRIMARY KEY(username)
                );        
            ');

            DB_Conn::query('
                CREATE TABLE `groups` (
                    groupname varchar(15),
                    enabled TINYINT DEFAULT 1,
                    PRIMARY KEY(groupname)
                );        
            ');

            DB_Conn::query('
                CREATE TABLE `group_members` (
                    username varchar(15),
                    groupname varchar(15),
                    PRIMARY KEY(username, groupname),
                    FOREIGN KEY (`username`) REFERENCES `users`(`username`),
                    FOREIGN KEY (`groupname`) REFERENCES `groups`(`groupname`)
                );        
            ');

            DB_Conn::query('
                CREATE TABLE `forums` (
                    id INT auto_increment,
                    title varchar(255),
                    PRIMARY KEY(id)
                );
            ');

            DB_Conn::query('
                CREATE TABLE `threads` (
                    thread_id INT auto_increment,
                    forum_id INT NOT NULL,
                    title varchar(255),
                    `datetime` DATETIME,
                    PRIMARY KEY(thread_id),
                    FOREIGN KEY(forum_id) REFERENCES `forums`(`id`)
                );
            ');

            DB_Conn::query('
                CREATE TABLE `posts` (
                    id INT auto_increment,
                    thread_id INT NOT NULL,
                    posted_text MEDIUMTEXT,
                    image MEDIUMBLOB,
                    poster varchar(30),
                    date DATETIME,
                    PRIMARY KEY(id),
                    FOREIGN KEY(thread_id) REFERENCES `threads`(`thread_id`)
                );
            ');

            DB_Conn::query("
            INSERT INTO users (username) VALUES
                ('admin'),
                ('user1'),
                ('user2'),
                ('user3'),
                ('user4'),
                ('user5'),
                ('user6');
            ");

            DB_Conn::query("
            INSERT INTO groups (groupname) VALUES
                ('group1'),
                ('group2'),
                ('group3'),
                ('group4');
            ");

            DB_Conn::query("
            INSERT INTO group_members (groupname, username) VALUES
                ('group1', 'user1'),
                ('group1', 'user2'),
                ('group1', 'user3'),
                ('group2', 'user3'),
                ('group2', 'user4'),
                ('group3', 'user4'),
                ('group3', 'user5'),
                ('group3', 'user6');
            ");


            DB_Conn::query("
            INSERT INTO forums (title) VALUES
                ('The first'),
                ('The second'),
                ('The third');
            ");
            DB_Conn::query("
            INSERT INTO threads (forum_id, title, `datetime`) VALUES
                (1, 'First thread', NOW()),
                (1, 'Second thread', NOW()),
                (1, 'Third thread', NOW()),
                (2, 'First thread', NOW()),
                (2, 'Second thread', NOW()),
                (3, 'First thread', NOW())
            ");

            DB_Conn::query("
            INSERT INTO posts (thread_id, posted_text, poster, date) VALUES
                (1, 'Bla bla bla post', 'sebas', NOW()),
                (1, 'Second post', 'sebas', NOW()),
                (1, 'Third post', 'sebas', NOW()),
                (2, 'First post', 'sebas', NOW()),
                (2, 'Second post', 'sebas', NOW()),
                (3, 'First post', 'sebas', NOW())
            ");

            $stmt = DB_conn::get_link()->prepare(
                'INSERT INTO posts (thread_id, posted_text, poster, date) VALUES (2, ?, \'long\', NOW())');
            $big_post = str_repeat('1234567890', 100000);
            $null = null;
            if (!$stmt->bind_param('s', $null))
            	die($stmt->error);
            if (!$stmt->send_long_data(0, $big_post))
            	die($stmt->error);
            if (!$stmt->execute())
            	;//die($stmt->error);
            $stmt->close();
            
            DB_Conn::get_link()->autocommit(true);
        }

        static public function destroy()
        {
            DB_Conn::connect(
                self::$conn_params['host'],
                self::$conn_params['username'],
                self::$conn_params['password'],
                'mysql'
            );
            @DB_Conn::query('DROP DATABASE `' . self::$conn_params['schema']. '`;');
            DB_Conn::disconnect();

        }
}

