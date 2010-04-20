<?php

require_once __DIR__ .  '/../path.inc.php';

class Forum extends DB_Record
{
    static public $table = 'forums';

    static public $fields = array(
        'id' => array('pk' => true, 'ai' => true),
        'title' => array('default' => 'noname')
    );
}

class Thread extends DB_Record
{
    static public $table = 'threads';

    static public $fields = array(
        'id' => array('pk' => true, 'ai' => true),
        'forum_id' => array('fk' => 'Forum'),
        'title',
        'datetime' => array('type' => 'datetime')
    );
}

class Post extends DB_Record
{
    static public $table = 'posts';

    static public $fields = array(
        'id' => array('pk' => true, 'ai' => true),
        'thread_id' => array('fk' => 'Thread'),
        'post',
        'image' => array('type' => 'serialized'),
        'poster',
        'date' => array('type' => 'datetime')
    );
}

class User extends DB_Record
{
    static public $table = 'users';

    static public $fields = array(
        'username' => array('pk' => true),
        'password',
        'enabled'
    );
}

class Group extends DB_Record
{
    static public $table = 'groups';

    static public $fields = array(
        'groupname' => array('pk' => true),
        'enabled'
    );
}

class Group_Members extends DB_Record
{
    static public $table = 'group_members';

    static public $fields = array(
        'username' => array('pk' => true, 'fk' => 'User'),
        'groupname' => array('pk' => true, 'fk' => 'Group')
    );
}
?>
