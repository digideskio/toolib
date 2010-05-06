<?php
/**
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
require_once __DIR__ .  '/../../path.inc.php';

class Conn_DisconnectedTest extends PHPUnit_Framework_TestCase
{

    public function testSetCharSet()
    {
        $this->setExpectedException('NotConnectedException');
        DB_Conn::set_charset('utf8');
    }

    public function testGetLink()
    {
        $this->setExpectedException('NotConnectedException');
        DB_Conn::get_link();
    }

    public function testEscapeString()
    {
        $this->setExpectedException('NotConnectedException');
        DB_Conn::escape_string('test');
    }

    public function testLastInsertId()
    {
        $this->setExpectedException('NotConnectedException');
        DB_Conn::last_insert_id();
    }

    public function testIsKeyUsed()
    {
        $this->setExpectedException('NotConnectedException');
        DB_Conn::is_key_used('test');
    }

    public function testisConnected()
    {
        $this->assertFalse(DB_Conn::is_connected());
    }

    public function testPrepare()
    {
        $this->setExpectedException('NotConnectedException');
        DB_Conn::prepare('test', 'select * from dummy');
    }

    public function testMultiPrepare()
    {
        $this->setExpectedException('NotConnectedException');
        DB_Conn::multi_prepare(array('test' => 'select * from dummy'));
    }

    public function testRelease()
    {
        $this->setExpectedException('NotConnectedException');
        DB_Conn::release('test');
    }

    public function testQuery()
    {
        $this->setExpectedException('NotConnectedException');
        DB_Conn::query('select * from dummy');
    }

    public function testQueryFetchAll()
    {
        $this->setExpectedException('NotConnectedException');
        DB_Conn::query_fetch_all('select * from dummy');
    }

    public function testExecute()
    {
        $this->setExpectedException('NotConnectedException');
        DB_Conn::execute('test');
    }

    public function testExecuteFetchAll()
    {
        $this->setExpectedException('NotConnectedException');
        DB_Conn::execute_fetch_all('test');
    }
}
?>
