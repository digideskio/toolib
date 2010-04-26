<?php
require_once dirname(__FILE__) . '/Identity.class.php';

//! Interface for authentication session storage
interface Auth_Storage
{
    //! Set the current session identity
    /**
     * @param $identity The identity object to save
     * @param $ttl The maximum time before expiration
     */
    public function set_identity(Auth_Identity $identity, $ttl = null);

    //! Get the current session identity
    /**
     * @return -b @b Auth_Identity object if one is signed on.
     *  - @b false If no identity online.
     */
    public function get_identity();

    //! Clear any identity from this session
    public function clear_identity();
}
