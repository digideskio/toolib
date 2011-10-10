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

use toolib\Http\Cgi\Response;
use toolib\Http\Cookie;

require_once __DIR__ .  '/../../path.inc.php';

class Http_CgiResponseTest extends PHPUnit_Framework_TestCase
{
	
    public function testEmptyConstructor()
    {
        $r = new Response();
    }
    
    public function testAddHeader()
    {
    	$r = new Response();
    	
    	// Add a new header
    	$r->addHeader('h1', 'v1');
    	
    	
    	// Add a second header
    	$r->addHeader('h2', 'v2');
    	
    	// Overwrite a previous one
    	$r->addHeader('h2', 'v3');
    	
    	// Append a preivious one
    	$r->addHeader('h2', 'v4', false);
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

    	
    	// Remove a signle one
    	$r->removeHeader('h3');
    	
    	// Remove double one
    	$r->removeHeader('h1');

    	// Remove last one
    	$r->removeHeader('h2');
    }
    
    public function testSetStatusCode()
    {
    	$r = new Response();
    	
    	// Set 302 and a message
    	$r->setStatusCode('302', 'My Message');
    	
    	// Set 500 and empty message
    	$r->setStatusCode(500, '');
    }
    
  
    
    public function testSetContentType()
    {
    	$r = new Response();    

    	$r->setContentType('plain/html');
    }
    
    public function testRedirect()
    {
    	$r = new Response();
    	
    	// Absolute uri
    	$r->redirect('/test', false);

    	// Complete uri
    	$r->redirect('http://host.example.com/test', false);
    	
    }
    
    public function testSetCookie()
    {
    	$r = new Response();
    	$c = new Cookie('myCook', '=asdfasgqerqsdafasgdas');
    	
		// One cookie
    	$r->setCookie($c);

    	// Two cookies
    	$c = new Cookie('myCook2', 'asgasd#%dfgsdfg');
    	$r->setCookie($c);
    }
    
    public function testAppendContent()
    {
    	$r = new Response();

    	ob_start();
    	// First data
    	$r->appendContent('abc');

    	
    	// n-data
    	$r->appendContent('123456');
    	$this->assertEquals('abc123456', ob_get_clean());
    }
}
