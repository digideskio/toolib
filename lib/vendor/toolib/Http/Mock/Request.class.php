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
use toolib\Http\ParameterContainer;
use toolib\Http;

require_once __DIR__ . '/../Request.class.php';
require_once __DIR__ . '/../ParameterContainer.class.php';
require_once __DIR__ . '/../HeaderContainer.class.php';

/**
 * @brief Request implementation for Mock package.
 */
class Request extends Http\Request 
{
	
	/**
	 * All the parameters of request
	 * @var array
	 */
	private $meta;
	
	/**
	 * @brief Create an empty Request object
	 */
    public function __construct($url = '/', $post_data = null, $headers = null)
    {
    
    	// Lower case headers before use
    	if (is_array($headers)) {
    		$new_headers = array();
    		foreach(array_keys($headers) as $name ){
    			$new_headers[strtolower($name)] = $headers[$name];
    		}
    		$headers = $new_headers;
    	}
    	
        $this->meta = array(
        	'url' => $url,
        	'protocol_version' => 1.1,
        	'method' => $post_data === null ? 'GET' : 'POST',
        	'scheme' => 'HTTP',
        	'cookies' => array(),
        	'raw_content' => $post_data,
        	'content' => $post_data,
        	'headers' => new HeaderContainer($headers)
        );

        // Analyze URL
        $url_parts = parse_url($this->meta['url']);
        if (isset($url_parts['scheme'])) {
        	if (!in_array($url_parts['scheme'], array('http', 'https')))
        		throw new \InvalidArgumentException(
        			"Cannot manipulate URL with \"{$url_parts['scheme']} scheme.\"");
        	$this->meta['scheme'] = strtoupper($url_parts['scheme']);
        }
        $this->meta['host'] = isset($url_parts['host'])?$url_parts['host']:'localhost';
        $this->meta['port'] = isset($url_parts['port'])?$url_parts['port']:null;
        $this->meta['path'] = isset($url_parts['path'])?$url_parts['path']: '/';
        $this->meta['query'] = 
        	$this->meta['query_string'] = isset($url_parts['query'])?$url_parts['query']: null;
        $this->meta['fragment'] = isset($url_parts['fragment'])?$url_parts['fragment']: null;
        
        // Create extra needed data
        $this->meta['uri'] = $this->meta['path']
        	. ($this->meta['query'] !== null?('?' . $this->meta['query']):'')
        	. ($this->meta['fragment'] !== null?('#' . $this->meta['fragment']):'');
        $this->meta['headers']->replace('Host', $this->meta['host'] 
        	. ($this->meta['port'] !== null?':' . $this->meta['port']:''));
        
        // Parse query string
        parse_str($this->meta['query_string'], $this->meta['query']);
        $this->meta['query'] = new ParameterContainer($this->meta['query']);        
        
        // Parse submitted content
        if ($post_data !== null) {
        	parse_str($this->meta['raw_content'], $this->meta['content']);
        	$this->content = new ParameterContainer($this->meta['content']);
        }
        $this->cookies = new ParameterContainer();
    }
    
    public function getEnviroment()
    {
    	return $this->meta;
    }
    
    public function getRequestUri()
    {
    	return $this->meta['uri'];
    }
    
    public function getPath($default = null)
    {
    	return $this->meta['path'];
    }
    
    public function getUriPath()
    {
    	return $this->_param['path'];
    }
    
    public function getScriptPath()
    {
    	return $this->_param['path'];
    }

    public function getFragment()
    {
    	return $this->meta['fragment'];
    }

    public function getQuery()
    {
    	return $this->meta['query'];
    }
    
    public function getQueryString()
    {
    	return $this->meta['query_string'];
    }
    
    public function getCookies()
    {
    	$cookie_headers = implode(' ; ', $this->getHeaders()->getValues('Cookie'));
    	$cookies = array();
    	foreach(explode(';', $cookie_headers) as $c) {
    		if (($c = trim($c)) == '')
    			continue;
    		if (($value = strstr($c, '=', true)) == false)
    			throw \RuntimeException('Mallformated Cookie header. "' . $c . '"');
    		
    		$cookies[$value] =  substr($c, strlen($value) + 1);
    	}
    	return $cookies;
    }
    
    public function getScheme()
    {
    	return $this->meta['scheme'];
    }
    
    public function setMethod($method)
    {
    	$method = strtoupper($method);
    	if (! in_array($method, array('GET', 'POST', 'PUT', 'DELETE', 'HEAD')))
    		throw new \InvalidArgumentException("Unknown HTTP method \"{$method}\"");
    	$this->meta['method'] = $method;
    	
    }
    public function getMethod()
    {
    	return $this->meta['method'];
    }
    
    public function getHeaders()
    {
    	return $this->meta['headers'];
    }
    
    public function getProtocolVersion()
    {
    	return $this->meta['protocol_version'];
    }
    
    public function getContent()
    {
    	return $this->meta['content'];
    }
    
    public function getRawContent()
    {
    	return $this->meta['raw_content'];
    }
}
