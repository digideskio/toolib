<?php
/*
 *  This file is part of PHPLibs <http://phplibs.kmfa.net/>.
 *  
 *  Copyright (c) 2010 < squarious at gmail dot com > .
 *  
 *  PHPLibs is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *  
 *  PHPLibs is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License
 *  along with PHPLibs.  If not, see <http://www.gnu.org/licenses/>.
 *  
 */

namespace toolib\Http\Cgi;
use toolib\Http;

require_once __DIR__ . '/../Cookie.class.php';

/**
* @brief Response implementation for Cgi package.
*/
class Response extends Http\Response
{
	public function addHeader($name, $value, $replace = true)
	{
		header($name .': ' . $value);
	}
	
	public function removeHeader($name)
	{
		header_remove($name);
	}
	
    public function redirect($url, $auto_exit = true)
    {   
        $this->addHeader('Location', $url);
        if ($auto_exit)
            exit;
    }

    public function setContentType($mime)
    {   
        $this->addHeader('Content-type', $mime);
    }

    public function setStatusCode($code, $message)
    {   
        header("HTTP/1.1 {$code} {$message}");
    }
    
    public function appendContent($data)
    {
    	echo $data;	// Just echo for CGI Enviroment
    	
    }
    
    public function setCookie(Http\Cookie $cookie)
    {
        setcookie($cookie->getName(),
            $cookie->getValue(),
            ($cookie->isSessionCookie()?0:$cookie->getExpirationTime()),
            $cookie->getPath(),
            $this->getDomain(),
            $this->isSecure(),
            $this->isHttponly()
        );
    }
}
