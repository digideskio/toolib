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


namespace toolib\Authn;

require_once __DIR__ . '/Identity.class.php';

/**
 * @brief Interface for authentication session storage
 */
interface Session
{
    /**
     * @brief Set the current session identity
     * @param $identity The identity object to save
     * @param $ttl 
     *  - Time in seconds that this identity will be online.
     *  - @b null if you dont want to declare it explicitly for this identity.
     *  .
     */
    public function setIdentity(Identity $identity, $ttl = null);

    /**
     * @brief Get the current session identity
     * @return \toolib\Authn\Identity
     *  - @b Authn_Identity object if one is signed on.
     *  - @b false If no identity online.
     */
    public function getIdentity();

    /**
     * @brief Clear any identity from this session
     */
    public function clearIdentity();
}
