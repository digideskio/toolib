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


namespace toolib\Authn\Session;
use toolib\Authn\Identity;

require_once __DIR__ . '/../Session.class.php';

/**
 * @brief Use native php sessions to track identity
 */
class Native implements \toolib\Authn\Session
{
    /**
     * @brief The index to use in $_SESSION array for storing identity.
     * @var mixed
     */
    private $session_index;
    
    /**
     * @brief Construct a php native authentication session
     * @param $session_index The index to use inside $_SESSION
     */
    public function __construct($session_index = 'PHPLIBS_AUTHN_SESSION')
    {
        $this->session_index = $session_index;
    }
    
    
    public function setIdentity(Identity $identity, $ttl = null)
    {
        session_regenerate_id();
        $_SESSION[$this->session_index] = $identity;
    }

    public function getIdentity()
    {
        if (!isset($_SESSION[$this->session_index]))
            return false;
        if ($_SESSION[$this->session_index] === null)
            return false;
        return $_SESSION[$this->session_index];
    }


    public function clearIdentity()
    {
        $_SESSION[$this->session_index] = null;
    }
}
