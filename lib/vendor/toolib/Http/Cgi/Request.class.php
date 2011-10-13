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
use toolib\Http;

require_once __DIR__ . '/../Request.class.php';
require_once __DIR__ . '/../ParameterContainer.class.php';
require_once __DIR__ . '/../HeaderContainer.class.php';
require_once __DIR__ . '/../UploadedFile.class.php';

/**
 * @brief Request implementation for Cgi package.
 */
class Request extends \toolib\Http\Request
{
	/**
	 * Storage for already parsed objects.
	 * @var array
	 */
	private $parsed_objects = array();

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
				$key = str_replace(" ", "-", strtolower(str_replace("_"," ",substr($key,5))));
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
		
		$this->parsed_objects['path'] = isset($parts['path'])?$parts['path']:null;
		$this->parsed_objects['fragment'] = isset($parts['fragment'])?$parts['fragment']:null;
	}
	
	/**
	 * @brief Fix bad/wierd order of files posted by php.
	 */
	private function fixFileKeys($files, $create_objects = true)
	{
		$fix_file_keys_impl = function($files, $create_objects) use(& $fix_file_keys_impl) {
			if (isset($files['name'], $files['tmp_name'], $files['size'], $files['type'], $files['error'])){
		
				// Multiple values for post-keys indexes
				$move_indexes_right = function($files) use(& $move_indexes_right, $create_objects)
				{
					if (!is_array($files['name']))
						return (!$create_objects ? $files :
							new Http\UploadedFile(
								$files['name'],
								$files['type'],
								$files['tmp_name'],
								$files['size'],
								$files['error'] ));
							
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
						else if ($create_objects)
							$reordered = new Http\UploadedFile(
								$reordered['name'],
								$reordered['type'],
								$reordered['tmp_name'],
								$reordered['size'],
								$reordered['error'] );	
		
						$results[$index] = $reordered;
					}
					return $results;
				};
				return $move_indexes_right($files);
			}
				
			// Re order pre-keys indexes
			array_walk($files, function(&$sub) use(& $fix_file_keys_impl, $create_objects) {
				$sub = $fix_file_keys_impl($sub, $create_objects);
			});
			return $files;
		};
		
		return $fix_file_keys_impl($files, $create_objects);
	}
	
	public function getEnviroment()
	{
		return $_SERVER;
	}
	
	public function getRequestUri()
	{
		return $_SERVER['REQUEST_URI'];
	}
	
	public function getPath($default = null)
	{
		return isset($_SERVER['PATH_INFO'])?$_SERVER['PATH_INFO']:$default;		
	}

	public function getUriPath()
	{
		if (isset($this->parsed_objects['path']))
			return $this->parsed_objects['path'];
		$this->parseUrl();
		return $this->parsed_objects['path'];
	}
	
	public function getScriptPath()
	{
		if (isset($this->parsed_objects['script_path']))
			return $this->parsed_objects['script_path'];
		
		return $this->parsed_objects['script_path']
			= (strstr($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']) === false)
	    		?dirname($_SERVER['SCRIPT_NAME'])
	    		:$_SERVER['SCRIPT_NAME'];
	}
	
	public function getFragment()
	{
		if (isset($this->parsed_objects['fragment']))
			return $this->parsed_objects['fragment'];
		
		$this->parseUrl();
		return $this->parsed_objects['fragment'];
	}
	
	public function getQuery()
	{
		if (isset($this->parsed_objects['query']))
			return $this->parsed_objects['query'];
		
		return $this->parsed_objects['query']
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
		if (isset($this->parsed_objects['scheme']))
			return $this->parsed_objects['scheme'];
		
		return $this->parsed_objects['scheme'] 
			= ((!isset($_SERVER['HTTPS']))
				|| $_SERVER['HTTPS'] == 'off')?'HTTP': 'HTTPS';
	}
	
	public function getMethod()
	{
		return $_SERVER['REQUEST_METHOD'];				
	}
	
	public function getHeaders()
	{
		if (isset($this->parsed_objects['headers']))
			return $this->parsed_objects['headers'];
		
		if (function_exists('apache_request_headers')) {
			$apache_headers = array();
			foreach(apache_request_headers() as $name => $value)
				$apache_headers[strtolower($name)] = $value;
			$this->parsed_objects['headers'] = new HeaderContainer($apache_headers);
		} else {
			$this->parsed_objects['headers'] = $this->headersToContainer();
		}
		return $this->parsed_objects['headers'];
	}
	
	public function getProtocolVersion()
	{
		if (isset($this->parsed_objects['protocol_version']))
			return $this->parsed_objects['protocol_version'];

		return $this->parsed_objects['protocol_version'] 
			= isset($_SERVER['SERVER_PROTOCOL'])?
				substr($_SERVER['SERVER_PROTOCOL'], -3):'1.0';
	}
	
	public function getContent()
	{
		if (isset($this->parsed_objects['content']))
			return $this->parsed_objects['content'];
		
		if (!$this->isPost())
			return $this->parsed_objects['content'] = null;
			
		// Get posted arguments
		$files = $this->fixFileKeys($_FILES, true);
		return $this->parsed_objects['content'] = new ParameterContainer(
			array_merge_recursive($_POST, $files));
	}
	
	public function getRawContent()
	{
		if (isset($this->parsed_objects['raw_content']))
			return $this->parsed_objects['raw_content'];
		return $this->parsed_objects['raw_content']
			= file_get_contents('php://input');
	}
}