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


class Authz_ACE
{
    private $role;
    
    private $action;
    
    private $allowed;
    
    public function __construct($role, $action, $allowed)
    {        
        $this->role = $role;
        
        $this->action = $action;
        
        $this->allowed = (boolean) $allowed;
    }
    
    public function get_role()
    {
        return $this->role;
    }
    
    public function is_role_null()
    {
        return $this->role === null;
    }
    
    public function get_action()
    {
        return $this->action;
    }
    
    public function is_allowed()
    {
        return $this->allowed;
    }
    
    public function set_allowed($allowed)
    {
        $this->allowed = (boolean) $allowed;
    }
    
    public function get_dn_hash()
    {   
        // @todo fix security flaw
        // Potential security flaw by hash slam attack.
        // As long as the delimiter can exist inside the role or action someone can craft
        // special role or action to create an overlaping ace.
        return "{$this->role}:{$this->action}";
    }
}
?>
