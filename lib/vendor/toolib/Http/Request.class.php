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
 */
abstract class Request
{

    /**
     * @brief Get the full requested uri
     */
    abstract public function getRequestUri();
        
    /**
     * @brief Get only the uri requested after the script
     * (PATH_INFO)
     */
    abstract public function getPath();

    /**
    * @brief Get the part of url after hash #
    */
    abstract public function getFragment();

    /**
    * @brief It will return processed the URL's query string.
     * @return \toolib\Http\ParameterContainer
    */
    abstract public function getQuery();
    
    /**
     * @brief URL Query is the part between ? and end/#.
     */
    abstract public function getQueryString();
    
    /**
     * @brief Cookies sent with the request. 
     */
    abstract public function getCookies();    
    
    /**
     * @brief The scheme of the url. 'HTTPS' or 'HTTP'.
     */
    abstract public function getScheme();    
    
    /**
     * @brief Get the HTTP request method
     */
    abstract public function getMethod();
    
    /**
     * @brief The headers sent with the request.
     * @return \toolib\Http\HeaderContainer
     */
    abstract public function getHeaders();    
    
    /**
     * @brief The http protocol version
     */
    abstract public function getProtocolVersion();
    
    /**
     * @brief Get the content of the request
     * @return \toolib\Http\ParameterContainer
     */
    abstract public function getContent();

    /**
    * @brief Get the raw (unprocessed) content of the request
    */
    abstract public function getRawContent();
    
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