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


class Request
{
	public $uri;
	
	public $method;
	
	public $http_method;
	
	public $scheme;
	
	public $cookies;
	
	public $headers;
	
	public $query;
	
	public $raw_content;
	
	public $content;
	
	/**
	 * @brief Create an empty Request object
	 */
    public function __construct()
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
     * @brief Get the full requested uri
     */
    public function getRequestUri()
    {
    	return $this->request_uri;
    }
    
    /**
     * @brief Get only the uri requested after the script
     * (PATH_INFO)
     */
    public function getUri()
    {
    	return $this->uri;
    }
    
    public function getCookies()
    {
    	
    }
    
    public function getScheme()
    {
    	
    }
    
    public function getMethod()
    {
    	
    }
    
    public function getHeaders()
    {
    	
    }
    
    public function getProtocolVersion()
    {
    	
    }
    
    public function getContent()
    {
    	
    }
    
    public function getRawContent()
    {
    	
    }
    /**
     * @brief Check if this request is of 'POST' method
     */
    public function isPost()
    {
    	return ($this->getMethod() == 'POST');
    }
    
    /**
     * @brief Check if this request is of 'GET' method
     */
    public function isGet()
    {
    	return ($this->getMethod() == 'GET');
    }
    
    /**
     * @brief Check if this request is through https
     */
    public function isSecure()
    {
    	return ($this->getScheme() == 'HTTPS');
    }
    
    /**
    * @brief Get the reponse from the current gateway instance
    */
    public function getInstance()
    {
    	return \toolib\Http\Gateway::getInstance()->getRequest();
    }
}
