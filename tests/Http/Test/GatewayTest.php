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

use toolib\Http as H;

require_once __DIR__ .  '/../../path.inc.php';

class Http_TestServiceTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
    	$this->assertNull(H\Test\Gateway::getInstance());    	
        $gw = new H\Test\Gateway();
        $this->assertSame($gw, H\Test\Gateway::getInstance());
        
    }

    /**
     * @depends testConstructor
     */
    public function testGetRequest()
    {
    	$request = H\Gateway::getInstance()->getRequest();
    	$this->assertType('\toolib\Http\Test\Request', $request);
    	
    	// Check is the same
    	$this->assertSame($request, H\Gateway::getInstance()->getRequest());
    }
    
    /**
    * @depends testConstructor
    */
    public function testGetResponse()
    {
    	$response = H\Gateway::getInstance()->getResponse();
    	$this->assertType('\toolib\Http\Test\Response', $response);
    	 
    	// Check is the same
    	$this->assertSame($response, H\Gateway::getInstance()->getResponse());
    }
}
