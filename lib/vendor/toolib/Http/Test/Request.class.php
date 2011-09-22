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
use toolib\Http\HeaderContainer;

use toolib\Http\ParameterContainer;
use toolib\Http;

require_once __DIR__ . '/../Request.class.php';

/**
 * @brief Request implementation for Test package.
 */
class Request extends Http\Request 
{
	
	/**
	 * All the parameters of request
	 * @var array
	 */
	private $_params;
	
	/**
	 * @brief Create an empty Request object
	 */
    public function __construct($uri = '/', $post_data = null, $headers = null)
    {
        $this->_params = array(
        	'uri' => $uri,
        	'protocol_version' => 1.1,
        	'method' => $post_data === null ? 'GET' : 'POST',
        	'scheme' => 'HTTP',
        	'cookies' => array(),
        	'raw_content' => $post_data,
        	'content' => $post_data,
        	'headers' => new HeaderContainer($headers)
        );

        // Analyze URL
        $url_parts = parse_url($this->_params['uri']);
        $this->_params['path'] = isset($url_parts['path'])?$url_parts['path']: '/';
        $this->_params['query'] = 
        	$this->_params['query_string'] = isset($url_parts['query'])?$url_parts['query']: null;
        $this->_params['fragment'] = isset($url_parts['fragment'])?$url_parts['fragment']: null;
        
        // Parse query string
        parse_str($this->_params['query_string'], $this->_params['query']);
        $this->_params['query'] = new ParameterContainer($this->_params['query']);        
        
        // Parse submitted content
        if ($post_data !== null) {
        	parse_str($this->_params['raw_content'], $this->_params['content']);
        	$this->content = new ParameterContainer($this->_params['content']);
        }
        $this->cookies = new ParameterContainer();
    }
    
    public function getRequestUri()
    {
    	return $this->_params['uri'];
    }
    
    public function getPath()
    {
    	return $this->_params['path'];
    }

    public function getFragment()
    {
    	return $this->_params['fragment'];
    }

    public function getQuery()
    {
    	return $this->_params['query'];
    }
    
    public function getQueryString()
    {
    	return $this->_params['query_string'];
    }
    
    public function getCookies()
    {
    	$cookie_headers = $this->getHeaders();
    	return array();
    }
    
    public function getScheme()
    {
    	return $this->_params['scheme'];
    }
    
    public function getMethod()
    {
    	return $this->_params['method'];
    }
    
    public function getHeaders()
    {
    	return $this->_params['headers'];
    }
    
    public function getProtocolVersion()
    {
    	return $this->_params['protocol_version'];
    }
    
    public function getContent()
    {
    	return $this->_params['content'];
    }
    
    public function getRawContent()
    {
    	return $this->_params['raw_content'];
    }
}
