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

use toolib\Http\Test\Response;
use toolib\Http\Test\ImmediateExitRequest;
use toolib\Http\Cookie;

require_once __DIR__ .  '/../../path.inc.php';

class Http_TestResponseTest extends PHPUnit_Framework_TestCase
{
	
    public function testEmptyConstructor()
    {
        $r = new Response();
        
		$this->assertEquals('', $r->getContent());
        $this->assertEquals(array('code' => '200', 'message' => 'OK'), $r->getStatusCode());
        $this->assertType('\toolib\Http\HeaderContainer', $r->getHeaders());
        $this->assertEquals(0, count($r->getHeaders()));
    }
    
    public function testAddHeader()
    {
    	$r = new Response();
    	
    	// Add a new header
    	$r->addHeader('h1', 'v1');
    	$this->assertEquals(1, count($r->getHeaders()));
    	$this->assertTrue($r->getHeaders()->is('h1', 'v1'));
    	$this->assertEquals(array('v1'), $r->getHeaders()->getValues('h1'));
    	
    	// Add a second header
    	$r->addHeader('h2', 'v2');
    	$this->assertEquals(2, count($r->getHeaders()));
    	$this->assertTrue($r->getHeaders()->is('h2', 'v2'));
    	$this->assertEquals(array('v2'), $r->getHeaders()->getValues('h2'));
    	
    	// Overwrite a previous one
    	$r->addHeader('h2', 'v3');
    	$this->assertEquals(2, count($r->getHeaders()));
    	$this->assertTrue($r->getHeaders()->is('h2', 'v3'));
    	$this->assertEquals(array('v3'), $r->getHeaders()->getValues('h2'));

    	// Append a preivious one
    	$r->addHeader('h2', 'v4', false);
    	$this->assertEquals(2, count($r->getHeaders()));
    	$this->assertTrue($r->getHeaders()->is('h2', 'v4'));
    	$this->assertEquals(array('v3', 'v4'), $r->getHeaders()->getValues('h2'));
    }
    
    public function testSetStatusCode()
    {
    	$r = new Response();
    	
    	// Set 302 and a message
    	$r->setStatusCode('302', 'My Message');
    	$this->assertEquals(array('code' => '302', 'message' => 'My Message'), $r->getStatusCode());
    	
    	// Set 500 and empty message
    	$r->setStatusCode(500, '');
    	$this->assertEquals(array('code' => '500', 'message' => ''), $r->getStatusCode());
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testSetInvalidStatusCode()
    {
    	$r = new Response();
    	 
    	// Set 99 and a message
    	$r->setStatusCode(99, 'My Message');    	
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testSetInvalidStatusCode2()
    {
    	$r = new Response();
    
    	// Set 1000 and a message
		$r->setStatusCode(1000, 'My Message');
	}
    
    
    public function testSetContentType()
    {
    	$r = new Response();    

    	$r->setContentType('plain/html');
    	$this->assertEquals(1, count($r->getHeaders()));
    	$this->assertTrue($r->getHeaders()->is('Content-Type', 'plain/html'));    	
    }
    
    public function testRedirect()
    {
    	$r = new Response();
    	
    	// Absolute uri
    	$r->redirect('/test', false);
    	$this->assertEquals(1, count($r->getHeaders()));
    	$this->assertTrue($r->getHeaders()->is('Location', '/test'));    	

    	// Complete uri
    	$r->redirect('http://host.example.com/test', false);
    	$this->assertEquals(1, count($r->getHeaders()));
    	$this->assertTrue($r->getHeaders()->is('Location', 'http://host.example.com/test'));
    	
    	// Test redirect and exit
    	try{
    		$r->redirect('/test', true);
    	}catch(ImmediateExitRequest $e) {
    		$catched = true;
    	}
    	$this->assertTrue($catched);
    	$this->assertEquals(1, count($r->getHeaders()));
    	$this->assertTrue($r->getHeaders()->is('Location', '/test'));
    }
    
    public function testSetCookie()
    {
    	$r = new Response();
    	$c = new Cookie('myCook', '=asdfasgqerqsdafasgdas');
    	
		// One cookie
    	$r->setCookie($c);
    	$this->assertEquals(1, count($r->getHeaders()));
    	$this->assertTrue($r->getHeaders()->is('Set-Cookie', 'myCook=%3Dasdfasgqerqsdafasgdas'));

    	// Two cookies
    	$c = new Cookie('myCook2', 'asgasd#%dfgsdfg');
    	$r->setCookie($c);
    	$this->assertEquals(2, $r->getHeaders()->countValues('Set-Cookie'));
    	$this->assertTrue($r->getHeaders()->is('Set-Cookie', 'myCook=%3Dasdfasgqerqsdafasgdas'));
    	$this->assertTrue($r->getHeaders()->is('Set-Cookie', 'myCook2=asgasd%23%25dfgsdfg'));
    }
    
    public function testAppendContent()
    {
    	$r = new Response();
    	$this->assertEquals('', $r->getContent());
    	
    	// First data
    	$r->appendContent('abc');
    	$this->assertEquals('abc', $r->getContent());
    	
    	// n-data
    	$r->appendContent('123456');
    	$this->assertEquals('abc123456', $r->getContent());
    }
}
