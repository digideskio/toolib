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


require_once dirname(__FILE__) . '/Authz/ResourceList.class.php';
require_once dirname(__FILE__) . '/Authz/RoleFeederInstance.class.php';

class Authz
{
    static private $role_feeder = null;

    static private $resource_list = null;
    
    static public function get_resource_list()
    {
        if (self::$resource_list === null)  
            self::$resource_list = new Authz_ResourceList();
        return self::$resource_list;
    }
    
    static public function set_resource_list(Authz_ResourceList $list)
    {
        self::$resource_list = $list;
    }
    
    static public function get_role_feeder()
    {
        return self::$role_feeder;
    }
    
    static public function set_role_feeder(Authz_RoleFeeder $feeder)
    {
        self::$role_feeder = $feeder;
    }
    
    static public function is_allowed($resource, $role, $action)
    {
        if (is_array($resource))
            $res = self::get_resource_list()->get_resource($resource[0], $resource[1]);
        else
            $res = self::get_resource_list()->get_resource($resource);

        if (!$res)
            throw new InvalidArgumentException('Cannot find resource with name "' . 
                (is_array($resource)?$resource[0]:$resource) . '"');

        if (($ace = $res->effective_ace($role, $action, self::get_role_feeder(), $depth)) === null)
            return false;

        return $ace->is_allowed();
    }
   
}

?>
