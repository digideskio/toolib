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

namespace toolib\Http;
require_once __DIR__ . '/Cookie.class.php';

/**
 * @brief Base class for interfacing HTTP Responses.
 */
abstract class Response
{
	
	/**
	 * @brief Add a new header on response
	 * @param string $name Name of header
	 * @param string $value Value of header
	 * @param boolean $replace If true, it will replace any existing header with same name.
	 */
	abstract public function addHeader($name, $value, $replace = true);
	
	/**
	* @brief Remove a header from response
	* @param string $name Name of header
	*/
	abstract public function removeHeader($name);
	
    /**
     * @brief Ask user-agent to redirect in a new url
     * @param $url The absolute or relative url to redirect.
     * @param $auto_exit If @b true the program will terminate immediately.
     */
    abstract public function redirect($url, $auto_exit = true);

    /**
     * @brief Define the content type of this response
     * @param $mime The mime of the content.
     */
    abstract public function setContentType($mime);

    /**
     * @brief Set the status code and message of response
     * @param $code 3-digits error code.
     * @param $message A small description of this error code.
     * @throws InvalidArgumentException
     */
    abstract public function setStatusCode($code, $message);
    
    /**
     * @brief Append content data on the response.
     * @param string $data
     */
    abstract public function appendContent($data);
    
    /**
     * @brief Set a cookie to be send with response
     * @param \toolib\Http\Cookie $cookie
     */    
    abstract public function setCookie(Cookie $cookie);
}
