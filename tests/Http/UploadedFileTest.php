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

use toolib\Http\UploadedFile;

require_once __DIR__ .  '/../path.inc.php';

class Http_UploadedFileTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
    	$u = new UploadedFile('nicename.jpg', 'image/jpeg', '/tmp/dead.link', 12345, 10);

    	$this->assertEquals(10, $u->getError());
    	$this->assertEquals('nicename.jpg', $u->getName());
    	$this->assertEquals('/tmp/dead.link', $u->getTempName());
    	$this->assertEquals(12345, $u->getSize());
    	
    	$this->assertEquals('nicename.jpg', (string)$u);
    }
    
    public function testDelete()
    {
    	$u = new UploadedFile('nicename.jpg', 'image/jpeg', '/tmp/toolib-uploadead-file-test.txt', 12345, 10);
    	
    	// Delete unexisting should not cause problems
    	$this->assertNull($u->delete());
    	
    	file_put_contents('/tmp/toolib-uploadead-file-test.txt', 'this is test');
    	$this->assertTrue(file_exists('/tmp/toolib-uploadead-file-test.txt'));
    	
    	$this->assertNull($u->delete());
    	$this->assertFalse(file_exists('/tmp/toolib-uploadead-file-test.txt'));
    }
    
}
