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
 * @brief Runtime non-persistent support package for authorization
 */
namespace toolib\Authz\Instance;

require_once __DIR__ . '/../RoleFeeder.class.php';
require_once __DIR__ . '/Role.class.php';

/**
 * @brief A role feeder with life-cycle of object instance.
 */
class RoleFeeder implements \toolib\Authz\RoleFeeder
{
    /**
     * @brief Array with all roles
     * @var array
     */
    private $roles = array();
    
    /**
     * @brief Add a new role in feeder
     * @param $name The name of the role.
     * @param $parents
     *  - @b null If the role has no parents.
     *  - @b string The name of the single parent.
     *  - @b array Array of parents names.
     *  .
     * @throws \InvalidArgumentException If there is already role with that name.
     * @throws \InvalidArgumentException If at least one parent is unknown.
     * @return \toolib\Authz\Instance\Role
     */
    public function addRole($name, $parents = null)
    {
        // Check for duplication
        if (isset($this->roles[$name]))
            throw new \InvalidArgumentException("There is already role with name \"{$name}\"");
        
        // Check parents
        if ($parents ===  null)
            $parents = array();
        else if(! is_array($parents))
            $parents = array($parents);

        // Validate and objectify parents
        foreach($parents as $idx => $p) {
            if (! ($prole = $this->getRole($p)))
                throw new \InvalidArgumentException("Cannot add role that depends on unknown role \"{$p}\"");
            $parents[$idx] = $prole;
        }
            
        return $this->roles[$name] = new Role($name, $parents);
    }
    
    /**
     * @brief Remove a role from the feeder
     * @param $name The name of the role to remove.
     */
    public function removeRole($name)
    {
        if (isset($this->roles[$name]))
            unset($this->roles[$name]);
    }
    
    public function getRole($name)
    {
        if (isset($this->roles[$name]))
            return $this->roles[$name];
        return false;
    }
    
    public function hasRole($name)
    {
        return isset($this->roles[$name]);
    }
}

