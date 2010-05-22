<?php

class Authz_ResourceList
{
    private $resources = array();
    
    public function add_resource($name, $parent = null)
    {
        // Check for duplication
        if (isset($this->resources[$name]))
            throw new InvalidArgumentException("There is already resource with name \"{$name}\"");

        // Check for broken dependency
        if ($parent !== null)
            if (!$this->has_resource($parent))
                throw new InvalidArgumentException("Cannot add resource that depends on unknown resource \"{$parent}\"");
                
        $this->resources[$name] = new Authz_Resource($name, ($parent?$this->get_resource($parent):null));
    }
    
    public function remove_resource($name)
    {
        if (isset($this->resources[$name]))
            unset($this->resources[$resource_name]);
    }
    
    public function get_resource($name)
    {
        if (isset($this->resources[$name]))
            return $this->resources[$name];
        return false;
    }
    
    public function has_resource($name)
    {
        return isset($this->resources[$name]);
    }
}

?>
