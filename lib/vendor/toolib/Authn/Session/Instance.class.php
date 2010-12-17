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

//! Track identity inside the instance of this object
class Instance implements \toolib\Authn\Session
{
    //! The session identity
    private $online_identity;
    
    public function __construct()
    {
        $this->online_identity = false;
    }
        
    public function setIdentity(Identity $identity, $ttl = null)
    {
        $this->online_identity = $identity;
    }

    public function getIdentity()
    {
        return $this->online_identity;
    }
    
    public function clearIdentity()
    {
        $this->online_identity = false;
    }
}
