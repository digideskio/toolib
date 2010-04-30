<?php
require_once 'PHPUnit/Framework.php';
require_once __DIR__ .  '/../path.inc.php';

class Net_CookieTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {   
        $cookie = new Net_HTTP_Cookie('testname1', 'testvalue');
        $this->assertEquals($cookie->get_name(), 'testname1');
        $this->assertEquals($cookie->get_value(), 'testvalue');
        $this->assertEquals($cookie->get_domain(), '');
        $this->assertEquals($cookie->get_path(), '/');
        $this->assertEquals($cookie->get_expiration_time(), 0);
        $this->assertEquals($cookie->is_session_cookie(), true);
        $this->assertEquals($cookie->is_secure(), false);
        $this->assertEquals($cookie->is_httponly(), false);

        $cookie = new Net_HTTP_Cookie('testname1', 'testvalue', time() + 500, '/test/path', 'test.domain.com', true, false);
        $this->assertEquals($cookie->get_name(), 'testname1');
        $this->assertEquals($cookie->get_value(), 'testvalue');
        $this->assertEquals($cookie->get_domain(), 'test.domain.com');
        $this->assertEquals($cookie->get_path(), '/test/path');
        $this->assertEquals($cookie->get_expiration_time(), time() + 500);
        $this->assertEquals($cookie->is_session_cookie(), false);
        $this->assertEquals($cookie->is_secure(), true);
        $this->assertEquals($cookie->is_httponly(), false);

        $cookie = new Net_HTTP_Cookie('testname1', 'testvalue', time() + 300, '/test/path', 'test.domain.com', false, true);
        $this->assertEquals($cookie->get_name(), 'testname1');
        $this->assertEquals($cookie->get_value(), 'testvalue');
        $this->assertEquals($cookie->get_domain(), 'test.domain.com');
        $this->assertEquals($cookie->get_path(), '/test/path');
        $this->assertEquals($cookie->get_expiration_time(), time() + 300);
        $this->assertEquals($cookie->is_session_cookie(), false);
        $this->assertEquals($cookie->is_secure(), false);
        $this->assertEquals($cookie->is_httponly(), true);
    }

    public function testSetters()
    {   
        $cookie = new Net_HTTP_Cookie('testname1', 'testvalue');
        $this->assertEquals($cookie->get_name(), 'testname1');
        $this->assertEquals($cookie->get_value(), 'testvalue');
        $this->assertEquals($cookie->get_domain(), '');
        $this->assertEquals($cookie->get_path(), '/');
        $this->assertEquals($cookie->get_expiration_time(), 0);
        $this->assertEquals($cookie->is_session_cookie(), true);
        $this->assertEquals($cookie->is_secure(), false);
        $this->assertEquals($cookie->is_httponly(), false);

        $cookie->set_name('testnamenew');
        $this->assertEquals($cookie->get_name(), 'testnamenew');

        $cookie->set_value('testvaluenew');
        $this->assertEquals($cookie->get_value(), 'testvaluenew');

        $cookie->set_domain('my.domain.com');
        $this->assertEquals($cookie->get_domain(), 'my.domain.com');

        $cookie->set_path('/path/pp/test');
        $this->assertEquals($cookie->get_path(), '/path/pp/test');

        $cookie->set_expiration_time(time()+112);
        $this->assertEquals($cookie->get_expiration_time(), time()+112);
        $this->assertFalse($cookie->is_session_cookie());
        
        $cookie->set_secure(true);
        $this->assertTrue($cookie->is_secure());

        $cookie->set_httponly(true);
        $this->assertTrue($cookie->is_httponly());

        $this->assertEquals($cookie->get_name(), 'testnamenew');
        $this->assertEquals($cookie->get_value(), 'testvaluenew');
        $this->assertEquals($cookie->get_domain(), 'my.domain.com');
        $this->assertEquals($cookie->get_path(), '/path/pp/test');
        $this->assertEquals($cookie->get_expiration_time(), time() + 112);
        $this->assertEquals($cookie->is_session_cookie(), false);
        $this->assertEquals($cookie->is_secure(), true);
        $this->assertEquals($cookie->is_httponly(), true);
    }
}
?>