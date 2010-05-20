<?php

class Authz_ACE
{
    private $role;
    
    private $action;
    
    private $allowed;
    
    public function __construct($role, $action, $allowed)
    {        
        $this->role = $role;
        
        $this->action = $action;
        
        $this->allowed = (boolean) $allowed;
    }
    
    public function get_role()
    {
        return $this->role;
    }
    
    public function get_action()
    {
        return $this->action;
    }
    
    public function is_allowed()
    {
        return $this->allowed;
    }
    
    public function get_dn_hash()
    {
        return "{$this->role}:{$this->action}";
    }
}
?>
