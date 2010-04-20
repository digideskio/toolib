<?php

require_once __DIR__ .  '/../path.inc.php';

//! Create a Sample schema
class SampleSchema
{
    static public $conn_params = array(
        'host' => 'localhost',
        'username' => 'root',
        'password' => 'root',
        'schema' => 'phplibs-unittest'
    );
    
    static public function connect($delayed = true)
    {
        return DB_Conn::connect(
            self::$conn_params['host'],
            self::$conn_params['username'],
            self::$conn_params['password'],
            self::$conn_params['schema'],
            $delayed
        );            
    }

    static public function build()
    {   self::destroy();
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
            id INT auto_increment,
            forum_id INT NOT NULL,
            title varchar(255),
            date DATETIME,
            PRIMARY KEY(id),
            FOREIGN KEY(forum_id) REFERENCES `forums`(`id`)
        );
        ');

        DB_Conn::query('
        CREATE TABLE `posts` (
            id INT auto_increment,
            thread_id INT NOT NULL,
            post MEDIUMTEXT,
            imaged MEDIUMBLOB,
            poster varchar(30),
            date DATETIME,
            PRIMARY KEY(id),
            FOREIGN KEY(thread_id) REFERENCES `threads`(`id`)
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
        INSERT INTO threads (forum_id, title, date) VALUES
            (1, 'First thread', NOW()),
            (1, 'Second thread', NOW()),
            (1, 'Third thread', NOW()),
            (2, 'First thread', NOW()),
            (2, 'Second thread', NOW()),
            (3, 'First thread', NOW())
        ");

        DB_Conn::query("
        INSERT INTO posts (thread_id, post, poster, date) VALUES
            (1, 'Bla bla bla post', 'sebas', NOW()),
            (1, 'Second post', 'sebas', NOW()),
            (1, 'Third post', 'sebas', NOW()),
            (2, 'First post', 'sebas', NOW()),
            (2, 'Second post', 'sebas', NOW()),
            (3, 'First post', 'sebas', NOW())
        ");

        $stmt = DB_conn::get_link()->prepare(
            'INSERT INTO posts (thread_id, post, poster, date) VALUES (2, ?, \'long\', NOW())');
        $big_post = str_repeat('1234567890', 100000);
        $null = null;
        $stmt->bind_param('b', $null);
        $stmt->send_long_data(0, $big_post);
        $stmt->execute();
        $stmt->close();

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
?>
