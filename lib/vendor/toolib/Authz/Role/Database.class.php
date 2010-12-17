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


namespace toolib\Authz\Role;

require_once __DIR__ . '/../Role.class.php';

//! Implementation of Authz_Role for Authz_Role_FeederDatabase
class Database implements \toolib\Authz\Role
{
    //! Options of database communication
    protected $options;
    
    //! The name of this role
    protected $name;
    
    //! Construct a new role
    /**
     * @param $name The name of the role.
     * @param $options Normalized options given by FeederDatabase
     */
    public function __construct($name, $options)
    {
        $this->name = $name;
        $this->options = $options;
    }
    
    //! Check if this role have info for parents location
    protected function hasParentsAbility()
    {
        if (($this->options === null) || ($this->options['parents_query'] === null))
            return false;
        return true;
    }
    
    public function getName()
    {
        return $this->name;
    }
        
    public function getParents()
    {
        if (! $this->hasParentsAbility())
            return array();
            
        $result = $this->options['parents_query']->execute($this->getName());

        $parents = array();
        foreach($result as $record) {   
            $parent_name = $record->{$this->options['parent_name_field']};
            if ($this->options['parent_name_filter_func'])
                $parent_name = call_user_func($this->options['parent_name_filter_func'], $parent_name);
            $parents[$parent_name] = new Database(
                $parent_name , null);
        }

        return $parents;
    }

    public function hasParent($name)
    {      
        return array_key_exists($name, $this->getParents());
    }
    
    public function getParent($name)
    {   
        if (array_key_exists($name, $parents = $this->getParents()))
            return $parents[$name];

        return false;
    }
}
