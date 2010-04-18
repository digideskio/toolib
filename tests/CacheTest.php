<?php
require_once 'PHPUnit/Framework.php';
require_once __DIR__ .  '/../lib/cache.lib.php';



class CacheTest extends PHPUnit_Framework_TestCase
{
    public static function tearDownAfterClass()
    {   unlink(sys_get_temp_dir() . '/cache.db');   }
    
	public function provider_impl()
	{
		// Tested cache implementations
		return array(
			array(new Cache_Memcached('127.0.0.1')), 
			array(new Cache_File()),
			array(new Cache_Sqlite(sys_get_temp_dir() . '/cache.db')),
			array(new Cache_Apc('test-subscirpts', true))
		);
	}

    public function provider_data1()
    {
        return array(
            array('key1', 123),
            array('key2', 'big string'),
            array('key3', array(123, 'big string')),
            array('key4', array('beta', new stdClass())),
            array('key5', new stdClass()),
            array('wierd_name/!@#$%^&*()_+{}[]:";"\'<>,.?/>', 'hasdfasdf'),
            array('/\\\\wierd_name26123/!@#$%^&*()_+{}[]:";"\'<>,.?/>', 'gasd'),
            array('#/\\\\wierd_name26123/!@#$%^&*()_+{}[]:";"\'<>,.?/>', 'fdh'),
            array('124#/\\\\wierd_name26123/!@#$%^&*()_+{}[]:";"\'<>,.?/>', 'qwer'),
            array('key7', array_fill(0, 50000, '_')),
            array('key8', str_repeat('_', 50000))
        );
    }

    public function provider_data2()
    {
        return array(
            array('key1', 6453),
            array('key2', 'new string'),
            array('key3', array('big string', 123)),
            array('key4', array(new Datetime('@0'), 62345)),
            array('key5', new Datetime('@0')),
            array('wierd_name/!@#$%^&*()_+{}[]:";"\'<>,.?/>', 'test123'),
            array('/\\\\wierd_name26123/!@#$%^&*()_+{}[]:";"\'<>,.?/>', 'okeke'),
            array('#/\\\\wierd_name26123/!@#$%^&*()_+{}[]:";"\'<>,.?/>', 'ghjbvn'),
            array('124#/\\\\wierd_name26123/!@#$%^&*()_+{}[]:";"\'<>,.?/>', 'zxcxcv'),
            array('key7', array_fill(0, 50000, 'o')),
            array('key8', str_repeat('o', 50000))
        );
    }

    public function provider_impl_data1()
    {
        $cache_data_set = array();
        foreach($this->provider_impl() as $impl)
            foreach($this->provider_data1() as $pair)
                $cache_data_set[] = array($impl[0], $pair[0], $pair[1]);
        return $cache_data_set;
    }

    public function provider_impl_data2()
    {
        $cache_data_set = array();
        foreach($this->provider_impl() as $impl)
            foreach($this->provider_data2() as $pair)
                $cache_data_set[] = array($impl[0], $pair[0], $pair[1]);
        return $cache_data_set;
    }
    
    /**
     * @dataProvider provider_impl_data2
     */
	public function testNoTTLSetGetTypes($cache, $key, $value)
	{	
        // Set value
	    $res = $cache->set($key, $value);
	    $this->assertTrue($res);

	    // Get value
	    $res = $cache->get($key, $succ);
	    $this->assertTrue($succ);
	    $this->assertEquals($res, $value);
	}

    /**
     * @dataProvider provider_impl_data2
     */
    public function testNoTTLSetOverwrite($cache, $key, $value)
    {
        // Set value
	    $res = $cache->set($key, $value);
	    $this->assertTrue($res);

	    // Get value
	    $res = $cache->get($key, $succ);
	    $this->assertTrue($succ);
	    $this->assertEquals($res, $value);
    }
    
    /**
     * @dataProvider provider_impl_data2
     * @depends testNoTTLSetOverwrite
     */
	public function testDelete($cache, $key, $value)
	{
	    // Get value
	    $res = $cache->get($key, $succ);
	    $this->assertTrue($succ);
	    $this->assertEquals($res, $value);

	    // Delete value
	    $res = $cache->delete($key);
	    $this->assertTrue($res);
	}

	/**
     * @dataProvider provider_impl_data2
     * @depends testDelete
     */
	public function testNonExistingGetAndDelete($cache, $key, $value)
	{
	    // Get value
	    $res = $cache->get($key, $succ);
	    $this->assertFalse($succ);

	    // Delete value
	    $res = $cache->delete($key);
	    $this->assertFalse($res);
	}

