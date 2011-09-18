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


use \toolib\Authz\Ace;
require_once __DIR__ .  '/../path.inc.php';

class Authz_AceTest extends PHPUnit_Framework_TestCase
{
    public function testGeneral()
    {
        $ace = new Ace('admin', 'read', false);
        $this->assertFalse($ace->isAllowed());
        $this->assertEquals($ace->getRole(), 'admin');
        $this->assertEquals($ace->getAction(), 'read');

        $ace2 = new Ace('@devs', 'write', true);
        $this->assertTrue($ace2->isAllowed());
        $this->assertEquals($ace2->getRole(), '@devs');
        $this->assertEquals($ace2->getAction(), 'write');
    }
    
    public function testSetAllowed()
    {
        $ace = new Ace('admin', 'read', false);
        $this->assertFalse($ace->isAllowed());
        $ace->setAllowed(true);
        $this->assertTrue($ace->isAllowed());
        $ace->setAllowed(false);
        $this->assertFalse($ace->isAllowed());
    }
    
    public function testIsRoleNull()
    {
        $ace = new Ace('admin', 'read', false);
        $this->assertFalse($ace->isRoleNull());
        $ace = new Ace('0', null, false);
        $this->assertFalse($ace->isRoleNull());
        $ace = new Ace(0, null, false);
        $this->assertFalse($ace->isRoleNull());
        $ace = new Ace(null, null, false);
        $this->assertTrue($ace->isRoleNull());

    }
}
