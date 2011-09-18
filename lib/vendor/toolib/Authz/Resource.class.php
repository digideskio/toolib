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
 * @brief Authorization functionalities package
 */
namespace toolib\Authz;

require_once __DIR__ . '/Acl.class.php';
require_once __DIR__ . '/RoleFeeder.class.php';


/**
 * @brief Representation of resource.
 */
class Resource
{
    /**
     * @brief The name of the resource
     * @var string
     */
    protected $name;
    
    /**
     * @brief The parent of this resource.
     * @var \toolib\Authz\Resource
     */
    protected $parent = null;
    
    /**
     * @brief The Acl of this resource.
     * @var \toolib\Authz\Acl
     */
    protected $acl;

    /**
     * @brief Construct a new resource
     * @param $name The name of this resource.
     * @param $parent \toolib\Authz\Resource 
     * - @b The parent of this resource.
     * - @b null If this resource has no parent.
     */
    public function __construct($name, $parent = null)
    {
        $this->acl = new ACL();
        
        $this->name = $name;
        
        if (is_object($parent))
            $this->parent = $parent;
    }
    
    /**
     * @brief Get the name of this resource.
     */
    public function getName()
    {
        return $this->name;
    }

    
    /**
     * @brief Get the parent of this resource.
     * @return \toolib\Authz\Resource
     */
    public function getParent()
    {
        return $this->parent;
    }
    
    /**
     * @brief Check if this resource has parent.
     * @return boolean
     */
    public function hasParent()
    {
        return $this->parent !== null;
    }
    
    /**
     * @brief Get the access control list of this resource.
     * @return \toolib\Authz\Acl
     */
    public function getAcl()
    {
        return $this->acl;
    }
    
    /**
     * @brief Search through role inheritance and resource inheritance for effective ACE
     * @param $role The name of the role to search for effective ACE.
     * @param $action The action to search for.
     * @param $roles The roles feeder that describes roles inheritance.
     * @param $depth A return value of the ACE's depth.
     *  This value is relative to implementation but it can be used to compare weight of ACEs.
     * @return \toolib\Authz\Ace
     *  - @b \\toolib\\Authz\\Ace The effective ACE that was found.
     *  - @b null If no ACE was found for criteria.
     *  .
     */
    public function effectiveAce($role, $action, RoleFeeder $roles, & $depth)
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
