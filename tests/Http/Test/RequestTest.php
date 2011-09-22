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

use toolib\Http\Test\Request;

require_once __DIR__ .  '/../../path.inc.php';

class Http_TestRequestTest extends PHPUnit_Framework_TestCase
{
	
	public function commonDefaultConditions(Request $r, $must_be_post = false)
	{
		$this->assertType('\toolib\Http\ParameterContainer', $r->getQuery());
		$this->assertType('\toolib\Http\HeaderContainer', $r->getHeaders());
		$this->assertType('array', $r->getCookies());
		
		$this->assertEquals(1.1, $r->getProtocolVersion());
		$this->assertEquals('HTTP', $r->getScheme());
		$this->assertFalse($r->isSecure());
		
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
}
