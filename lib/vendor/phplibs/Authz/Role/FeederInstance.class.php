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


require_once dirname(__FILE__) . '/Feeder.class.php';
require_once dirname(__FILE__) . '/Instance.class.php';

class Authz_Role_FeederInstance implements Authz_Role_Feeder
{
    private $roles = array();
    
    public function add_role($name, $parents = null)
    {
        // Check for duplication
        if (isset($this->roles[$name]))
            throw new InvalidArgumentException("There is already role with name \"{$name}\"");
        
        // Check parents
        if ($parents ===  null)
            $parents = array();
        else if(! is_array($parents))
            $parents = array($parents);
            
        foreach($parents as $p)
            if (! $this->has_role($p))
                throw new InvalidArgumentException("Cannot add role that depends on unknown role \"{$p}\"");
            
        return $this->roles[$name] = new Authz_Role_Instance($name, $parents);
    }
    
    public function remove_role($name)
    {
        if (isset($this->roles[$name]))
            unset($this->roles[$name]);
    }
    
    public function get_role($name)
    {
        if (isset($this->roles[$name]))
            return $this->roles[$name];
        return false;
    }
    
    public function has_role($name)
    {
        return isset($this->roles[$name]);
    }
}

?>
