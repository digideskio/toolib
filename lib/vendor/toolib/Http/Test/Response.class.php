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

namespace toolib\Http\Test;
use toolib\Http as H;

require_once __DIR__ . '/../Cookie.class.php';

class Response
{
	private $_status = array('code' => '200', 'message' => 'OK');
	
	private $_headers = array();
	
	private $_body = '';
	
	public function __construct()
	{
		
	}
	
	public function addHeader($name, $value, $replace = true)
	{
		if ($replace || !isset($this->_headers[$name])) {
			$this->_headers[$name] = $value;
			return;
		} 
		
		if (is_array($this->_headers[$name]))
			$this->_headers[$name][] = $value; 
		else
			$this->_headers[$name] = array($this->_headers[$name], $value);		
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

    static public function setErrorCode($code, $message)
    {   
        $this->$_status['code'] = $code;
        $this->$_status['message'] = $message;
    }
    
    public function appendContent($data)
    {
    	$this->$_body .= (string)$data;
    }
    
    /**
     * @brief Send cookie to the http response layer
     * 
     * It will use the php's setcookie() function to send
     * all cookie data to the response.
     */
    public function sendCookie(H\Cookie $cookie)
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
