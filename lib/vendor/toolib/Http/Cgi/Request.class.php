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
	 * @brief Extract headers from CGI enviroment
	 * @return \toolib\Http\HeaderContainer
	 */
	private function headersToContainer()
	{
		$container = new HeaderContainer();
		
		// Loop around meta variables
		foreach($_SERVER as $key => $value) {
			if (substr($key, 0, 5) == "HTTP_") {
				$key = str_replace(" ", "-", ucwords(strtolower(str_replace("_"," ",substr($key,5)))));
				$container[$key] = $value;
			}
		}
		
		return $container;
	}
	
	/**
	 * @brief Parse requested url and extract data
	 */
	private function parseUrl()
	{
		$parts = parse_url($_SERVER['REQUEST_URI']);
		
		$this->_parsed_objects['path'] = isset($parts['path'])?$parts['path']:null;
		$this->_parsed_objects['fragment'] = isset($parts['fragment'])?$parts['fragment']:null;
	}
	
	/**
	 * @brief Fix bad/wierd order of files posted by php.
	 */
	private function fixFileKeys($files)
	{
		if (isset($files['name'], $files['tmp_name'], $files['size'], $files['type'], $files['error'])){
	
			// Multiple values for post-keys indexes
			$move_indexes_right = function($files) use(& $move_indexes_right)
			{
				if (!is_array($files['name']))
					return $files;
				
				$results = array();
				foreach($files['name'] as $index => $name) {
					$reordered = array(
								'name' => $files['name'][$index],
								'tmp_name' => $files['tmp_name'][$index],
								'size' => $files['size'][$index],
								'type' => $files['type'][$index],
								'error' => $files['error'][$index],
					);
	
					// If this is not leaf do it recursivly
					if (is_array($name))
						$reordered = $move_indexes_right($reordered);
	
					$results[$index] = $reordered;
				}
				return $results;
			};
			return $move_indexes_right($files);
		}
			
		// Re order pre-keys indexes
		array_walk($files, function(&$sub) use(& $fix_files_keys) {
			$sub = $this->fixFileKeys($sub);
		});
		return $files;
	}
	
	public function getRequestUri()
	{
		return $_SERVER['REQUEST_URI'];
	}
	
	public function getPath()
	{
		if (isset($this->_parsed_objects['path']))
			return $this->_parsed_objects['path'];
		
		$this->parseUrl();
		return $this->_parsed_objects['path'];
	}
	
	public function getFragment()
	{
		if (isset($this->_parsed_objects['fragment']))
			return $this->_parsed_objects['fragment'];
		
		$this->parseUrl();
		return $this->_parsed_objects['fragment'];
	}
	
	public function getQuery()
	{
		if (isset($this->_parsed_objects['scheme']))
			return $this->_parsed_objects['scheme'];
		
		return $this->_parsed_objects['scheme']
			= new ParameterContainer($_GET);

	}
	
	public function getQueryString()
	{
		return $_SERVER['QUERY_STRING'];
	}
	
	public function getCookies()
	{
		return $_COOKIE;
	}
	
	public function getScheme()
	{
		if (isset($this->_parsed_objects['scheme']))
			return $this->_parsed_objects['scheme'];
		
		return $this->_parsed_objects['scheme'] 
			= ((!isset($_SERVER['HTTPS']))
				|| $_SERVER['HTTPS'] == 'off')?'HTTP': 'HTTPS';
	}
	
	public function getMethod()
	{
		return $_SERVER['REQUEST_METHOD'];				
	}
	
	public function getHeaders()
	{
		if (isset($this->_parsed_objects['headers']))
			return $this->_parsed_objects['headers'];
		
		if (function_exists('apache_request_headers'))
			$this->_parsed_objects['headers'] = new HeadersContainer(apache_request_headers());
		else
			$this->_parsed_objects['headers'] = $this->headersToContainer();
		return $this->_parsed_objects['headers'];
	}
	
	public function getProtocolVersion()
	{
		if (isset($this->_parsed_objects['protocol_version']))
			return $this->_parsed_objects['protocol_version'];

		return $this->_parsed_objects['protocol_version'] 
			= $this->$property = isset($this->_meta_variables['SERVER_PROTOCOL'])?
			substr($this->_meta_variables['SERVER_PROTOCOL'], -3):'1.0';
	}
	
	public function getContent()
	{
		if (isset($this->_parsed_objects['content']))
			return $this->_parsed_objects['content'];
		
		if (!$this->isPost())
			return $this->_parsed_objects['content'] = null;
			
		// Get posted arguments
		$files = $this->fixFileKeys($_FILES);
		$this->_parsed_objects['content'] = new ParameterContainer(
			array_merge($_POST, $files));
	}
	
	public function getRawContent()
	{
		if (isset($this->_parsed_objects['raw_content']))
			return $this->_parsed_objects['raw_content'];
		return $this->_parsed_objects['raw_content']
			= file_get_contents('php://input');
	}
}