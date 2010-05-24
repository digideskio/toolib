<?php

class Authz_RoleFeederStatic implements Authz_RoleFeeder
{
    private $roles = array();
    
    public function add_role(Authz_Role $role)
    {
        // Check for duplication
        if (isset($this->roles[$role->get_name()]))
            throw new InvalidArgumentException("There is already role with name \"{$role->get_name()}\"");
        
        // Check for broken dependency
        foreach($role->get_parents() as $p)
            if (! $this->has_role($p))
                throw new InvalidArgumentException("Cannot add role that depends on unknown role \"{$p}\"");
            
        $this->roles[$role->get_name()] = $role;
    }
    
    public function remove_role($role_name)
    {
        if (isset($this->roles[$role_name]))
            unset($this->roles[$role_name]);
    }
    
    public function get_role($role_name)
    {
        if (isset($this->roles[$role_name]))
            return $this->roles[$role_name];
        return false;
    }
    
    public function has_role($role_name)
    {
        return isset($this->roles[$role_name]);
    }
}

?>
