<?php

require_once(dirname(__FILE__) . '/../Storage.class.php');

//! Use native php sessions to track identity
class Auth_Storage_Session implements Auth_Storage
{
    //! The index to use in $_SESSION array for storing identity.
    private $session_index;
    
    public function __construct($session_index = 'PHPLIBS_AUTH_SESSION')
    {
        $this->session_index = $session_index;
    }
    
    
    public function set_identity(Auth_Identity $identity, $ttl = null)
    {
        session_regenerate_id();
        $_SESSION[$this->session_index] = $identity;
    }

    public function get_identity()
    {
        if (!isset($_SESSION[$this->session_index]))
            return false;
        if ($_SESSION[$this->session_index] === null)
            return false;
        return $_SESSION[$this->session_index];
    }
            

    public function clear_identity()
    {
        $_SESSION[$this->session_index] = null;
    }
}

?>
