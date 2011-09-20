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
use toolib\Http\ParameterContainer;

require_once __DIR__ . '/../Request.class.php';

//! Wrapper for CGI Request
/**
 * Manage meta-variables of a CGI Request
 * @property integer $cgi_version The version of cgi protocol.
 * @property array $server_info Information about this server.
 * @property array $remote_info Information about this remote end point.
 * @property string $path_info Part of the path after script name.
 * @property string $script_name The actual script that is executed 
 */
class Request extends \toolib\Http\Request
{

	/**
	 * @param array $meta_variables The meta variables as defined in CGI protocol.
	 * @param boolean $php_request Flag if this instance represents actual php request.
	 *  Enabling it, other superglobal variables will also be used.
	 */
	public function __construct($meta_variables = null, $php_request = false)
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
}