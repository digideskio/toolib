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


/**
 * @brief Database support package.
 */
namespace toolib\Authn\DB;

require_once __DIR__ . '/../Backend.class.php';
require_once __DIR__ . '/Identity.class.php';

/**
 * @brief Implementation for database backend
 * 
 * Authentication based on \toolib\DB package.
 * The database models must first be declared before using this class.
 */
class Backend implements \toolib\Authn\Backend
{
    /**
     * @brief The normalized options of this instance.
     * @var \toolib\Options
     */
    private $options;

    /**
     * @brief The model query object that will be used for authentication.
     * @var array
     */
    private $model_query = array();

    /**
     * @brief Get the options of this instance.
     * @return \toolib\Options
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @brief Create an instance of this backend
     * @param $options An associative array of options.
     *  - @b query_user [@b *] A DB_RecordModelQuery prepared to select records based on username.
     *  - @b field_username [@b *] The field that is the username.
     *  - @b field_password [@b *] The field that is the password.
     *  - @b hash_function The hash function to be used on password, or NULL for plain.
     *  .
     *  [@b *] mandatory field.
     * @throws InvalidArgumentException If one of the mandatory fields is missing.
     */
    public function __construct($options = array())
    {
    	$this->options = new \toolib\Options(
    		$options,
    		array('hash_function' => null),
    		array('query_user', 'field_username', 'field_password')
    	);
    }
    
    public function authenticate($username, $password)
    {
        // Get user
        $records = $this->options['query_user']->execute($username);
        if (count($records) !== 1)
            return false;

        // Hash-salt function
        if ($this->options['hash_function'] !== NULL)
            $password = call_user_func($this->options['hash_function'], $password, $records[0]);

        // Check password
        if ($password !== $records[0]->{$this->options['field_password']})
            return false;

        // Succesfull
        return new Identity($records[0]->{$this->options['field_username']}, $this, $records[0]);
    }

    /**
     * @brief Reset the password of an identity
     * @param $id The username of the identity.
     * @param $new_password The new effective password of identity after reset.
     * @return
     *  - @b true if the password was reset.
     *  - @b false on any error.
     */
    public function resetPassword($id, $new_password)
    {   
        $records = $this->options['query_user']->execute($id);
            
        if ((!$records) || (count($records) !== 1))
            return false;
        $user = $records[0];

        // Hash-salt function
        if ($this->options['hash_function'] !== null)
            $new_password = call_user_func($this->options['hash_function'], $new_password, $user);

        $user->{$this->options['field_password']} = $new_password;
        return $user->update();
    }
}
