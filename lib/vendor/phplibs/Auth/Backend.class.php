<?php

//! Interface of authentication backend.
interface Auth_Backend
{
    //! Authenticate a user on this authority
    /**
     * @param $username The username of credentials.
     * @param $password The password of credentials.
     * @return - @b false If the authentication failed.
     *  - @b Auth_User object with the authenticated user.
     */
    public function authenticate($username, $password);
}
?>
