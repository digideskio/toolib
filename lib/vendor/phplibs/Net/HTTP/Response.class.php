<?php
require_once dirname(__FILE__) . '/Cookie.class.php';

//! Manage the native HTTP response
class Net_HTTP_Response
{
    //! Ask user-agnet to redirect in a new url
    /**
     * @param $url The absolute or relative url to redirect.
     * @param $auto_exit If @b true the program will terminate immediatly.
     */
    static public function redirect($url, $auto_exit = true)
    {   
        header('Location: '. $url);
        if ($auto_exit)
            exit;
    }

    //! Define the content type of this response
    /**
     * @param $mime The mime of the content.
     */
    static public function set_content_type($mime)
    {   
        header('Content-type: ' . $mime);
    }

    //! Set the error code and message of response
    /**
     * @param $code 3-digits error code.
     * @param $message A small description of this error code.
     */
    static public function set_error_code($code, $message)
    {   
        header("HTTP/1.1 {$code} {$message}");
    }
}
