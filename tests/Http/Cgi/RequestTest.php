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

use toolib\Http\Cgi\Request;

require_once __DIR__ .  '/../../path.inc.php';

class Http_CgiRequestTest extends PHPUnit_Framework_TestCase
{
	
	public function commonDefaultConditions(Request $r, $must_be_post = false, $is_secure = false )
	{
		$this->assertInstanceOf('\toolib\Http\ParameterContainer', $r->getQuery());
		$this->assertInstanceOf('\toolib\Http\HeaderContainer', $r->getHeaders());
		$this->assertInternalType('array', $r->getCookies());
		
		$this->assertEquals(1.1, $r->getProtocolVersion());
		if ($is_secure){
			$this->assertEquals('HTTPS', $r->getScheme());
			$this->assertTrue($r->isSecure());
		} else {
			$this->assertEquals('HTTP', $r->getScheme());
			$this->assertFalse($r->isSecure());
		}
		
		if ($must_be_post) {
			$this->assertEquals('POST', $r->getMethod());
			$this->assertFalse($r->isGet());
			$this->assertTrue($r->isPost());
		} else {
			$this->assertEquals('GET', $r->getMethod());
			$this->assertTrue($r->isGet());
			$this->assertFalse($r->isPost());
		}
	}
	
	public function SimpleCgiCase1()
	{
		$server = array(
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
		
		$get = array();
		$post = array();
		return array('server' => $server, 'get' => $get, 'post' => $post);
	}
	
	public function ComplexCgiCase()
	{
		$server = array(
			'SERVER_SOFTWARE' => 'toolib',
			'SERVER_NAME' => 'my.host.com',
			'GATEWAY_INTERFACE' => 'CGI/1.1',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'SERVER_PORT' => 8080,
			'REQUEST_METHOD' => 'POST',
			'PATH_INFO' => null,
			'SCRIPT_NAME' => '/var/www/more/test.php',
			'REQUEST_URI' => '/alias/to/page.php?a=3%3Dasdfas&asdf-df=123#fragmented',
			'QUERY_STRING' => 'getya=3%3Dasdfas&getyb-df=123',
			'REMOTE_HOST' => '',
			'REMOTE_ADDR' => '',
			'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
			'CONTENT_LENGTH' => 37
		);
	
		$content = 'postya=3%3Dqwertyuiop&getyb-df=zaqwsx';
		$get = array(
			'getya' => '3=Dasdfas',
			'getyb-df' => '123'
		);
		$post = array(
			'postya' => '3=qwertyuiop',
			'postyb-df' => 'zaqwsx'
		);
		
		return array('server' => $server, 'get' => $get, 'post' => $post);
	}
	
	public function ComplexQsCgiCase()
	{
		$server = array(
			'SERVER_SOFTWARE' => 'toolib',
			'SERVER_NAME' => 'my.host.com',
			'GATEWAY_INTERFACE' => 'CGI/1.1',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'SERVER_PORT' => 8080,
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => null,
			'SCRIPT_NAME' => '/var/www/more/test.php',
			'REQUEST_URI' => '/alias/to/page.php?a[]=value1&a[]=value2&bite[popo]=value3#fragmented',
			'QUERY_STRING' => 'getya=3%3Dasdfas&getyb-df=123',
			'REMOTE_HOST' => '',
			'REMOTE_ADDR' => '',
			'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
			'CONTENT_LENGTH' => null
		);
		
		$content = 'postya=3%3Dqwertyuiop&getyb-df=zaqwsx';
		$get = array(
			'a' => array('value1', 'value2'),
			'bite' => array('popo' => 'value3')
		);
		$post = array();
		
		return array('server' => $server, 'get' => $get, 'post' => $post);
	}
	
    public function testEmptyConstructor()
    {
        $r = new Request();
        
        $this->assertEquals('/', $r->getRequestUri());
        $this->assertEquals('/', $r->getPath());
        $this->assertNull($r->getFragment());
        $this->assertNull($r->getContent());
        $this->assertNull($r->getRawContent());
        $this->assertNull($r->getQueryString());
        
        $this->commonDefaultConditions($r);
        
        $this->assertEquals(0, count($r->getQuery()));
        $this->assertEquals(1, count($r->getHeaders()));
        $this->assertTrue($r->getHeaders()->is('Host', 'localhost'));
    }

    public function testConstructorSimpleUrl()
    {
    	$r = new Request('http://my.host.com/example/path');
    
    	$this->assertEquals('/example/path', $r->getRequestUri());
    	$this->assertEquals('/example/path', $r->getPath());
    	$this->assertNull($r->getFragment());
    	$this->assertNull($r->getContent());
    	$this->assertNull($r->getRawContent());
    	$this->assertNull($r->getQueryString());
    	 
    	$this->commonDefaultConditions($r);
    	$this->assertEquals(0, count($r->getQuery()));
    	$this->assertEquals(1, count($r->getHeaders()));
    	$this->assertTrue($r->getHeaders()->is('Host', 'my.host.com'));
    }
    
    public function testConstructorComplexUrl()
    {
    	$r = new Request('https://user:pass@my.host.com:8080/example/path');
    
    	$this->assertEquals('/example/path', $r->getRequestUri());
    	$this->assertEquals('/example/path', $r->getPath());
    	$this->assertNull($r->getFragment());
    	$this->assertNull($r->getContent());
    	$this->assertNull($r->getRawContent());
    	$this->assertNull($r->getQueryString());
    
    	$this->commonDefaultConditions($r, false, true);
    	$this->assertEquals(0, count($r->getQuery()));
    	$this->assertEquals(1, count($r->getHeaders()));
    	$this->assertTrue($r->getHeaders()->is('Host', 'my.host.com:8080'));
    }
    
    public function testConstructorSimpleUri()
    {
    	$r = new Request('/example/path');
    
    	$this->assertEquals('/example/path', $r->getRequestUri());
    	$this->assertEquals('/example/path', $r->getPath());
    	$this->assertNull($r->getFragment());
    	$this->assertNull($r->getContent());
    	$this->assertNull($r->getRawContent());
    	$this->assertNull($r->getQueryString());
    	
    	$this->commonDefaultConditions($r);
        $this->assertEquals(0, count($r->getQuery()));
        $this->assertEquals(1, count($r->getHeaders()));
        $this->assertTrue($r->getHeaders()->is('Host', 'localhost'));
    }
    
    public function testConstructorUriWithQS()
    {
    	$r = new Request('/example/path?a=1&b=2&c=3');
    
    	$this->assertEquals('/example/path?a=1&b=2&c=3', $r->getRequestUri());
    	$this->assertEquals('/example/path', $r->getPath());
    	$this->assertNull($r->getFragment());
    	$this->assertNull($r->getContent());
    	$this->assertNull($r->getRawContent());
    	$this->assertEquals('a=1&b=2&c=3', $r->getQueryString());

    	$this->commonDefaultConditions($r);
    	$this->assertEquals(3, count($r->getQuery()));
    	$this->assertEquals(array('a' => 1, 'b' => 2, 'c' => 3), $r->getQuery()->getArrayCopy());
    }

    
    public function testConstructorUriWithComplexQS()
    {
    	$r = new Request('/example/path?a=1&a=2&c[]=3&c[]=5');
    
    	$this->assertEquals('/example/path?a=1&a=2&c[]=3&c[]=5', $r->getRequestUri());
    	$this->assertEquals('/example/path', $r->getPath());
    	$this->assertNull($r->getFragment());
    	$this->assertNull($r->getContent());
    	$this->assertNull($r->getRawContent());
    	$this->assertEquals('a=1&a=2&c[]=3&c[]=5', $r->getQueryString());
    
    	$this->commonDefaultConditions($r);
    	$this->assertEquals(2, count($r->getQuery()));
    	$this->assertEquals(array('a' => 2, 'c' => array(3, 5)), $r->getQuery()->getArrayCopy());
    }

    public function testConstructorUriWithComplexQSandFragment()
    {
    	$r = new Request('/example/path?a=1&a=2&c[]=3&c[]=5#bigone?bigtwo');
    
    	$this->assertEquals('/example/path?a=1&a=2&c[]=3&c[]=5#bigone?bigtwo', $r->getRequestUri());
    	$this->assertEquals('/example/path', $r->getPath());
    	$this->assertEquals('bigone?bigtwo', $r->getFragment());
    	$this->assertNull($r->getContent());
    	$this->assertNull($r->getRawContent());
    	$this->assertEquals('a=1&a=2&c[]=3&c[]=5', $r->getQueryString());
    
    	$this->commonDefaultConditions($r);
    	$this->assertEquals(2, count($r->getQuery()));
    	$this->assertEquals(array('a' => 2, 'c' => array(3, 5)), $r->getQuery()->getArrayCopy());
    }
    
    public function testConstructorUriPost()
    {
    	$r = new Request('/example/path?a=1&a=2&c[]=3&c[]=5#bigone?bigtwo', 'pa=1&pa=2&pc[]=3&pc[]=5');
    
    	$this->assertEquals('/example/path?a=1&a=2&c[]=3&c[]=5#bigone?bigtwo', $r->getRequestUri());
    	$this->assertEquals('/example/path', $r->getPath());
    	$this->assertEquals('bigone?bigtwo', $r->getFragment());
    	$this->assertEquals('pa=1&pa=2&pc[]=3&pc[]=5', $r->getRawContent());
    	$this->assertEquals(array('pa' => 2, 'pc' => array(3, 5)), $r->getContent());
    	$this->assertEquals('a=1&a=2&c[]=3&c[]=5', $r->getQueryString());
    
    	$this->commonDefaultConditions($r, true);
    	$this->assertEquals(2, count($r->getQuery()));
    	$this->assertEquals(array('a' => 2, 'c' => array(3, 5)), $r->getQuery()->getArrayCopy());
    }

    public function testHeaders()
    {
    	$r = new Request(
    		'/example/path?a=1&a=2&c[]=3&c[]=5#bigone?bigtwo',
    		'pa=1&pa=2&pc[]=3&pc[]=5',
    		array('X-Test' => 'bride two',
    			'Cookie' => 'Bla bla blouba')
    	);
    	
    	$this->assertEquals(3, count($r->getHeaders()));
    	$this->assertTrue($r->getHeaders()->has('X-Test'));
    	$this->assertTrue($r->getHeaders()->has('Cookie'));
    	$this->assertTrue($r->getHeaders()->is('X-Test', 'bride two'));
    	$this->assertTrue($r->getHeaders()->is('Cookie', 'Bla bla blouba'));
    	$this->assertTrue($r->getHeaders()->is('Host', 'localhost'));
    }
    
    public function testCookies()
    {
    	$r = new Request(
    	    		'/example/path?a=1&a=2&c[]=3&c[]=5#bigone?bigtwo',
    	    		'pa=1&pa=2&pc[]=3&pc[]=5',
    	array('X-Test' => 'bride two',
    	    			'Cookie' => 'PREF=ID=AFSLDOWEMADF:U=9a8sdf34gsd9fg:FF=23:LD=en:NR=40:'.
    	    			'TM=124575346734:LM=12436346234:SG=10:S=8sdfgdfhjasfdga; ' .
    	    			'NID=51=SDFGSDfg-sdfhsdf-gk3425topui[90sugjsdfgaSGAegasdGasdfasdfqwer_=-')
    	);
    	
    	$this->assertEquals(array('PREF' => 'ID=AFSLDOWEMADF:U=9a8sdf34gsd9fg:FF=23:LD=en:NR=40:'.
    	    	'TM=124575346734:LM=12436346234:SG=10:S=8sdfgdfhjasfdga',
    	    'NID' => '51=SDFGSDfg-sdfhsdf-gk3425topui[90sugjsdfgaSGAegasdGasdfasdfqwer_=-'), $r->getCookies());
    }
}
