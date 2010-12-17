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

require_once __DIR__ . '/ACL.class.php';
require_once __DIR__ . '/Role/Feeder.class.php';

//! Representation of resource.
class Resource
{
    //! The name of the resource
    protected $name;
    
    //! The parent of this resource.
    protected $parent = null;
    
    //! The Authz_ACL of this resource.
    protected $acl;

    //! Construct a new resource
    /**
     * @param $name The name of this resource.
     * @param $parent 
     * - @b Authz_Resource The parent of this resource.
     * - @b null If this resource has no parent.
     */
    public function __construct($name, $parent = null)
    {
        $this->acl = new ACL();
        
        $this->name = $name;
        
        if (is_object($parent))
            $this->parent = $parent;
    }
    
    //! Get the name of this resource.
    public function getName()
    {
        return $this->name;
    }

    //! Get the parent of this resource.
    public function getParent()
    {
        return $this->parent;
    }
    
    //! Check if this resource has parent.
    public function hasParent()
    {
        return $this->parent !== null;
    }
    
    //! Get the access control list of this resource.
    public function getAcl()
    {
        return $this->acl;
    }
    
    //! Search through role inheritance and resource inheritance for effective ACE
    /**
     * @param $role The name of the role to search for effective ACE.
     * @param $action The action to search for.
     * @param $roles The roles feeder that describes roles inheritance.
     * @param $depth A return value of the ACE's depth.
     *  This value is relative to implementation but it can be used to compare weight of ACEs.
     * @return \toolib\Authz\ACE
     *  - @b Authz_ACE The effective ACE that was found.
     *  - @b null If no ACE was found for criteria.
     *  .
     */
    public function effectiveAce($role, $action, Role\Feeder $roles, & $depth)
    {
    	$matched = array('ace' => null, 'depth' => -1);    
        
        // Search local acl
        if ($ace = $this->acl->effectiveAce($role, $action)) {   
            $matched = array('ace' => $ace, 'depth' => ($ace->isRoleNull()?500:0));

            if (! $ace->isRoleNull()) {
                $depth = $matched['depth'];
                return $ace;
            }
        }

        // Search for role inheritance
        if ($roles->hasRole($role)) {
            foreach($roles->getRole($role)->getParents() as $prole) {
            	$pdepth = -1;
                
                if (($ace = $this->effectiveAce($prole->getName(), $action, $roles, $pdepth)) === null)
                    continue;

                if ($ace->isRoleNull())
                    continue;
                    
                if (($matched['depth'] == -1) || ($pdepth < $matched['depth'])) {
                    $matched['depth'] = 1 + $pdepth;
                    $matched['ace'] = $ace;
                }
            }
        }
        
        // Resolved using role inheritance
        if ($matched['depth'] >= 0) {
            $depth = $matched['depth'];
            return $matched['ace'];
        }
        
        // Search for resource inheritance
        if ($this->hasParent())
            if (($ace = $this->getParent()->effectiveAce($role, $action, $roles, $pdepth)) !== null) {
                $depth = $pdepth + 10000;
                return $ace;
            }

        return null;
    }
}
