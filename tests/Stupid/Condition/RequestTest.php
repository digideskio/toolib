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


use toolib\Http;
use toolib\Stupid\Knowledge;
use toolib\Stupid\Condition;
require_once __DIR__ .  '/../../path.inc.php';

class Stupid_RequestTest extends PHPUnit_Framework_TestCase
{

	public function testEmptyConstruct()
	{
		$g = new Http\Mock\Gateway();
		$k = new Knowledge(array('request.gateway' => $g));
		
		$c = new Condition\Request();
		$c->methodIsGet()
			->pathIs('/');
		$this->assertTrue($c($k));
		
		$c = new Condition\Request();
		$c->methodIsPost()
			->pathIs('/');
		$this->assertFalse($c($k));
	}
	
	public function testPathIs()
	{
		$g = new Http\Mock\Gateway();
		$g->setRequest(new Http\Mock\Request('/big/example/path/'));
		$k = new Knowledge(array('request.gateway' => $g));
		
		$c = new Condition\Request();
		$c->pathIs('/big/example/path/');
		$this->assertTrue($c($k));
		$this->assertEquals('/big/example/path/', $k->{'request.path_matched'});
		$k->extractFacts();
		$this->assertEquals('/big/example/path/', $k->getFact('request.path_prefix'));
		
		// Subpath cannot be partially valid
		$k = new Knowledge(array('request.gateway' => $g));
		$c = new Condition\Request();
		$c->pathIs('/big/example/path');
		$this->assertFalse($c($k));
		$this->assertFalse(isset($k->results['request.path_matched']));
		$k->extractFacts();
		$this->assertNull($k->getOptionalFact('request.path_prefix', null));
		
		// Take into account path prefix
		$k = new Knowledge(array('request.gateway' => $g, 'request.path_prefix' => '/big'));
		$c = new Condition\Request();
		$c->pathIs('/example/path/');
		$this->assertTrue($c($k));
		$this->assertEquals('/example/path/', $k->{'request.path_matched'});
		$k->extractFacts();
		$this->assertEquals('/big/example/path/', $k->getFact('request.path_prefix'));
		
		// Path prefix check must be done
		$k = new Knowledge(array('request.gateway' => $g, 'request.path_prefix' => '/bog'));
		$c = new Condition\Request();
		$c->pathIs('/example/path/');
		$this->assertFalse($c($k));
		$this->assertFalse(isset($k->results['request.path_matched']));
		$k->extractFacts();
		$this->assertEquals('/bog', $k->getFact('request.path_prefix'));
	}
	
	public function testPathRegexIs()
	{
		$g = new Http\Mock\Gateway();
		$g->setRequest(new Http\Mock\Request('/big/example/15/path/'));
		$k = new Knowledge(array('request.gateway' => $g));
		
		$c = new Condition\Request();
		$k = new Knowledge(array('request.gateway' => $g));
		$c->pathRegexIs('/example/');
		$this->assertTrue($c($k));
		$this->assertEquals('/big/example/15/path/', $k->{'request.path_matched'});
		$k->extractFacts();
		$this->assertEquals('/big/example/15/path/', $k->getFact('request.path_prefix'));

		$c = new Condition\Request();
		$k = new Knowledge(array('request.gateway' => $g, 'request.path_prefix' => '/big'));
		$c->pathRegexIs('#^/example/(?<num>[[:digit:]]+)/path/$#');
		$this->assertTrue($c($k));
		$this->assertEquals('/example/15/path/', $k->{'request.path_matched'});
		$this->assertEquals(array('num' => 15), $k->{'request.params'});
		$k->extractFacts();
		$this->assertEquals('/big/example/15/path/', $k->getFact('request.path_prefix'));
		
		// Prefix must be the same to be valid
		$c = new Condition\Request();
		$k = new Knowledge(array('request.gateway' => $g, 'request.path_prefix' => '/bog'));
		$c->pathRegexIs('#^/example/15/path/$#');
		$this->assertFalse($c($k));
		$this->assertFalse(isset($k->results['request.path_matched']));
		$k->extractFacts();
		$this->assertEquals('/bog', $k->getFact('request.path_prefix'));
		
		// Do not match on prefix
		$c = new Condition\Request();
		$k = new Knowledge(array('request.gateway' => $g, 'request.path_prefix' => '/big'));
		$c->pathRegexIs('#/big#');
		$this->assertFalse($c($k));
		$this->assertFalse(isset($k->results['request.path_matched']));
		$k->extractFacts();
		$this->assertEquals('/big', $k->getFact('request.path_prefix'));
	}
	