    /**
     * @dataProvider provider_impl_data1
     * @depends testNonExistingGetAndDelete
     */
    public function testNonTTLAdd($cache, $key, $value)
    {
        // Add value
        $res = $cache->add($key, $value);
        $this->assertTrue($res);

        // Get value
	    $res = $cache->get($key, $succ);
	    $this->assertTrue($succ);
	    $this->assertEquals($res, $value);

	    // Re-add value (must fail)
        $res = $cache->add($key, 'dumb value');
        $this->assertFalse($res);

        // Get value to check unaffected
	    $res = $cache->get($key, $succ);
	    $this->assertTrue($succ);
	    $this->assertEquals($res, $value);
    }

    /**
     * @dataProvider provider_impl
     * @depends testNonTTLAdd
     */
    public function testDeleteAll($cache)
    {   $data = $this->provider_data1();
    
        // Check that are is something stored
	    $res = $cache->get($data[0][0], $succ);
	    $this->assertTrue($succ);
	    $this->assertEquals($res, $data[0][1]);

        // Delete all
        $res = $cache->delete_all();
        $this->assertTrue($res);

        // Check that this data does not exist
	    $res = $cache->get($data[2][0], $succ);
	    $this->assertFalse($succ);

        // Try to re-delete
        $res = $cache->delete_all();
        $this->assertTrue($res);
    }

    /**
     * @dataProvider provider_impl
     * @depends testDeleteAll
     */
    public function testMultiSetGet($cache)
    {   $data = array();        
        foreach($this->provider_data2() as $pair)
            $data[$pair[0]] = $pair[1];
        // Set values
        $res = $cache->set_multi($data);
        $this->assertTrue($res);

        // Get values;
        foreach($data as $key => $value)
        {   $res = $cache->get($key, $succ);
            $this->assertTrue($succ);
            $this->assertEquals($res, $value);
        }
    }

    /**
     * @dataProvider provider_impl
     * @depends testMultiSetGet
     */
    public function testOverwriteMultiSetGet($cache)
    {   $data = array();        
        foreach($this->provider_data1() as $pair)
            $data[$pair[0]] = $pair[1];

        // Set values
        $res = $cache->set_multi($data);
        $this->assertTrue($res);

        // Get values;
        foreach($data as $key => $value)
        {   $res = $cache->get($key, $succ);
            $this->assertTrue($succ);
            $this->assertEquals($res, $value);
        }
    }

    /**
     * @dataProvider provider_impl
     * @depends testOverwriteMultiSetGet
     */
    public function testMultiGet($cache)
    {   $data = array();
        $keys = array();
        foreach($this->provider_data1() as $pair)
        {   $keys[] = $pair[0];
            $data[$pair[0]] = $pair[1];
        }

        // Get values
        $res = $cache->get_multi($keys);
        $this->assertEquals($res, $data);

        // Erase values
        $cache->delete_all();

        // Get values
        $res = $cache->get_multi($keys);
        $this->assertEquals($res, array());
    }

    /**
     * @dataProvider provider_impl_data1
     * @depends testMultiGet
     */
    public function testTTLSetGet($cache, $key, $value)
    {  
        // Set values with 2 seconds ttl
        $res = $cache->set($key, $value, 2);
        $this->assertTrue($res);

        // Check that value exists
        $res = $cache->get($key, $succ);
        $this->assertTrue($succ);
        $this->assertEquals($res, $value);
    }
    
    /**
     * @depends testTTLSetGet
     */
    public function testForceTTLExpiration()
    {   // Force cache expiration
        sleep(3);
    }

    /**
     * @dataProvider provider_impl_data1
     * @depends testForceTTLExpiration
     */
    public function testTTLExpiredGet($cache, $key, $value)
    {   // Skip APC Checks as an optimazation feature does invalidate
        // cache in same request making unit test useless.
        if ($cache instanceof Cache_Apc)
            return;
            
        // Check that value exists
        $res = $cache->get($key, $succ);
        $this->assertFalse($succ);
    }

   /**
     * @dataProvider provider_impl
     * @depends testTTLExpiredGet
     */
    public function testAfterTTLDeleteAll($cache)
    {   $cache->delete_all();
    }
    
   /**
     * @dataProvider provider_impl_data2
     * @depends testAfterTTLDeleteAll
     */
    public function testTTLAddGet($cache, $key, $value)
    {  
        // Set values with 2 seconds ttl
        $res = $cache->add($key, $value, 2);
        $this->assertTrue($res);

        // Check that value exists
        $res = $cache->get($key, $succ);
        $this->assertTrue($succ);
        $this->assertEquals($res, $value);
    }
    
    /**
     * @depends testTTLAddGet
     */
    public function testForceTTLAddExpiration()
    {   // Force cache expiration
        sleep(3);
    }

    /**
     * @dataProvider provider_impl_data2
     * @depends testForceTTLAddExpiration
     */
    public function testTTLAddExpiredGet($cache, $key, $value)
    {   // Skip APC Checks as an optimazation feature does invalidate
        // cache in same request making unit test useless.
        if ($cache instanceof Cache_Apc)
            return;
            
        // Check that value exists
        $res = $cache->get($key, $succ);
        $this->assertFalse($succ);
    }
}
?>
