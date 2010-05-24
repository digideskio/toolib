<?php

interface Authz_RoleFeeder
{
    public function has_role($name);
    
    public function get_role($name);
}

?>
