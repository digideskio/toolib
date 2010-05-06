<?php

class User extends DB_Record
{
    static public $table = 'users';

    static public $fields = array(
        'user' => array('pk' => true),
        'password',
        'is_enabled'
        );
}

?>
