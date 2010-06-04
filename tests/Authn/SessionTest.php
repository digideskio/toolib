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
require_once dirname(__FILE__) . '/SampleSchema.class.php';

class Authn_SessionTest extends PHPUnit_Framework_TestCase
{

    public function testInstanceSession()
    {
        $stor = new Authn_Session_Instance();

        $this->assertFalse($stor->get_identity());

        $stor->set_identity(new Authn_Identity_DB(true,true,true));
        $this->assertType('Authn_Identity_DB', $stor->get_identity());

        $stor->clear_identity();
        $this->assertFalse($stor->get_identity());
    }

    public function testNativeSession()
    {
        $stor = new Authn_Session_Native();

        $this->assertFalse($stor->get_identity());

        @$stor->set_identity(new Authn_Identity_DB(true,true,true));
        $this->assertType('Authn_Identity_DB', $stor->get_identity());

        $stor->clear_identity();
        $this->assertFalse($stor->get_identity());
    }

}
?>
