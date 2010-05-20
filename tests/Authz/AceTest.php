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

class Authz_AceTest extends PHPUnit_Framework_TestCase
{
    public function testGeneral()
    {
        $ace = new Authz_ACE('admin', 'read', false);
        $this->assertFalse($ace->is_allowed());
        $this->assertEquals($ace->get_role(), 'admin');
        $this->assertEquals($ace->get_action(), 'read');

        $ace2 = new Authz_ACE('@devs', 'write', true);
        $this->assertTrue($ace2->is_allowed());
        $this->assertEquals($ace2->get_role(), '@devs');
        $this->assertEquals($ace2->get_action(), 'write');
    }
}
?>
