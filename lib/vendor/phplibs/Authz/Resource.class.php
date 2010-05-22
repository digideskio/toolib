<?php

class Authz_Resource
{
    private $name;
    
    private $parent = null;
    
    public function __construct($name, $parent = null)
    {
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
}

?>
