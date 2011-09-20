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

use toolib\Http\Cookie;

require_once __DIR__ .  '/../path.inc.php';

class Net_CookieTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $cookie = new Cookie('testname1', 'testvalue');
		$this->assertEquals('testname1', $cookie->getName());
		$this->assertEquals('testvalue', $cookie->getValue());
		$this->assertEquals('', $cookie->getDomain());
		$this->assertEquals('/', $cookie->getPath());
		$this->assertEquals(0, $cookie->getExpirationTime());
		$this->assertTrue($cookie->isSessionCookie());
		$this->assertFalse($cookie->isSecure());
		$this->assertFalse($cookie->isHttponly());

        $cookie = new Cookie('testname1', 'testvalue', time() + 500, '/test/path', 'test.domain.com', true, false);
		$this->assertEquals('testname1', $cookie->getName());
		$this->assertEquals('testvalue', $cookie->getValue());
		$this->assertEquals('test.domain.com', $cookie->getDomain());
		$this->assertEquals('/test/path', $cookie->getPath());
		$this->assertEquals(time() + 500, $cookie->getExpirationTime());
		$this->assertFalse($cookie->isSessionCookie());
		$this->assertTrue($cookie->isSecure());
		$this->assertFalse($cookie->isHttponly());


        $cookie = new Cookie('testname1', 'testvalue', time() + 300, '/test/path', 'test.domain.com', false, true);
        $this->assertEquals('testname1', $cookie->getName());
		$this->assertEquals('testvalue', $cookie->getValue());
		$this->assertEquals('test.domain.com', $cookie->getDomain());
		$this->assertEquals('/test/path', $cookie->getPath());
		$this->assertEquals(time() + 300, $cookie->getExpirationTime());
		$this->assertFalse($cookie->isSessionCookie());
		$this->assertFalse($cookie->isSecure());
		$this->assertTrue($cookie->isHttponly());
    }

    public function testSetters()
    {
        $cookie = new Cookie('testname1', 'testvalue');
        $this->assertEquals($cookie->getName(), 'testname1');
        $this->assertEquals($cookie->getValue(), 'testvalue');
        $this->assertEquals($cookie->getDomain(), '');
        $this->assertEquals($cookie->getPath(), '/');
        $this->assertEquals($cookie->getExpirationTime(), 0);
        $this->assertEquals($cookie->isSessionCookie(), true);
        $this->assertEquals($cookie->isSecure(), false);
        $this->assertEquals($cookie->isHttponly(), false);

        $cookie->setName('testnamenew');
        $this->assertEquals($cookie->getName(), 'testnamenew');

        $cookie->setValue('testvaluenew');
        $this->assertEquals($cookie->getValue(), 'testvaluenew');

        $cookie->setDomain('my.domain.com');
        $this->assertEquals($cookie->getDomain(), 'my.domain.com');

        $cookie->setPath('/path/pp/test');
        $this->assertEquals($cookie->getPath(), '/path/pp/test');

        $cookie->setExpirationTime(time()+112);
        $this->assertEquals($cookie->getExpirationTime(), time()+112);
        $this->assertFalse($cookie->isSessionCookie());

        $cookie->setSecure(true);
        $this->assertTrue($cookie->isSecure());

        $cookie->setHttponly(true);
        $this->assertTrue($cookie->isHttponly());

        $this->assertEquals($cookie->getName(), 'testnamenew');
        $this->assertEquals($cookie->getValue(), 'testvaluenew');
        $this->assertEquals($cookie->getDomain(), 'my.domain.com');
        $this->assertEquals($cookie->getPath(), '/path/pp/test');
        $this->assertEquals($cookie->getExpirationTime(), time() + 112);
        $this->assertEquals($cookie->isSessionCookie(), false);
        $this->assertEquals($cookie->isSecure(), true);
        $this->assertEquals($cookie->isHttponly(), true);
    }
    
    public function testopenReceived()
    {
    	$this->assertFalse(Cookie::openReceived('unknown'));
    	
    	$_COOKIE = array(
    		'cookie1' => 'value1',
    		'cookie2' => 'value2'
    	);
    	
    	$this->assertFalse(Cookie::openReceived('cookie3'));
    	
    	// Check cookie 1
    	$cookie = Cookie::openReceived('cookie1');
    	$this->assertType('toolib\Http\Cookie', $cookie);
		$this->assertEquals('cookie1', $cookie->getName());
		$this->assertEquals('value1', $cookie->getValue());
		$this->assertEquals('', $cookie->getDomain());
		$this->assertEquals('/', $cookie->getPath());
		$this->assertEquals(0, $cookie->getExpirationTime());
		$this->assertTrue($cookie->isSessionCookie());
		$this->assertFalse($cookie->isSecure());
		$this->assertFalse($cookie->isHttponly());
    }
    
    public function testToString()
    {
    	$c = new Cookie('parameter', 'value=2');
    	$this->assertEquals('parameter=value%3D2', (string)$c);
    	
    	$c = new Cookie('parameter', 'value=2', 800000000);    	
    	$this->assertEquals('parameter=value%3D2; expires=Tue, 09-May-1995 06:13:20 GMT', (string)$c);
    	
    	$c = new Cookie('parameter', 'value=2', 800000000, '/test');
    	$this->assertEquals('parameter=value%3D2; expires=Tue, 09-May-1995 06:13:20 GMT; path=/test', (string)$c);
    	
    	$c = new Cookie('parameter', 'value=2', 800000000, '/test', 'www.example.com');
    	$this->assertEquals('parameter=value%3D2; expires=Tue, 09-May-1995 06:13:20 GMT; domain=www.example.com; path=/test', (string)$c);
    	
    	$c = new Cookie('parameter', 'value=2', 800000000, '/test', 'www.example.com', true);
    	$this->assertEquals('parameter=value%3D2; expires=Tue, 09-May-1995 06:13:20 GMT; domain=www.example.com; path=/test; secure', (string)$c);
    	
    	$c = new Cookie('parameter', 'value=2', 800000000, '/test', 'www.example.com', true, true);
    	$this->assertEquals('parameter=value%3D2; expires=Tue, 09-May-1995 06:13:20 GMT; domain=www.example.com; path=/test; secure; httponly', (string)$c);
    }
    
	public function testFromString()
    {
    	$c = Cookie::fromString('parameter=value%3D2');
    	$this->assertEquals('parameter', $c->getName());
    	$this->assertEquals('value=2', $c->getValue());
    	$this->assertEquals(null, $c->getExpirationTime());
    	$this->assertTrue($c->isSessionCookie());
    	$this->assertEquals(null, $c->getDomain());
    	$this->assertEquals('/', $c->getPath());    	
    	$this->assertFalse($c->isHttponly());
    	$this->assertFalse($c->isSecure());
    	
    	$c = Cookie::fromString('parameter=value%3D2; expires=Tue, 09-May-1995 06:13:20 GMT');
    	$this->assertEquals('parameter', $c->getName());
    	$this->assertEquals('value=2', $c->getValue());
    	$this->assertEquals(800000000, $c->getExpirationTime());    	
    	$this->assertFalse($c->isSessionCookie());
    	$this->assertEquals(null, $c->getDomain());
    	$this->assertEquals('/', $c->getPath());
    	$this->assertFalse($c->isHttponly());
    	$this->assertFalse($c->isSecure());
    	
    	$c = Cookie::fromString('parameter=value%3D2; expires=Tue, 09-May-1995 06:13:20 GMT; path=/test');
    	$this->assertEquals('parameter', $c->getName());
    	$this->assertEquals('value=2', $c->getValue());
    	$this->assertEquals(800000000, $c->getExpirationTime());    	
    	$this->assertFalse($c->isSessionCookie());
    	$this->assertEquals(null, $c->getDomain());
    	$this->assertEquals('/test', $c->getPath());
    	$this->assertFalse($c->isHttponly());
    	$this->assertFalse($c->isSecure());
    	
    	$c = Cookie::fromString('parameter=value%3D2; expires=Tue, 09-May-1995 06:13:20 GMT; domain=www.example.com; path=/test');
    	$this->assertEquals('parameter', $c->getName());
    	$this->assertEquals('value=2', $c->getValue());
    	$this->assertEquals(800000000, $c->getExpirationTime());    	
    	$this->assertFalse($c->isSessionCookie());
    	$this->assertEquals('www.example.com', $c->getDomain());
    	$this->assertEquals('/test', $c->getPath());
    	$this->assertFalse($c->isHttponly());
    	$this->assertFalse($c->isSecure());
    	
    	$c = Cookie::fromString('parameter=value%3D2; expires=Tue, 09-May-1995 06:13:20 GMT; domain=www.example.com; path=/test; secure');
    	$this->assertEquals('parameter', $c->getName());
    	$this->assertEquals('value=2', $c->getValue());
    	$this->assertEquals(800000000, $c->getExpirationTime());    	
    	$this->assertFalse($c->isSessionCookie());
    	$this->assertEquals('www.example.com', $c->getDomain());
    	$this->assertEquals('/test', $c->getPath());
    	$this->assertFalse($c->isHttponly());
    	$this->assertTrue($c->isSecure());

    	
    	$c = Cookie::fromString('parameter=value%3D2; expires=Tue, 09-May-1995 06:13:20 GMT; domain=www.example.com; path=/test; secure; httponly');
    	$this->assertEquals('parameter', $c->getName());
    	$this->assertEquals('value=2', $c->getValue());
    	$this->assertEquals(800000000, $c->getExpirationTime());    	
    	$this->assertFalse($c->isSessionCookie());
    	$this->assertEquals('www.example.com', $c->getDomain());
    	$this->assertEquals('/test', $c->getPath());
    	$this->assertTrue($c->isHttponly());
    	$this->assertTrue($c->isSecure());
    }
}
