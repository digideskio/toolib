<?php

class Authz
{
    static private $res_list;
    
    static private $role_list;

    static private init()
    {
        self::$role_list = new Authz_RoleList();
        self::$res_list = new Authz_ResourceList($this->role_list);
        
    }
    static public function get_resources()
    {
        return self::$res_list;
    }
    
    static public function get_roles()
    {
        return self::$role_list;
    }
    
    static public function is_allowed($resource, $role, $action)
    {
        if (is_array($resource))
            $res = self::$res_list->get_resource($resource[0], $resource[1]);
        else
            $res = self::$res_list->get_resource($resource);

        if (!$res)
            throw new InvalidArgumentException('Cannot find exception with name "' . 
                (is_array($resource)?$resource[0]:$resource) . '"');

        return $res->is_allowed($role, $action);
    }
   
}

?>
