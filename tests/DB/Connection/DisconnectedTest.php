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


require_once __DIR__ .  '/../../path.inc.php';
require_once __DIR__ .  '/../SampleSchema.class.php';

use toolib\DB\Connection;

class Connection_DisconnectedTest extends PHPUnit_Framework_TestCase
{

    public function testSetCharSet()
    {
        $this->setExpectedException('toolib\NotConnectedException');
        Connection::setCharset('utf8');
    }

    public function testGetLink()
    {
        $this->setExpectedException('toolib\NotConnectedException');
        Connection::getLink();
    }

    public function testEscapeString()
    {
        $this->setExpectedException('toolib\NotConnectedException');
        Connection::escapeString('test');
    }

    public function testLastInsertId()
    {
        $this->setExpectedException('toolib\NotConnectedException');
        Connection::getLastInsertId();
    }

    public function testIsKeyUsed()
    {
        $this->setExpectedException('toolib\NotConnectedException');
        Connection::isKeyUsed('test');
    }

    public function testisConnected()
    {
        $this->assertFalse(Connection::isConnected());
    }

    public function testPrepare()
    {
        $this->setExpectedException('toolib\NotConnectedException');
        Connection::prepare('test', 'select * from dummy');
    }

    public function testMultiPrepare()
    {
        $this->setExpectedException('toolib\NotConnectedException');
        Connection::multiPrepare(array('test' => 'select * from dummy'));
    }

    public function testRelease()
    {
        $this->setExpectedException('toolib\NotConnectedException');
        Connection::release('test');
    }

    public function testQuery()
    {
        $this->setExpectedException('toolib\NotConnectedException');
        Connection::query('select * from dummy');
    }

    public function testQueryFetchAll()
    {
        $this->setExpectedException('toolib\NotConnectedException');
        Connection::queryFetchAll('select * from dummy');
    }

    public function testExecute()
    {
        $this->setExpectedException('toolib\NotConnectedException');
        Connection::execute('test');
    }

    public function testExecuteFetchAll()
    {
        $this->setExpectedException('toolib\NotConnectedException');
        Connection::executeFetchAll('test');
    }
}