	public function testPathPatternIs()
	{
		$g = new Http\Mock\Gateway();
		$g->setRequest(new Http\Mock\Request('/some/more/ofthis/stuff'));
		
		// Standard verification
		$c = new Condition\Request();
		$k = new Knowledge(array('request.gateway' => $g));
		$c->pathPatternIs('/some/more/{what}/stuff');
		$this->assertTrue($c($k));
		$this->assertEquals('/some/more/ofthis/stuff', $k->{'request.path_matched'});
		$this->assertEquals(array('what' => 'ofthis'), $k->{'request.params'});
		$k->extractFacts();
		$this->assertEquals('/some/more/ofthis/stuff', $k->getFact('request.path_prefix'));

		// Litteral fixed pattern
		$c = new Condition\Request();
		$k = new Knowledge(array('request.gateway' => $g));
		$c->pathPatternIs('/some/more/ofthis/stuff');
		$this->assertTrue($c($k));
		$this->assertEquals('/some/more/ofthis/stuff', $k->{'request.path_matched'});
		$this->assertEquals(array(), $k->{'request.params'});
		$k->extractFacts();
		$this->assertEquals('/some/more/ofthis/stuff', $k->getFact('request.path_prefix'));
		
		// Complicated pattern
		$g->setRequest(new Http\Mock\Request('/something/10/more/literal-id.html'));
		$c = new Condition\Request();
		$k = new Knowledge(array('request.gateway' => $g, 'request.path_prefix' => '/something'));
		$c->pathPatternIs('/{id}/more/{id2}.{ext}', array('id' => '[[:digit:]]+', 'id2' => '[\w\-]+'));
		$this->assertTrue($c($k));
		$this->assertEquals('/10/more/literal-id.html', $k->{'request.path_matched'});
		$this->assertEquals(array('id' => 10, 'id2' => 'literal-id', 'ext' => 'html'), $k->{'request.params'});
		$k->extractFacts();
		$this->assertEquals('/something/10/more/literal-id.html', $k->getFact('request.path_prefix'));

		// Extensions support
		$g->setRequest(new Http\Mock\Request('/something/10/more/literal-id.html'));
		$c = new Condition\Request();
		$k = new Knowledge(array('request.gateway' => $g, 'request.path_prefix' => '/something'));
		$c->pathPatternIs('/{id}/more/{id2}{ext}', array('id' => '[[:digit:]]+', 'id2' => '[\w\-]+', 'ext' => '.html|'));
		$this->assertTrue($c($k));
		$this->assertEquals('/10/more/literal-id.html', $k->{'request.path_matched'});
		$this->assertEquals(array('id' => 10, 'id2' => 'literal-id', 'ext' => '.html'), $k->{'request.params'});
		$k->extractFacts();
		$this->assertEquals('/something/10/more/literal-id.html', $k->getFact('request.path_prefix'));
		
		// Wrong Match based on requirements
		$g->setRequest(new Http\Mock\Request('/something/10a/more/literal-id.html'));
		$c = new Condition\Request();
		$k = new Knowledge(array('request.gateway' => $g, 'request.path_prefix' => '/something'));
		$c->pathPatternIs('/{id}/more/{id2}.{ext}', array('id' => '[[:digit:]]+', 'id2' => '[\w\-]+'));
		$this->assertFalse($c($k));
		$this->assertFalse(isset($k->results['request.path_matched']));
		$this->assertFalse(isset($k->results['request.params']));
		$k->extractFacts();
		$this->assertEquals('/something', $k->getFact('request.path_prefix'));
	}
	
	public function testMethodIs()
	{
		$g = new Http\Mock\Gateway();
		$k = new Knowledge(array('request.gateway' => $g));
		
		$c = new Condition\Request();
		$c->methodIsGet();
		$this->assertTrue($c($k));
		
		$c = Condition\Request::create()->methodIsPost();
		$this->assertFalse($c($k));
		
		$g->setRequest($r = new Http\Mock\Request('/', 'blabla'));
		$c = Condition\Request::create()->methodIsPost();
		$this->assertTrue($c($k));
		
		$g->setRequest($r = new Http\Mock\Request('/', 'blabla'));
		$c = Condition\Request::create()->methodIsGet();
		$this->assertFalse($c($k));
		
		$r->setMethod('put');
		$c = Condition\Request::create()->methodIsPut();
		$this->assertTrue($c($k));
		
		$r->setMethod('Delete');
		$c = Condition\Request::create()->methodIsDelete();
		$this->assertTrue($c($k));
		
		$r->setMethod('head');
		$c = Condition\Request::create()->methodIsHead();
		$this->assertTrue($c($k));
	}
	
	public function testQueryParam()
	{
		$g = new Http\Mock\Gateway();
		$g->setRequest($r = new Http\Mock\Request('/apath.php?array_param[]=a&array_param[]=b&intparm=10&foo=barbobi'));
		$k = new Knowledge(array('request.gateway' => $g));
		
		// All true
		$c = new Condition\Request();
		$c->queryParamIs('intparm', 10)
			->queryParamRegexIs('intparm', '/^\d+$/')
			->queryParamIs('foo', 'barbobi')
			->queryParamRegexIs('foo', '/barbobi/');
		$this->assertTrue($c($k));
		
		// One fail
		$c = new Condition\Request();
		$c->queryParamIs('intparm', 15)
			->queryParamIs('foo', 'bar');
		$this->assertFalse($c($k));
		
		// param fail
		$c = new Condition\Request();
		$c->queryParamIs('array_param', 15);
		$this->assertFalse($c($k));
		
		$c = new Condition\Request();
		$c->queryParamRegexIs('intparm', '/^a\d+$/');
		$this->assertFalse($c($k));
	}
	
	public function testContentParam()
	{
		$g = new Http\Mock\Gateway();
		$g->setRequest($r = new Http\Mock\Request('/apath.php', 'array_param[]=a&array_param[]=b&intparm=10&foo=barbobi'));
		$k = new Knowledge(array('request.gateway' => $g));
	
		// All true
		$c = new Condition\Request();
		$c->methodIsPost()->contentParamIs('intparm', 10)
		->contentParamRegexIs('intparm', '/^\d+$/')
		->contentParamIs('foo', 'barbobi')
		->contentParamRegexIs('foo', '/barbobi/');
		$this->assertTrue($c($k));
	
		// One fail
		$c = new Condition\Request();
		$c->contentParamIs('intparm', 15)
		->contentParamIs('foo', 'bar');
		$this->assertFalse($c($k));
	
		// param fail
		$c = new Condition\Request();
			$c->contentParamIs('array_param', 15);
		$this->assertFalse($c($k));
	
		$c = new Condition\Request();
		$c->contentParamRegexIs('intparm', '/^a\d+$/');
		$this->assertFalse($c($k));
	}
}
