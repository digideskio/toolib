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

use toolib\Http\Mock\Response;
use toolib\Http\Mock\Request;
use toolib\Http\Mock\ImmediateExitRequest;
use toolib\Http\Cookie;

require_once __DIR__ .  '/../../path.inc.php';

class Http_MockResponseTest extends PHPUnit_Framework_TestCase
{
	
    public function testEmptyConstructor()
    {
        $r = new Response();
        
		$this->assertEquals('', $r->getContent());
        $this->assertEquals(array('code' => '200', 'message' => 'OK'), $r->getStatusCode());
        $this->assertInstanceOf('\toolib\Http\HeaderContainer', $r->getHeaders());
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

    	// Append a previous one
    	$r->addHeader('h2', 'v4', false);
    	$this->assertEquals(2, count($r->getHeaders()));
    	$this->assertTrue($r->getHeaders()->is('h2', 'v4'));
    	$this->assertEquals(array('v3', 'v4'), $r->getHeaders()->getValues('h2'));
    }
    
    public function testRemoveHeader()
    {
    	$r = new Response();
    	$r->addHeader('h1', 'v1');
    	$r->addHeader('h1', 'v11');
    	$r->addHeader('h2', 'v2');
    	$r->addHeader('h3', 'v3');
    	
    	// Remove a unknown one
    	$r->removeHeader('wrong');
    	$this->assertEquals(3, count($r->getHeaders()));
    	
    	// Remove a signle one
    	$r->removeHeader('h3');
    	$this->assertEquals(2, count($r->getHeaders()));
    	$this->assertFalse($r->getHeaders()->has('h3'));
    	
    	// Remove double one
    	$r->removeHeader('h1');
    	$this->assertEquals(1, count($r->getHeaders()));
    	$this->assertFalse($r->getHeaders()->has('h1'));

    	// Remove last one
    	$r->removeHeader('h2');
    	$this->assertEquals(0, count($r->getHeaders()));
    	$this->assertFalse($r->getHeaders()->has('h2'));
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
    
    public function testExpirationNoCache()
    {
    	$r = new Response();
    	
		$r->setCacheDirectiveNoCache();
    	$this->assertEquals('no-cache', $r->getHeaders()->getValue('Cache-control'));
    }
    
    public function testExpirationDefault()
    {
    	$r = new Response();
    	 
    	// Set one argument
    	$r->setCacheMaxAge(10000);
    	$this->assertEquals('max-age=10000', $r->getHeaders()->getValue('Cache-control'));
    	
    	// Set second argument
    	$r->setCacheSharedMaxAge(999);
    	$this->assertEquals('max-age=10000, s-max-age=999', $r->getHeaders()->getValue('Cache-control'));
    	
    	// Set third
    	$r->setCachePublic();
    	$this->assertEquals('max-age=10000, s-max-age=999, public', $r->getHeaders()->getValue('Cache-control'));

    	// Reset shared max age
    	$r->setCacheSharedMaxAge(888);
    	$this->assertEquals('max-age=10000, s-max-age=888, public', $r->getHeaders()->getValue('Cache-control'));
    	
    	// Reset  max age
    	$r->setCacheMaxAge(222);
    	$this->assertEquals('max-age=222, s-max-age=888, public', $r->getHeaders()->getValue('Cache-control'));
    	
    	// Set private again
    	$r->setCachePrivate();
    	$this->assertEquals('max-age=222, s-max-age=888', $r->getHeaders()->getValue('Cache-control'));

    	// Set custom directive
    	$r->setCacheDirective('must-revalidate');
    	$this->assertEquals('max-age=222, s-max-age=888, must-revalidate', $r->getHeaders()->getValue('Cache-control'));
    }
    
    public function testValidation()
    {
    	$request = new Request();
    	$response = new Response();
    	$this->assertFalse($response->isNotModified($request));
    	
    	// Etag check - with no declaration - FALSE
    	$request = new Request('/', null, array('If-None-Match' => 'qweasd'));
    	$response = new Response();
    	$this->assertFalse($response->isNotModified($request));
    	
    	// Etag check - with no Ifnone-match- FALSE
    	$request = new Request();
    	$response = new Response();
    	$response->setEtag('qweasd');
    	$this->assertFalse($response->isNotModified($request));
    	
    	// Etag check - one if-none-match TRUE
    	$request = new Request('/', null, array('If-None-Match' => 'qweasd'));
    	$response = new Response();
    	$response->setEtag('qweasd');
    	$this->assertTrue($response->isNotModified($request));
    	
    	// Etag check - multiple if-none-match TRUE
    	$request = new Request('/', null, array('If-None-Match' => 'qweasd, zxcasd'));
    	$response = new Response();
    	$response->setEtag('qweasd');
    	$this->assertTrue($response->isNotModified($request));
    	
    	// Etag check - multiple if-none-match TRUE
    	$request = new Request('/', null, array('If-None-Match' => 'qweasd, "zxcasd"'));
    	$response = new Response();
    	$response->setEtag('"zxcasd"');
    	$this->assertTrue($response->isNotModified($request));
    	
    	
    	// Last-modified check - with no declaration False
    	$request = new Request('/', null, array('If-Modified-Since' => 'Sat, 29 Oct 1994 19:43:31 GMT'));
    	$response = new Response();
    	$this->assertFalse($response->isNotModified($request));
    	
    	// Last-modified check - with  no if-modified False
    	$request = new Request();
    	$response = new Response();
    	$response->setLastModified(date_create('Sat, 29 Oct 1994 19:43:31 GMT'));
    	$this->assertFalse($response->isNotModified($request));
    	
    	// Last-modified check - with exactly same time TRUE
    	$request = new Request('/', null, array('If-Modified-Since' => 'Sat, 29 Oct 1994 19:43:31 GMT'));
    	$response = new Response();
    	$response->setLastModified(date_create('Sat, 29 Oct 1994 19:43:31 GMT'));
    	$this->assertTrue($response->isNotModified($request));

    	// Last-modified check - with 1 second older TRUE
    	$request = new Request('/', null, array('If-Modified-Since' => 'Sat, 29 Oct 1994 19:43:31 GMT'));
    	$response = new Response();
    	$response->setLastModified(date_create('Sat, 29 Oct 1994 19:43:30 GMT'));
    	$this->assertTrue($response->isNotModified($request));

    	// Last-modified check - with 1 second newer FALSE
    	$request = new Request('/', null, array('If-Modified-Since' => 'Sat, 29 Oct 1994 19:43:31 GMT'));
    	$response = new Response();
    	$response->setLastModified(date_create('Sat, 29 Oct 1994 19:43:32 GMT'));
    	$this->assertFalse($response->isNotModified($request));
    	
    	
    	// ETag (TRUE) + Last Modified (TRUE) = TRUE
    	$request = new Request('/', null, array(
    	    		'If-None-Match' => 'qweasd, "zxcasd"',
    	    		'If-Modified-Since' => 'Sat, 29 Oct 1994 19:43:31 GMT'));
    	$response = new Response();
    	$response->setEtag('"zxcasd"');
    	$response->setLastModified(date_create('Sat, 29 Oct 1994 19:43:30 GMT'));
    	$this->assertTrue($response->isNotModified($request));
    	
    	// ETag (TRUE) + Last Modified (FALSE) = TRUE 
    	$request = new Request('/', null, array(
    		'If-None-Match' => 'qweasd, "zxcasd"',
    		'If-Modified-Since' => 'Sat, 29 Oct 1994 19:43:31 GMT'));
    	$response = new Response();
    	$response->setEtag('"zxcasd"');
    	$this->assertTrue($response->isNotModified($request));
    	
    	// ETag (FALSE) + Last Modified (FALSE) = FALSE
    	$request = new Request('/', null, array(
    	    		'If-None-Match' => 'qweasd, "zxcasd"',
    	    		'If-Modified-Since' => 'Sat, 29 Oct 1994 19:43:31 GMT'));
    	$response = new Response();
    	$this->assertFalse($response->isNotModified($request));
    }
    
}
