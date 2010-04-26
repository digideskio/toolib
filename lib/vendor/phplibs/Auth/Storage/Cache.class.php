<?php

require_once(dirname(__FILE__) . '/../Storage.class.php');

//! Use a cache engine to store tracked identities
class Auth_Storage_Cache implements Auth_Storage
{
    //! Cache Engine
    private $cache;

    //! Current session id
    private $session_id = null;

    //! The cookie that will be used to save id.
    private $cookie;

    //! Cache storage constructor
    /**
     * @param $cache The cache engine that will be used.
     * @param $cookie Cookie to be used for saving identity.
     *  All the parameters of cookie will be used except of value which will
     *  be changed to the appropriate one.
     */
    public function __construct(Cache $cache, Comm_HTTP_Cookie $cookie)
    {
        $this->cache = $cache;
        $this->cookie = $cookie;

        // Check if there is already a cookie
        $received_cookie = Comm_HTTP_Cookie::open($cookie->get_name());
        if ($received_cookie)
            $this->session_id = $received_cookie->get_value();
    }    
    
    public function set_identity(Auth_Identity $identity)
    {   // Clear identity
        clear_identity();

        // Create a new sessionid
        // Uniqid() without $entropy = true is just an alias for mircoseconds
        // mt_rand() is a better random generator that will be prefixed to uniqid
        // sha1() just hides clues about returned values of mt_rand() and uniquid()
        // however it does not protect you if mt_rand() and uniqid() are time dependant.
        $this->session_id = sha1(uniqid((string)mt_rand(), true));
        var_dump($this->session_id);
        var_dump(uniqid((string)mt_rand(), true));

        // Save in cache
        $this->cache->set($this->session_id, $identity);

        // Send cookie
        $this->cookie->set_value($this->session_id);
        $this->cookie->send();
    }

    public function get_identity()
    {   
        if ($this->session_id === null)
            return false;

        $identity = $this->cache->fetch($this->session_id, $succ)
        if (!$succ)
        {   clear_identity();
            return false;
        }

        return $identity;
    }

    public function clear_identity()
    {   // Remove data from cache
        if ($this->session_id)
            $this->cache->delete($this->session_id);

        // Reset session_id
        $this->session_id = null;

        // Delete cookie
        $this->cookie->set_value('');
        $this->cookie->send();
    }
}

?>
