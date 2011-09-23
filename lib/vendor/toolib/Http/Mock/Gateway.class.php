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

require_once __DIR__ . '/../Gateway.class.php';
require_once __DIR__ . '/Request.class.php';
require_once __DIR__ . '/Response.class.php';

/**
* @brief Gateway implementation for Test package.
*/
class Gateway extends \toolib\Http\Gateway
{
	public function __construct()
	{
		parent::__construct();
		
		$this->request = new Request();
		$this->response = new Response();		
	}
	
	public function getRequest()
	{
		return $this->request;
	}
	
	public function getResponse()
	{
		return $this->response;
	}
}