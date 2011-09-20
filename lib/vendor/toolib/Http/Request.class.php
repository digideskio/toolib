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

/**
 * All classes related with HTTP protocol. 
 * @author sque
 */
namespace toolib\Http;
use toolib\Http\ParameterContainer;

require_once __DIR__ . '/ParameterContainer.class.php';

/**
 * @brief Base class for managing HTTP Requests.
 * @property string $uri The requested uri
 * @property string $method The HTTP Request method that was used.
 * @property string $http_version The HTTP protocol version.
 * @property string $scheme The scheme of the url. 'HTTPS' or 'HTTP'.
 * @property toolib\Http\ParameterContainer $cookies Cookies sent with the request. 
 * @property toolib\Http\ParameterContainer $headers The headers sent with the request.
 * @property toolib\Http\ParameterContainer $query The query string parsed and structured.
 * @property sting $raw_content The actual raw message body.
 * @property toolib\Http\ParameterContainer $content The analyzed content (post parameters).  
 */
class Request
{
	/**
	 * @brief Create an empty Request object
	 */
    public function __construct($uri = '/')
    {
        $this->uri = '/';
        $this->method = 'GET';
        $this->http_version = 1.1;
        $this->scheme = 'HTTP';
        $this->cookies = new ParameterContainer();
        $this->headers = new ParameterContainer();
        $this->query = new ParameterContainer();
        $this->raw_content = null;
        $this->content = new ParameterContainer();
    }
    
    /**
     * @brief Check if this request is of 'POST' method
     */
    public function isPost()
    {
    	return ($this->method == 'POST');
    }
    
    /**
     * @brief Check if this request is of 'GET' method
     */
    public function isGet()
    {
    	return ($this->method == 'GET');
    }
    
    /**
     * @brief Check if this request is through https
     */
    public function isSecure()
    {
    	return ($this->scheme == 'HTTPS');
    }
    
    public function getInstance()
    {
    	return \toolib\Http\Gateway::getInstance()->getRequest();
    }
}
