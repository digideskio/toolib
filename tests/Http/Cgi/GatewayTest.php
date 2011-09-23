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


use toolib\Http\Cgi;
use toolib\Http;

require_once __DIR__ .  '/../../path.inc.php';

/**
 */
class Http_CgiServiceTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @runInSeparateProcesses
 	 * @preserveGlobalState disabled
	 */
    public function testConstructor()
    {
    	$this->assertNull(Cgi\Gateway::getInstance());
        $gw = new Cgi\Gateway();
        $this->assertSame($gw, Cgi\Gateway::getInstance());
        
    }

    /**
     * @depends testConstructor
     */
    public function testGetRequest()
    {
    	$request = Http\Gateway::getInstance()->getRequest();
    	$this->assertInstanceOf('\toolib\Http\Cgi\Request', $request);
    	
    	// Check is the same
    	$this->assertSame($request, Http\Gateway::getInstance()->getRequest());
    }
    
    /**
    * @depends testConstructor
    */
    public function testGetResponse()
    {
    	$response = Http\Gateway::getInstance()->getResponse();
    	$this->assertInstanceOf('\toolib\Http\Cgi\Response', $response);
    	 
    	// Check is the same
    	$this->assertSame($response, Http\Gateway::getInstance()->getResponse());
    }
}
