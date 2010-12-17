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
require_once __DIR__ . '/Authz/Role/FeederInstance.class.php';

//! Static authorization realm
/**
 * Authz was created to provide a singleton that just works in the majority
 * of cases with little customization. You can archive the same
 * functionality with non singleton interface using directly ResourceList()
 * and one Authz_Role_Feeder implementation.
 */
class Authz
{
    //! The role feeder that is used
    static private $role_feeder = null;

    //! The resource list that is used
    static private $resource_list = null;
    
    //! Function to retrieve the current role
    static private $current_role_func = null;
    
    //! Prohibit instantiation of this class
    final private function __construct()
    {
    }
    
    //! Get the current ResourceList used by Authz
    static public function getResourceList()
    {
        if (self::$resource_list === null)  
            self::$resource_list = new Authz\ResourceList();
        return self::$resource_list;
    }
    
    //! Set a new ResourceList for Authz to use.
    static public function setResourceList(Authz\ResourceList $list)
    {
        self::$resource_list = $list;
    }
    
    //! Get the current Authz_Role_Feeder used by Authz
    static public function getRoleFeeder()
    {
        return self::$role_feeder;
    }
    
    //! Set a new Authz=Role\Feeder for Authz to use.
    static public function setRoleFeeder(Authz\Role\Feeder $feeder)
    {
        self::$role_feeder = $feeder;
    }
    
    //! Get the callback that retrieves the current role name
    static public function getCurrentRoleFunc()
    {
        if (self::$current_role_func === null)
            self::$current_role_func = create_function('', 
                ' if (!Authn_Realm::hasIdentity())
                    return null;
                  return Authn_Realm::getIdentity()->id();');
        return self::$current_role_func;
    }
    
    //! Get the current role name based on callback
    static public function getCurrentRole()
    {   
        return call_user_func(self::getCurrentRoleFunc());
    }

    //! Set the callback function tha returns the current role name
    /**
     * @param $callable Any type of object that can be called with call_user_func()
     */
    static public function setCurrentRoleFunc($callable)
    {
        self::$current_role_func = $callable;
    }
    
    //! Search and return a resource in current resource list.
    /**
     * @param $resource
     *  - @b string The name of the resource class
     *  - @b array A tuple of name and instance id of a resource instance.
     *  .
     * @return \toolib\Authz\Resource
     *  - @b Authz_Resource The found resource object.
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
    
    //! Shortcut to add an @b allow ACE in the ACL of a resource
    /**
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
    
    //! Shortcut to add an @b deny ACE in the ACL of a resource
    /**
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

    //! Search if an action by current role on a specific resource is permitted.
    /**
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
    
    //! Search if a role on a resource is permitted to do an action.
    /**
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
