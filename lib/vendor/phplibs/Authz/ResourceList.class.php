<?php

class Authz_ResourceList
{
    private $resources = array();
    
    private $roles_feeder;
    
    public function __construct(Authz_RoleFeeder $roles)
    {
        $this->roles_feeder = $roles;
    }
    
    public function add_resource($name, $parent = null)
    {
        // Check for duplication
        if (isset($this->resources[$name]))
            throw new InvalidArgumentException("There is already resource with name \"{$name}\"");

        // Check for broken dependency
        if ($parent !== null)
            if (!$this->has_resource($parent))
                throw new InvalidArgumentException(
                    "Cannot add resource that depends on unknown resource \"{$parent}\"");
                
        $this->resources[$name] = new Authz_ResourceClass(
            $this->roles_feeder,
            $name,
            ($parent?$this->get_resource($parent):null)
        );
        
        return $this->resources[$name];
    }
    
    public function remove_resource($name)
    {
        if (!isset($this->resources[$name]))
            return false;
            
        foreach($this->resources as $res)
            if ($res->has_parent())
                if ($res->get_parent()->get_name() == $name)
                    throw new RuntimeException(
                        "Cannot remove resource \"{$name}\" because \"{$res->get_name()}\" depends on it.");

        unset($this->resources[$name]);
        return true;
    }
    
    public function get_resource($name, $instance = null)
    {
        if (!isset($this->resources[$name]))
            return false;
            
        if ($instance === null)
            return $this->resources[$name];

        return $this->resources[$name]->get_instance($instance);
    }
    
    public function has_resource($name)
    {
        return isset($this->resources[$name]);
    }
    
}

?>
