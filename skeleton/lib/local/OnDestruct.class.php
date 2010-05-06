<?php

//! Object to register handlers on destruction of this object
class OnDestruct
{
    //! Handlers
    private $handlers = array();

    //! Implement on destruction
    public function __destruct()
    {
        foreach($this->handlers as $handle)
        call_user_func($handle);
    }

    //! Register a new handler
    public function register_handler($callable)
    {
        $this->handlers[] = $callable;
    }

    //! Unregister handler
    public function unregister_handler($callable)
    {
        if (($key = array_search($callable, $this->handlers, true)) !== false)
        unset($this->handlers[$key]);
    }
}

?>
