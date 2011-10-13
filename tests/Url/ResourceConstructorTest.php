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

namespace toolib\tests\Url;

use toolib\Stupid;
use toolib\Url;
use toolib\Http;

require_once __DIR__ .  '/../path.inc.php';

class User
{
	public $name;
	
	public $id;
	
	public function __construct($id, $name)
	{
		$this->name = $name;
		$this->id = $id;
	}
}


class Photo
{
	public $id;
	
	public $owner;
	
	public $desc;
	
	public function __construct($id, User $owner, $desc)
	{
		$this->id = $id;
		$this->owner = $owner;
		$this->desc = $desc;
	}
}

class ResourceConstructorTest extends \PHPUnit_Framework_TestCase
{
	public function testPath()
	{
		$r = new Url\ResourceConstructor('test', '/user/{id}');
		$this->assertEquals('/user/15', $r->path(array('id' => 15)));
		$this->assertEquals('/user/30', $r->path(array('id' => 30)));

		// Two parameters
		$r = new Url\ResourceConstructor('test', '/user/{id}/{id2}');
		$this->assertEquals('/user/15/30', $r->path(array('id' => 15, 'id2' => 30)));
		$this->assertEquals('/user/30/50', $r->path(array('id2' => 50, 'id' => 30, )));
		
		// Unamed parameters
		$r = new Url\ResourceConstructor('test', '/user/{0}/{1}');
		$this->assertEquals('/user/15/30', $r->path(array(15, 30)));
		$this->assertEquals('/user/30/50', $r->path(array(30, 50)));
	}
	
	public function testPropAccess()
	{
		$sally = new User(15, 'Sally');
		$photo = new Photo('32-asd', $sally, 'nicephoto');
		
		$r = new Url\ResourceConstructor('test', '/user/{photo.owner.id}/photo/{photo.id}/{photo.desc}');
		$this->assertEquals('/user/15/photo/32-asd/nicephoto', $r->path(array('photo' => $photo)));
	}
	
	public function testEscaping()
	{
		$sally = new User(15, 'Sally');
		$photo = new Photo('32-asd', $sally, 'Wierd description /!');
		
		$r = new Url\ResourceConstructor('test', '/~{photo.owner.name}/photo/{photo.id}/{photo.desc}');
		$this->assertEquals('/~Sally/photo/32-asd/Wierd+description+%2F%21', $r->path(array('photo' => $photo)));
		$this->assertEquals('/~Sally/photo/32-asd/Wierd description /!', $r->path(array('photo' => $photo), false));
		
		$this->assertEquals('http://www.example.com/~Sally/photo/32-asd/Wierd+description+%2F%21',
			$r->url(array('photo' => $photo), 'www.example.com'));
		$this->assertEquals('http://www.example.com/~Sally/photo/32-asd/Wierd description /!',
			$r->url(array('photo' => $photo), 'www.example.com', false, 80, false));
		
		$req = new Http\Mock\Request('http://www.example.com');
		$this->assertEquals('http://www.example.com/~Sally/photo/32-asd/Wierd+description+%2F%21',
			$r->urlFromRequest($req, array('photo' => $photo)));
		$this->assertEquals('http://www.example.com/~Sally/photo/32-asd/Wierd description /!',
			$r->urlFromRequest($req, array('photo' => $photo), false));
	}
	
	public function testUrl()
	{
		$r = new Url\ResourceConstructor('test', '/user/{id}/photo/{pid}');
		$this->assertEquals('http://www.example.com/user/150/photo/32', 
			$r->url(array('id' => 150, 'pid' => 32), 'www.example.com'));
		$this->assertEquals('http://www.example.com:8080/user/150/photo/32',
			$r->url(array('id' => 150, 'pid' => 32), 'www.example.com', false, 8080));
		
		$this->assertEquals('https://www.example.com/user/150/photo/32',
			$r->url(array('id' => 150, 'pid' => 32), 'www.example.com', true));
		
		$this->assertEquals('https://www.example.com:80/user/150/photo/32',
			$r->url(array('id' => 150, 'pid' => 32), 'www.example.com', true, 80));
		$this->assertEquals('https://www.example.com/user/150/photo/32',
			$r->url(array('id' => 150, 'pid' => 32), 'www.example.com', true, 443));
	}
	
	public function testUrlFromRequest()
	{
		$r = new Url\ResourceConstructor('test', '/user/{id}/photo/{pid}');
		
		$req = new Http\Mock\Request('http://www.example.com/test/to/boom', null);
		$this->assertEquals('http://www.example.com/user/150/photo/32',
			$r->urlFromRequest($req, array('id' => 150, 'pid' => 32)));
		
		$req = new Http\Mock\Request('http://www.example.com:8080/test/to/boom', null);
		$this->assertEquals('http://www.example.com:8080/user/150/photo/32',
			$r->urlFromRequest($req, array('id' => 150, 'pid' => 32)));
	
		$req = new Http\Mock\Request('https://www.example.com/test/to/boom', null);
		$this->assertEquals('https://www.example.com/user/150/photo/32',
			$r->urlFromRequest($req, array('id' => 150, 'pid' => 32)));
	
		$req = new Http\Mock\Request('https://www.example.com:80/test/to/boom', null);
		$this->assertEquals('https://www.example.com:80/user/150/photo/32',
			$r->urlFromRequest($req, array('id' => 150, 'pid' => 32)));
		
	}
	
	
	
}
