<?php

class Authz_Resource
{
    protected $name;
    
    protected $parent = null;
    
    protected $acl;
    
    protected $roles;
        
    public function __construct(Authz_RoleFeeder $roles, $name, $parent = null)
    {
        $this->roles = $roles;
        $this->acl = new Authz_ACL($roles);
        $this->name = $name;
        if (is_object($parent))
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
    
    public function has_parent()
    {
        return $this->parent !== null;
    }
    
    public function get_acl()
    {
        return $this->acl;
    }
    
    public function is_allowed($role, $action)
    {
        $res = $this->acl->effective_permission($role, $action);
        if ($res['metric'] !== 10000)
            return $res['allowed'];
        
        // Must check parent
        if ($this->has_parent())
            return $this->parent->is_allowed($role, $action);

        return false;
    }
}

?>
