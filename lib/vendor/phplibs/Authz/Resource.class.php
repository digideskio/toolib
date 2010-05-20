<?php

class Authz_Resource
{
    private $name;
    
    private $parent = null;
    
    private $acl = null
    
    public function __construct($name, $parent = null)
    {
        $this->name = $name;
        if (is_string($parent))
            $this->parent = $parent;
    }
    
    public function get_name()
    {
        return $this->name;
    }
    
    public function get_parent()
    {
        return $this->parent;
    }
    
    public function is_allowed($role, $action, $access)
    {
    
    }
    
    public function allow($role, $action, $access)
    {
    
    }
    
    public function deny($role, $action, $access)
    {
    }
}


?>
