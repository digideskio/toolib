<?php
require_once( dirname(__FILE__) . '/../Identity.class.php');

class Auth_Identity_DB implements Auth_Identity
{
    private $record;

    private $id;

    private $authority;

    //! The object is constructed by Auth_Backend_DB
    public function __construct($id, $authority, $record)
    {
        $this->id = $id;
        $this->record = $record;
        $this->authority = $authority;
    }

    public function id()
    {
        return $this->id;
    }

    //! Reset password of this identity
    /**
    * @param $password The new password to be set for this identity
    * @return - @b true If the password was changed succesfully.
    *  - @b false on any kind of error.
    */
    public function reset_password($password)
    {
        return $this->authority->reset_password($this->id(), $password);
    }

    //! Get the database record of this user
    public function get_record()
    {
        return $this->record;
    }

}
