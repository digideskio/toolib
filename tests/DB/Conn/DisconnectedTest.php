<?php
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
