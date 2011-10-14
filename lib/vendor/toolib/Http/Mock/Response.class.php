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

namespace toolib\Http\Mock;
use toolib\Http\HeaderContainer;
use toolib\Http ;

require_once __DIR__ . '/../Response.class.php';
require_once __DIR__ . '/../HeaderContainer.class.php';
require_once __DIR__ . '/../Cookie.class.php';

/**
 * @brief Exception to be raised when exit shoud be called (for test). 
 */
class ImmediateExitRequest extends \Exception
{
	
}

/**
* @brief Response implementation for Mock package.
*/
class Response extends Http\Response
{
	/**
	 * @brief Status code reported
	 * @var array
	 */
	private $status = array('code' => '200', 'message' => 'OK');
	
	/**
	 * @brief Headers
	 * @var \toolib\Http\HeaderContainer
	 */
	private $headers;
	
	/**
	 * @brief Body of the response message
	 * @var string
	 */
	private $body = '';
	
	/**
	 * @brief Construct a new empty response
	 */
	public function __construct()
	{
		$this->headers = new HeaderContainer();
	}

	public function addHeader($name, $value, $replace = true)
	{
		if ($replace)
			$this->headers->replace($name, $value);
		else
			$this->headers->add($name, $value);
	}
	
	public function removeHeader($name)
	{
		if ($this->headers->has($name))
			$this->headers->remove($name);
	}
	
	/**
	 * @brief Get the headers of this message
	 * @return \toolib\Http\HeaderContainer
	 */
	public function getHeaders()
	{
		return $this->headers;
	}
	
    public function redirect($url, $auto_exit = true)
    {
        $this->addHeader('Location', $url);
        if ($auto_exit)
            throw new ImmediateExitRequest();
    }

    public function setContentType($mime)
    {
        $this->addHeader('Content-Type', $mime);
    }

    public function setStatusCode($code, $message)
    {
    	if ($code < 100 || $code > 999)
    		throw new \InvalidArgumentException("Code \"{$code}\" is not valid HTTP Status code.");
    	
        $this->status['code'] = $code;
        $this->status['message'] = $message;
    }
    
    
    public function getStatusCode(& $message = null)
    {
    	$message = $this->status['message'];
    	return $this->status['code'];
    }
    
    public function appendContent($data)
    {
    	$this->body .= (string)$data;
    }
    
    /**
     * @brief Get the content (body) of this message
     */
    public function getContent()
    {
    	return $this->body;
    }
    
    public function setCookie(Http\Cookie $cookie)
    {
    	$this->addHeader('Set-Cookie', (string)$cookie, false);        
    }
}
