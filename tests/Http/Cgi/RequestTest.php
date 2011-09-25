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



	public function SimpleCgiCase()
	{
		$server = array(
			'SERVER_SOFTWARE' => 'toolib',
			'SERVER_NAME' => 'localhost',
			'GATEWAY_INTERFACE' => 'CGI/1.1',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'SERVER_PORT' => 80,
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => null,
			'SCRIPT_NAME' => '/index.php',
			'REQUEST_URI' => '/',
			'QUERY_STRING' => null,
			'REMOTE_HOST' => '',
			'REMOTE_ADDR' => '',
			'CONTENT_TYPE' => 'text/html',
			'CONTENT_LENGTH' => null,
			'HTTP_HOST' => 'localhost'
		);

		$get = array();
		$post = array();
		$files = array();
		return array('server' => $server, 'get' => $get, 'post' => $post, 'files' => $files);
	}

	public function ComplexCgiPostCase()
	{
		$server = array(
			'SERVER_SOFTWARE' => 'toolib',
			'SERVER_NAME' => 'my.host.com',
			'GATEWAY_INTERFACE' => 'CGI/1.1',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'SERVER_PORT' => 8080,
			'REQUEST_METHOD' => 'POST',
			'PATH_INFO' => null,
			'SCRIPT_NAME' => '/alias/to/page.php',
			'REQUEST_URI' => '/alias/to/page.php?getya=3%3Dasdfas&getyb-df=123#fragmented',
			'QUERY_STRING' => 'getya=3%3Dasdfas&getyb-df=123',
			'REMOTE_HOST' => '',
			'REMOTE_ADDR' => '',
			'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
			'CONTENT_LENGTH' => 37,
			'HTTP_HOST' => 'my.example.com:8080'
			
		);

		$content = 'postya=3%3Dqwertyuiop&getyb-df=zaqwsx';
		$get = array(
			'getya' => '3=asdfas',
			'getyb-df' => '123'
		);
		$post = array(
			'postya' => '3=qwertyuiop',
			'postyb-df' => 'zaqwsx'
		);
		$files = array();
		return array('server' => $server, 'get' => $get, 'post' => $post, 'files' => $files);
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
			'PATH_INFO' => '/do/path/info',
			'SCRIPT_NAME' => '/alias/to/page.php',
			'REQUEST_URI' => '/alias/to/page.php/do/path/info?a[]=value1&a[]=value2&bite[popo]=value3#fragmented',
			'QUERY_STRING' => 'a[]=value1&a[]=value2&bite[popo]=value3',
			'REMOTE_HOST' => '',
			'REMOTE_ADDR' => '',
			'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
			'CONTENT_LENGTH' => null,
			'HTTP_HOST' => 'my.example.com:8080',
			'HTTPS' => 'HTTPS'
		);

		$content = 'postya=3%3Dqwertyuiop&getyb-df=zaqwsx';
		$get = array(
			'a' => array('value1', 'value2'),
			'bite' => array('popo' => 'value3')
		);
		$post = array();
		$files = array();
		return array('server' => $server, 'get' => $get, 'post' => $post, 'files' => $files);
	}

	public function PostFilesCgiCase()
	{
		$server = array(
				'SERVER_SOFTWARE' => 'toolib',
				'SERVER_NAME' => 'my.host.com',
				'GATEWAY_INTERFACE' => 'CGI/1.1',
				'SERVER_PROTOCOL' => 'HTTP/1.1',
				'SERVER_PORT' => 8080,
				'REQUEST_METHOD' => 'POST',
				'PATH_INFO' => null,
				'SCRIPT_NAME' => '/alias/to',
				'REQUEST_URI' => '/alias/to?a[]=value1&a[]=value2&bite[popo]=value3#fragmented',
				'QUERY_STRING' => 'a[]=value1&a[]=value2&bite[popo]=value3',
				'REMOTE_HOST' => '',
				'REMOTE_ADDR' => '',
				'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
				'CONTENT_LENGTH' => null,
				'HTTP_HOST' => 'my.example.com:8080',
				'HTTPS' => 'HTTPS'
		);

		$content = 'postya=3%3Dqwertyuiop&getyb-df=zaqwsx';
		$get = array(
			'a' => array('value1', 'value2'),
			'bite' => array('popo' => 'value3')
		);
		$post = array(
			'root' => 'value1',
			'form1' => array('popo' => 'value3'));
		$files = array(
			'single' =>	array(
				'name' => 'Single_file_pdf.pdf',
				'type' => 'application/pdf',
				'tmp_name' => '/tmp/deadlink12345',
				'error' => 0,
				'size' => 349225,
			),
			'form1' =>  array (
				'name' =>  array (
					'single1' => 'Embeded_file1.png',
					'single2' => '',
					'form2' =>  array (
						'multi' => array (
							0 => 'Multi file 1.pdf',
							1 => 'Multi file 2.png',
						),
					),
				),
				'type' => array (
					'single1' => 'image/png',
					'single2' => '',
					'form2' =>  array (
						'multi' => array (
							0 => 'application/pdf',
							1 => 'image/png',
						),
					),
				),
				'tmp_name' => array (
					'single1' => '/tmp/deadlinksingle1',
					'single2' => '',
					'form2' =>	array (
						'multi' => array (
							0 => '/tmp/deadlinkmulti1',
							1 => '/tmp/deadlinkmulti2',
						),
					),
				),
				'error' => array (
					'single1' => 0,
					'single2' => 4,
					'form2' => array (
						'multi' => array (
							0 => 0,
							1 => 0,
						),
					),
				),
				'size' => array (
					'single1' => 38452,
					'single2' => 0,
					'form2' => array (
						'multi' => array (
							0 => 3811120,
							1 => 42352,
						),
					),
				),
			),
		);
		return array('server' => $server, 'get' => $get, 'post' => $post, 'files' => $files);
	}

	public function loadCGIEnviroment($data)
	{
		$_SERVER = $data['server'];
		$_GET = $data['get'];
		$_POST = $data['post'];
		$_FILES = $data['files'];
	}

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

	public function testSimpleCGI()
	{
		$this->loadCGIEnviroment($this->SimpleCgiCase());
		$r = new Request();

		$this->assertEquals('/', $r->getRequestUri());
		$this->assertEquals('/', $r->getUriPath());
		$this->assertEquals('/', $r->getScriptPath());
		$this->assertNull($r->getPath());
		$this->assertNull($r->getFragment());
		$this->assertNull($r->getContent());
		$this->assertEmpty($r->getRawContent());
		$this->assertNull($r->getQueryString());

		$this->commonDefaultConditions($r);

		$this->assertEquals(0, count($r->getQuery()));
		$this->assertEquals(1, count($r->getHeaders()));
		$this->assertTrue($r->getHeaders()->is('Host', 'localhost'));
	}

	public function testComplexCgiPostCase()
	{
		$this->loadCGIEnviroment($this->ComplexCgiPostCase());
		$r = new Request();

		$this->assertEquals('/alias/to/page.php?getya=3%3Dasdfas&getyb-df=123#fragmented', $r->getRequestUri());
		$this->assertEquals('/alias/to/page.php', $r->getUriPath());
		$this->assertEquals('/alias/to/page.php', $r->getScriptPath());
		$this->assertNull($r->getPath());
		$this->assertEquals('fragmented', $r->getFragment());
		$this->assertInstanceOf('\toolib\Http\ParameterContainer', $r->getContent());
		$this->assertEmpty($r->getRawContent());
		$this->assertEquals('getya=3%3Dasdfas&getyb-df=123', $r->getQueryString());

		$this->commonDefaultConditions($r, true);

			
		$this->assertEquals(1, count($r->getHeaders()));
		$this->assertTrue($r->getHeaders()->is('Host', 'my.example.com:8080'));
			
		// Check query
		$this->assertEquals(2, count($r->getQuery()));
		$this->assertEquals('3=asdfas', $r->getQuery()->get('getya'));
		$this->assertEquals('123', $r->getQuery()->get('getyb-df'));
			
		// Check post
		$this->assertEquals(2, count($r->getContent()));
		$this->assertEquals('3=qwertyuiop', $r->getContent()->get('postya'));
		$this->assertEquals('zaqwsx', $r->getContent()->get('postyb-df'));
	}


	public function testComplexQsCgiCase()
	{
		$this->loadCGIEnviroment($this->ComplexQsCgiCase());
		$r = new Request();

		$this->assertEquals('/alias/to/page.php/do/path/info?a[]=value1&a[]=value2&bite[popo]=value3#fragmented', $r->getRequestUri());
		$this->assertEquals('/alias/to/page.php/do/path/info', $r->getUriPath());
		$this->assertEquals('/alias/to/page.php', $r->getScriptPath());
		$this->assertEquals('/do/path/info', $r->getPath());
		$this->assertEquals('fragmented', $r->getFragment());
		$this->assertNull($r->getContent());
		$this->assertEmpty($r->getRawContent());
		$this->assertEquals('a[]=value1&a[]=value2&bite[popo]=value3', $r->getQueryString());

		$this->commonDefaultConditions($r, false, true);


		$this->assertEquals(1, count($r->getHeaders()));
		$this->assertTrue($r->getHeaders()->is('Host', 'my.example.com:8080'));

		// Check query
		$this->assertEquals(2, count($r->getQuery()));
		$this->assertInstanceOf('\toolib\Http\ParameterContainer', $r->getQuery()->get('a'));
		$this->assertEquals(array('value1', 'value2'), $r->getQuery()->get('a')->getArrayCopy());
		$this->assertInstanceOf('\toolib\Http\ParameterContainer', $r->getQuery()->get('bite'));
		$this->assertEquals(array('popo' => 'value3'), $r->getQuery()->get('bite')->getArrayCopy());

	}

	public function testPostFilesCase()
	{
		$this->loadCGIEnviroment($this->PostFilesCgiCase());
		$r = new Request();
	
		$this->assertEquals('/alias/to?a[]=value1&a[]=value2&bite[popo]=value3#fragmented', $r->getRequestUri());
		$this->assertEquals('/alias/to', $r->getUriPath());
		$this->assertEquals('/alias/to', $r->getScriptPath());
		$this->assertNull($r->getPath());
		$this->assertEquals('fragmented', $r->getFragment());		
		$this->assertEmpty($r->getRawContent());
		$this->assertEquals('a[]=value1&a[]=value2&bite[popo]=value3', $r->getQueryString());
	
		$this->commonDefaultConditions($r, true, true);
	
	
		$this->assertEquals(1, count($r->getHeaders()));
		$this->assertTrue($r->getHeaders()->is('Host', 'my.example.com:8080'));
	
		// Check query
		$this->assertEquals(2, count($r->getQuery()));
		$this->assertInstanceOf('\toolib\Http\ParameterContainer', $r->getQuery()->get('a'));
		$this->assertEquals(array('value1', 'value2'), $r->getQuery()->get('a')->getArrayCopy());
		$this->assertInstanceOf('\toolib\Http\ParameterContainer', $r->getQuery()->get('bite'));
		$this->assertEquals(array('popo' => 'value3'), $r->getQuery()->get('bite')->getArrayCopy());
	
		// Check posted files and values
		$this->assertInstanceOf('\toolib\Http\ParameterContainer', $r->getContent());
		$this->assertEquals('value1', $r->getContent()->get('root'));		
		$this->assertInstanceOf('\toolib\Http\ParameterContainer', $r->getContent()->get('form1'));
		$this->assertEquals(4, count($r->getContent()->get('form1')));
		$this->assertEquals('value3', $r->getContent()->get('form1')->get('popo'));
		
		$file =$r->getContent()->get('single');
		$this->assertInstanceOf('\toolib\Http\UploadedFile', $file);
		$this->assertEquals('Single_file_pdf.pdf', $file->getName());
		$this->assertEquals('/tmp/deadlink12345', $file->getTempName());
		$this->assertEquals(0, $file->getError());
		$this->assertEquals(349225, $file->getSize());
		$this->assertTrue($file->isSubmitted());
		$this->assertTrue($file->isValid());

		$file =$r->getContent()->get('form1')->get('single1');
		$this->assertInstanceOf('\toolib\Http\UploadedFile', $file);
		$this->assertEquals('Embeded_file1.png', $file->getName());
		$this->assertEquals('/tmp/deadlinksingle1', $file->getTempName());
		$this->assertEquals(0, $file->getError());
		$this->assertEquals(38452, $file->getSize());
		$this->assertTrue($file->isSubmitted());
		$this->assertTrue($file->isValid());
		
		$file =$r->getContent()->get('form1')->get('single2');
		$this->assertInstanceOf('\toolib\Http\UploadedFile', $file);
		$this->assertEquals('', $file->getName());
		$this->assertEquals('', $file->getTempName());
		$this->assertEquals(UPLOAD_ERR_NO_FILE, $file->getError());
		$this->assertEquals(0, $file->getSize());
		$this->assertFalse($file->isSubmitted());
		$this->assertFalse($file->isValid());

		$file =$r->getContent()->get('form1')->get('form2')->get('multi')->get(0);
		$this->assertInstanceOf('\toolib\Http\UploadedFile', $file);
		$this->assertEquals('Multi file 1.pdf', $file->getName());
		$this->assertEquals('/tmp/deadlinkmulti1', $file->getTempName());
		$this->assertEquals(0, $file->getError());
		$this->assertEquals(3811120, $file->getSize());
		$this->assertTrue($file->isSubmitted());
		$this->assertTrue($file->isValid());

		$file =$r->getContent()->get('form1')->get('form2')->get('multi')->get(1);
		$this->assertInstanceOf('\toolib\Http\UploadedFile', $file);
		$this->assertEquals('Multi file 2.png', $file->getName());
		$this->assertEquals('/tmp/deadlinkmulti2', $file->getTempName());
		$this->assertEquals(0, $file->getError());
		$this->assertEquals(42352, $file->getSize());
		$this->assertTrue($file->isSubmitted());
		$this->assertTrue($file->isValid());
	}

	public function testGetEnviroment()
	{
		$this->loadCGIEnviroment($this->PostFilesCgiCase());
		$r = new Request();
		
		$this->assertEquals($_SERVER, $r->getEnviroment());
	}
}
