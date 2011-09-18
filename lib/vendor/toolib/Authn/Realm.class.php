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
 * @brief Authentication functionalities package
 */
namespace toolib\Authn;
use toolib\EventDispatcher;

require_once __DIR__ . '/Identity.class.php';
require_once __DIR__ . '/Backend.class.php';
require_once __DIR__ . '/Session.class.php';
require_once __DIR__ . '/Session/Native.class.php';

/**
 * @brief Static singleton authentication realm
 */
class Realm
{
    /**
     *  The authentication backend that will be used
     * @var \toolib\Authn\Backend
     */
    static private $backend = null;

    /**
     * @brief The session storage that will be used
     * @var \toolib\Authn\Session
     */
    static private $session = null;

    /**
     * The event dispatcher for events
     * @var \toolib\EventDispatcher
     */
    static private $event_dispatcher = null;

    /**
     * @brief Set the authentication backend of the realm
     * @param $backend Any valid Backend implementation.
     */
    static public function setBackend(\toolib\Authn\Backend $backend)
    {   
        self::$backend = $backend;
    }

    /**
     * @brief Get the current authentication backend.
     * @return \toolib\Authn\Backend
     */
    static public function getBackend()
    {   
        return self::$backend;
    }

    /**
     * @brief Set the current session storage engine.
     * @param $session Any valid Session implementation.
     */
    static public function setSession(\toolib\Authn\Session $session)
    {   
        self::$session = $session;
    }

    /**
     * @brief Get the current storage session.
     * @return \toolib\Authn\Session
     */
    static public function getSession()
    {
        return self::$session;
    }

    /**
     * @brief The events of this object.
     * 
	 * Events are announced through an EventDispatcher object. The following
	 * events are valid:
	 *  - @b auth.successful: A successful authentication took place.
	 *  - @b auth.error: An identity authentatication failed.
	 *  - @b ident.clear: The current authenticated identity was cleared.
	 * .
	 * @return \toolib\EventDispatcher
     */
    static public function events()
    {
    	if (self::$event_dispatcher === null)
            self::$event_dispatcher = new EventDispatcher(array(
                'auth.successful',
                'auth.error',
                'ident.clear'
            ));
        return self::$event_dispatcher;
    }

    /**
     * @brief Check if it has an authenticated identity
     * @return boolean
     *  - @b true if there is an authenticated identity on this realm.
     *  - @b false if the current user is anonymous.
     */
    static public function hasIdentity()
    {   
        return (self::$session->getIdentity() != false);
    }

    /**
     * @brief Get current authenticated identity
     * @return
     *  - @b Identity object of the authenticated identity.
     *  - @b false If there is no authenticated identity.
     *  @return \toolib\Authn\Identity
     */
    static public function getIdentity()
    {   
        return self::$session->getIdentity();
    }

    /**
     * @brief Clear current authenticated identity
     */
    static public function clearIdentity()
    {
        $identity = self::hasIdentity();
        if (!$identity)
            return false;

        self::events()->notify('ident.clear', array('identity' => $identity));
        
        return self::$session->clearIdentity();
    }

    /**
     * @brief Authenticate a (new) identity on this realm
     * @param $username The username of the identity
     * @param $password The password of identity
     * @param $ttl
     *  - An explicit declaration of expiration time for this authentication.
     *  - @b null if you want to follow the Session default policy.
     */
    static public function authenticate($username, $password, $ttl = null)
    {
        if (!self::$backend)
            return false;

        // Clear previous one
        if (self::hasIdentity())
            self::clearIdentity();

        $id = self::$backend->authenticate($username, $password);
        if (! ($id instanceof Identity)) {   
            self::events()->notify('auth.error', array('username' => $username, 'password' => $password));
            return false;
        }
        self::events()->notify('auth.successful', array('username' => $username, 'password' => $password));

        // Save session
        self::$session->setIdentity($id, $ttl);
        return $id;
    }
}

// Default session storage is set to native
Realm::setSession(new Session\Native());
