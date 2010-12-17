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


use toolib\Authn\Session;
use toolib\DB\Connection;

require_once __DIR__ .  '/../path.inc.php';
require_once __DIR__ . '/SampleSchema.class.php';

class Authn_SessionTest extends PHPUnit_Framework_TestCase
{
    public function getBackend()
    {
        return new \toolib\Authn\DB\Backend(array(
            'query_user' => User_plain::openQuery()
                ->where('username = ?'),
            'field_username' => 'username',
            'field_password' => 'password'
        ));
    }

    public function testInstanceSession()
    {
        $stor = new Session\Instance();

        $this->assertFalse($stor->getIdentity());

        $stor->setIdentity(new \toolib\Authn\DB\Identity(true,$this->getBackend(),true));
        $this->assertType('toolib\Authn\DB\Identity', $stor->getIdentity());

        $stor->clearIdentity();
        $this->assertFalse($stor->getIdentity());
    }

    public function testNativeSession()
    {
        $stor = new Session\Native();

        $this->assertFalse($stor->getIdentity());

        @$stor->setIdentity(new \toolib\Authn\DB\Identity(true,$this->getBackend(),true));
        $this->assertType('toolib\Authn\DB\Identity', $stor->getIdentity());

        $stor->clearIdentity();
        $this->assertFalse($stor->getIdentity());
    }

}
