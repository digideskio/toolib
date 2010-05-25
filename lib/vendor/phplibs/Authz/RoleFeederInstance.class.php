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


require_once dirname(__FILE__) . '/RoleFeeder.class.php';
require_once dirname(__FILE__) . '/Role.class.php';

class Authz_RoleFeederInstance implements Authz_RoleFeeder
{
    private $roles = array();
    
    public function add_role(Authz_Role $role)
    {
        // Check for duplication
        if (isset($this->roles[$role->get_name()]))
            throw new InvalidArgumentException("There is already role with name \"{$role->get_name()}\"");
        
        // Check for broken dependency
        foreach($role->get_parents() as $p)
            if (! $this->has_role($p))
                throw new InvalidArgumentException("Cannot add role that depends on unknown role \"{$p}\"");
            
        $this->roles[$role->get_name()] = $role;
    }
    
    public function remove_role($role_name)
    {
        if (isset($this->roles[$role_name]))
            unset($this->roles[$role_name]);
    }
    
    public function get_role($role_name)
    {
        if (isset($this->roles[$role_name]))
            return $this->roles[$role_name];
        return false;
    }
    
    public function has_role($role_name)
    {
        return isset($this->roles[$role_name]);
    }
}

?>
