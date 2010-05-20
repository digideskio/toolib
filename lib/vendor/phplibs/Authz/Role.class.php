<?php

class Authz_Role
{
    private $name;
    
    private $parents = array();
    
    public function __construct($name, $parents = null)
    {
        $this->name = $name;
        
        if (is_string($parents))
            $this->parents[] = $parents;
        if (is_array($parents))
            $this->parents = $parents;
    }

    public function get_name()
    {
        return $this->name;
    }
    
    public function get_parents()
    {
        return $this->parents;
    }
    
    public function has_parent($parent)
    {
        return in_array($parent, $this->parents);
    }
}

?>
