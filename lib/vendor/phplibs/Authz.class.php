<?php

class Authz
{
    private $res_list;
    
    private $role_list;
    
    public function __construct()
    {
        $this->role_list = new Authz_RoleList();
        $this->res_list = new Authz_ResourceList();
    }
    
    public function get_resource_list()
    {
        return $this->res_list;
    }
    
    public function get_role_list()
    {
        return $this->role_list;
    }
    
    static public function is_allowed($resource, $role, $action)
    {
        
    
    }
}

?>
