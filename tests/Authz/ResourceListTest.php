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


require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) .  '/../path.inc.php';

class Authz_ResourceListTest extends PHPUnit_Framework_TestCase
{
    public function testGeneral()
    {
        $list = new Authz_ResourceList();
        
        $this->assertFalse($list->has_resource('test'));
        $this->assertFalse($list->get_resource('test'));

        $list->add_resource('directory');
        $this->assertTrue($list->has_resource('directory'));
        $this->assertType('Authz_Resource', $list->get_resource('directory'));
/*
        $file_resource = new Authz_Resource('file', 'directory');
        $list->add_resource($file_resource);
        $this->assertTrue($list->has_resource('directory'));
        $this->assertEquals($list->get_resource('directory'), $dir_resource);
        $this->assertTrue($list->has_resource('file'));
        $this->assertEquals($list->get_resource('file'), $file_resource);*/
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testSameResourceException()
    {
        $list = new Authz_ResourceList();
        $list->add_resource('dir');
        $list->add_resource('dir', 'file');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testBrokenDependencyException()
    {
        $list = new Authz_ResourceList();
        $list->add_resource('dir', 'file');
    }
}
?>
