<?php
require_once 'PHPUnit/Framework.php';
require_once __DIR__ .  '/../path.inc.php';
require_once __DIR__ . '/SampleSchema.class.php';

class Auth_StorageTest extends PHPUnit_Framework_TestCase
{

    public function testInstanceStorage()
    {   $stor = new Auth_Storage_Instance();

    $this->assertFalse($stor->get_identity());

    $stor->set_identity(new Auth_Identity_DB(true,true,true));
    $this->assertType('Auth_Identity_DB', $stor->get_identity());

    $stor->clear_identity();
    $this->assertFalse($stor->get_identity());
    }

    public function testSessionStorage()
    {   $stor = new Auth_Storage_Session();

    $this->assertFalse($stor->get_identity());

    $stor->set_identity(new Auth_Identity_DB(true,true,true));
    $this->assertType('Auth_Identity_DB', $stor->get_identity());

    $stor->clear_identity();
    $this->assertFalse($stor->get_identity());
    }

}
?>
