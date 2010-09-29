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
        'id' => array('sqlfield' => 'thread_id', 'pk' => true, 'ai' => true),
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
        'post' => array('sqlfield' => 'posted_text'),
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

Forum::one_to_many('Thread', 'forum', 'threads');
Thread::one_to_many('Post', 'thread', 'posts');
User::many_to_many('Group', 'Group_Members', 'users', 'groups');
?>
