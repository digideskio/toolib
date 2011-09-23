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
use toolib\Http\ParameterContainer;
use toolib\Http\HeaderContainer;

require_once __DIR__ . '/../Request.class.php';
require_once __DIR__ . '/../ParameterContainer.class.php';

/**
 * @brief Request implementation for Cgi package.
 */
class Request extends \toolib\Http\Request
{
	/**
	 * Storage for already parsed objects.
	 * @var array
	 */
	private $_parsed_objects = array();
	
	/**
	 * @param array $meta_variables The meta variables as defined in CGI protocol.
	 */
	public function __construct($meta_variables = null)
	{

		if ($meta_variables !== null) {
			$this->_meta_variables = $meta_variables;
			return;
		}
		
		// Create default Request
		$this->_meta_variables = array(
			'SERVER_SOFTWARE' => 'toolib',
			'SERVER_NAME' => 'localhost',
			'GATEWAY_INTERFACE' => 'CGI/1.1',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'SERVER_PORT' => 80,
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => null,
			'SCRIPT_NAME' => '',
			'REQUEST_URI' => '/',
			'QUERY_STRING' => null,
			'REMOTE_HOST' => '',
			'REMOTE_ADDR' => '',
			'CONTENT_TYPE' => 'text/html',
			'CONTENT_LENGTH' => null
		);
	}

	
	/**
	 * @brief Create ParameterContainer from the query string
	 * @return \toolib\Http\ParameterContainer
	 */
	private function queryStringToContainer()
	{
		$container = new ParameterContainer();
		if( !isset($this->_meta_variables['QUERY_STRING']))
			return $container;
			
		$chunks = explode('&', $this->_meta_variables['QUERY_STRING']);
		
		foreach ($chunks as $chunk) {
			$parts = explode('=', $chunk);
			$container[urldecode($parts[0])] = urldecode($parts[1]);
		}

		return $container;			
	}
	
	/**
	 * @brief Create an array of Cookie objects
	 */
	private function cookiesToContainer()
	{
		$container = new ParameterContainer();
		if( !isset($this->_meta_variables['HTTP_COOKIE']))
			return $container;
			
		$chunks = explode(';', $this->_meta_variables['HTTP_COOKIE']);		
		foreach($chunks as $chunk) {
			$parts = explode('=', trim($chunk));
			if ($parts[0][0] == '$')
				continue;
			$container[urldecode($parts[0])] = urldecode($parts[1]);
		}
		
		return $container;	
	}
	
	/**
	 * @brief Create an array of Header objects
	 */
	private function headersToContainer()
	{
		$container = new HeaderContainer();
		
		// Loop around meta variables
		foreach($this->_meta_variables as $key => $value) {
			if (substr($key, 0, 5) == "HTTP_") {
				$key = str_replace(" ", "-", ucwords(strtolower(str_replace("_"," ",substr($key,5)))));
				$container[$key] = $value;
			}
		}
		
		return $container;	
	}
	
	/**
	 * @brief Dynamically convert CGI variables to Request
	 * interface.
	 * @param string $property The name of the property.
	 */
	public function __get($property)
	{
		if ($property == 'query') {
			if ($this->_php_request)
				$this->$property = new ParameterContainer($_GET);
			else
				$this->$property = $this->queryStringToContainer();
				
		} else if ($property == 'uri') {
			$this->$property = $this->_meta_variables['REQUEST_URI'];
			
		} else if ($property == 'method') {
			$this->$property = $this->_meta_variables['REQUEST_METHOD'];
			
		} else if ($property == 'http_version') {
			$this->$property = isset($this->_meta_variables['SERVER_PROTOCOL'])?
				substr($this->_meta_variables['SERVER_PROTOCOL'], -3):'1.0';
				
		} else if ($property == 'cookies') {
			if ($this->_php_request)
				$this->$property = new ParameterContainer($_COOKIE);
			else
				$this->$property = $this->cookiesToContainer();
				
		} else if ($property == 'headers') {
			if ($this->_php_request && function_exists('apache_request_headers')) 
				$this->$property = new ParameterContainer(apache_request_headers);
			else
				$this->$property = $this->headersToContainer();
				
		} else if ($property == 'raw_content') {
			$this->$property = $this->_php_request ? file_get_contents('php://input'):null;
			
		} else if ($property == 'content') {
			$this->$property = ($this->_php_request)?new ParameterContainer($_POST):null;
			
		} else if ($property == 'cgi_version') {
			$this->$property = isset($this->_meta_variables['GATEWAY_INTERFACE'])
				?substr($this->_meta_variables['GATEWAY_INTERFACE'], -3):'1.1';
				
		} else if ($property == 'server_info') {
			$this->$property = array();
			if (isset($this->_meta_variables['SERVER_ADDR']))
				$this->server_info['addr'] = $this->_meta_variables['SERVER_ADDR'];
			if (isset($this->_meta_variables['SERVER_PORT']))
				$this->server_info['port'] = $this->_meta_variables['SERVER_PORT'];
				if (isset($this->_meta_variables['SERVER_NAME']))
				$this->server_info['name'] = $this->_meta_variables['SERVER_NAME'];
			if (isset($this->_meta_variables['SERVER_SOFTWARE']))
				$this->server_info['software'] = $this->_meta_variables['SERVER_SOFTWARE'];
			if (isset($this->_meta_variables['SERVER_PROTOCOL']))
				$this->server_info['protocol'] = $this->_meta_variables['SERVER_PROTOCOL'];
			
		} else if ($property == 'remote_info') {
			$this->$property = array();
			if (isset($this->_meta_variables['REMOTE_ADDR']))
				$this->remote_info['addr'] = $this->_meta_variables['REMOTE_ADDR'];
			if (isset($this->_meta_variables['REMOTE_PORT']))
				$this->remote_info['port'] = $this->_meta_variables['REMOTE_PORT'];
			if (isset($this->_meta_variables['REMOTE_HOST']))
				$this->remote_info['name'] = $this->_meta_variables['REMOTE_HOST'];

		} else {
			throw new \InvalidArgumentException('Unknown ' . __CLASS__ . "->{$property} property was requested.");
		}
		
		return $this->$property;
	}
	
	public function getRequestUri()
	{
		return $_SERVER['REQUEST_URI'];
	}
	
	public function getPath()
	{
		
	}
	
	public function getFragment()
	{
		
	}
	
	public function getQuery()
	{
		
	}
	
	public function getQueryString()
	{
		return $_SERVER['QUERY_STRING'];
	}
	
	public function getCookies()
	{
		
	}
	
	public function getScheme()
	{
		if (isset($this->_parsed_objects['scheme']))
			return $this->_parsed_objects['scheme'];
		
		return $this->_parsed_objects['scheme'] 
			= ((!isset($this->_meta_variables['HTTPS']))
				|| $this->_meta_variables['HTTPS'] == 'off')?'HTTP': 'HTTPS';
	}
	
	public function getMethod()
	{
		
	}
	
	public function getHeaders()
	{
		
	}
	
	public function getProtocolVersion()
	{
		return $_SERVER['SERVER_PROTOCOL'];
	}
	
	public function getContent()
	{
		
	}
	
	public function getRawContent()
	{
		
	}
}