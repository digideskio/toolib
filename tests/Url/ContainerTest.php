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

class ContainerTest extends \PHPUnit_Framework_TestCase
{
	public function test()
	{
		$c = new Url\Container();		
		$this->assertNull($c->open('wrong'));
		
		$this->assertInstanceOf('toolib\Url\ResourceConstructor', $r = $c->create('url1', '/test/{key}'));
		$this->assertEquals('url1', $r->getName());
		$this->assertSame($r, $c->open('url1'));
		
		$c->createMultiple(array(
			'url2' => '/test/{key3}',
			'url3' => '/test/{key4}'
		));
		$this->assertEquals('url1', $c->open('url1')->getName());
		$this->assertEquals('url2', $c->open('url2')->getName());
		$this->assertEquals('url3', $c->open('url3')->getName());
		$this->assertEquals('/test/{key4}', $c->open('url3')->getPattern());
	}
	
	
	
}
