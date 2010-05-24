<?php

class Authz_ResourceClass extends Authz_Resource
{
    protected $instances = array();

    public function get_instance($id)
    {
        if (isset($this->instances[$id]))
            return $this->instances[$id];
        
        return $this->instances[$id] =
            new Authz_Resource(
                $this->roles,
                (string)$id,
                $this);
    }
    
}

?>
