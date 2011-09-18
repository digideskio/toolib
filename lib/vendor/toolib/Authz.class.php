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

namespace toolib;

require_once __DIR__ . '/Authz/ResourceList.class.php';
require_once __DIR__ . '/Authz/Instance/RoleFeeder.class.php';


/**
 * @brief Static authorization realm
 * 
 * Authz was created to provide a singleton that just works in the majority
 * of cases with little customization. You can archive the same
 * functionality with non singleton interface using directly ResourceList()
 * and one Authz_Role_Feeder implementation.
 */
class Authz
{
    /**
     * @brief The role feeder that is used 
     * @var \toolib\Authz\RoleFeeder
     */
    static private $role_feeder = null;

    /**
     * @brief The resource list that is used
     * @var \toolib\Authz\ResourceList
     */
    static private $resource_list = null;
    
    /**
     * @brief Function to retrieve the current role
     * @var callable
     */
    static private $current_role_func = null;
    
    /**
     * @brief Prohibit instantiation of this class
     */
    final private function __construct()
    {
    }
    
    /**
     * @brief Get the current ResourceList used by Authz
     * @return \toolib\Authz\ResourceList
     */
    static public function getResourceList()
    {
        if (self::$resource_list === null)  
            self::$resource_list = new Authz\ResourceList();
        return self::$resource_list;
    }
    
    /**
     * @brief Set a new ResourceList for Authz to use.
     */
    static public function setResourceList(Authz\ResourceList $list)
    {
        self::$resource_list = $list;
    }
    
    /**
     * @brief Get the current Authz_Role_Feeder used by Authz 
     * @return \toolib\Authz\RoleFeeder
     */
    static public function getRoleFeeder()
    {
        return self::$role_feeder;
    }
    
    /**
     * @brief Set a new Authz=Role\Feeder for Authz to use.
     */
    static public function setRoleFeeder(Authz\RoleFeeder $feeder)
    {
        self::$role_feeder = $feeder;
    }
    
    /**
     * @brief Get the callback that retrieves the current role name
     * @return callable
     */
    static public function getCurrentRoleFunc()
    {
        if (self::$current_role_func === null)
            self::$current_role_func = create_function('', 
                ' if (!Authn_Realm::hasIdentity())
                    return null;
                  return Authn_Realm::getIdentity()->id();');
        return self::$current_role_func;
    }
    
    /**
     * @brief Get the current role name based on callback
     * @return \toolib\Authz\Role
     */
    static public function getCurrentRole()
    {   
        return call_user_func(self::getCurrentRoleFunc());
    }

    /**
     * @brief Set the callback function tha returns the current role name
     * @param $callable Any type of object that can be called with call_user_func()
     */
    static public function setCurrentRoleFunc($callable)
    {
        self::$current_role_func = $callable;
    }
    
    /**
     * @brief Search and return a resource in current resource list.
     * @param $resource
     *  - @b string The name of the resource class
     *  - @b array A tuple of name and instance id of a resource instance.
     *  .
     * @return \toolib\Authz\Resource
     *  - @b \\toolib\\Authz\\Resource The found resource object.
     *  - @b false If the resource was not found.
     *  .
     */
    static public function getResource($resource)
    {
        if (is_array($resource))
            $res = self::getResourceList()->getResource($resource[0], $resource[1]);
        else
            $res = self::getResourceList()->getResource($resource);
        return $res;
    }
    
    /**
     * @brief Shortcut to add an @b allow Ace in the Acl of a resource
     * @param $resource
     *  - @b string The name of the resource class
     *  - @b array A tuple of name and instance id of a resource instance.
     *  .
     * @param $role
     *  - @b The name of the role.
     *  - @b null If you want to add wildcard role.
     *  .
     * @param $action The name of the action.
     */
    static public function allow($resource, $role, $action)
    {
        $res = self::getResource($resource);
        if (!$res)
            throw new \InvalidArgumentException('Cannot find resource with name "' . 
                (is_array($resource)?$resource[0]:$resource) . '"');

        return $res->getAcl()->allow($role, $action);
    }
    
    /**
     * @brief Shortcut to add an @b deny Ace in the Acl of a resource
     * @param $resource
     *  - @b string The name of the resource class
     *  - @b array A tuple of name and instance id of a resource instance.
     *  .
     * @param $role
     *  - @b The name of the role.
     *  - @b null If you want to add wildcard role.
     *  .
     * @param $action The name of the action.
     */
    static public function deny($resource, $role, $action)
    {
        $res = self::getResource($resource);
        if (!$res)
            throw new \InvalidArgumentException('Cannot find resource with name "' . 
                (is_array($resource)?$resource[0]:$resource) . '"');

        return $res->getAcl()->deny($role, $action);
    }


    /**
     * @brief Search if an action by current role on a specific resource is permitted.
     * 
     * The current role is retrieve using the callback defined by setCurrentRoleFunc().
     * @param $resource
     *  - @b string The name of the resource class
     *  - @b array A tuple of name and instance id of a resource instance.
     *  .
     * @param $action The name of the action.
     * @return
     *  - @b true If the most effective ACE is permitting it.
     *  - @b false If the ACE denied it or there is no effective ACE.
     *  .
     */
    static public function isAllowed($resource, $action)
    {   
        return self::isRoleAllowedTo(self::getCurrentRole(), $resource, $action);
    }
    
    /**
     * @brief Search if a role on a resource is permitted to do an action.
     * @param $resource
     *  - @b string The name of the resource class
     *  - @b array A tuple of name and instance id of a resource instance.
     *  .
     * @param $role
     *  - @b The name of the role.
     *  - @b null If you want to add wildcard role.
     *  .
     * @param $action The name of the action.
     * @return
     *  - @b true If the most effective ACE is permitting it.
     *  - @b false If the ACE denied it or there is no effective ACE.
     *  .
     */
    static public function isRoleAllowedTo($role, $resource, $action)
    {   
        $res = self::getResource($resource);

        if (!$res)
            throw new \InvalidArgumentException('Cannot find resource with name "' . 
                (is_array($resource)?$resource[0]:$resource) . '"');

        
        if (($ace = $res->effectiveAce($role, $action, self::getRoleFeeder(), $depth)) === null)
            return false;

        return $ace->isAllowed();
    }
}
