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

namespace toolib\Authz;

//! Access Control Entry
class ACE
{

    //! The role this entry is for.
    private $role;
    
    //! The action this entry refers to.
    private $action;
    
    //! The given access.
    private $allowed;
    
    //! Constuct a new ACE
    /**
     * @param $role The role this entry is for.
     * @param $action The action this entry refers to.
     * @param $allowed The access given to the previous tuple.
     */
    public function __construct($role, $action, $allowed)
    {        
        $this->role = $role;
        
        $this->action = $action;
        
        $this->allowed = (boolean) $allowed;
    }
    
    //! Get the role this ace is for.
    public function getRole()
    {
        return $this->role;
    }
    
    //! Check if the role is null (wildcard role)
    public function isRoleNull()
    {
        return $this->role === null;
    }
    
    //! Get the action this entry refers to.
    public function getAction()
    {
        return $this->action;
    }
    
    //! Check if this ace permit access to resource.
    public function isAllowed()
    {
        return $this->allowed;
    }
    
    //! Set the value of access.
    /**
     * @param $allowed A @b boolean allowing or denying access for this tuple.
     */
    public function setAllowed($allowed)
    {
        $this->allowed = (boolean) $allowed;
    }
    
    //! Get a distringuish name hash for this ace
    /**
     * The hash is unique for the @b role, @b action tuple.
     */
    public function getDnHash()
    {   
        // @todo fix security flaw
        // Potential security flaw by hash slam attack.
        // As long as the delimiter can exist inside the role or action someone can craft
        // special role or action to create an overlaping ace.
        return "{$this->role}:{$this->action}";
    }
}
