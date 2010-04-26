<?php

require_once(dirname(__FILE__) . '/../Storage.class.php');

//! Track identity inside the instance of this object
class Auth_Storage_Instance implements Auth_Storage
{
    //! The session identity
    private $online_identity;
    
    public function __construct()
    {
        $this->online_identity = false;
    }
        
    public function set_identity(Auth_Identity $identity, $ttl = null)
    {
        $this->online_identity = $identity;
    }

    public function get_identity()
    {
        return $this->online_identity;
    }
    
    public function clear_identity()
    {
        $this->online_identity = false;
    }
}

?>
