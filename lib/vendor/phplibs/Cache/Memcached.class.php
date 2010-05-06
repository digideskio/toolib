<?php

require_once(dirname(__FILE__) . '/../Cache.class.php');

//! Implementation using PECL/Memcached interface
class Cache_Memcached extends Cache
{
    //! Memcached object
    public $memc;

    public function __construct($host, $port = 11211)
    {	$this->memc = new Memcached();
    if ($this->memc->addServer($host, $port) === FALSE)
    throw new RuntimeException("Cannot connect to memcached server $host:$port");
    }

    public function add($key, $value, $ttl = 0)
    {	return $this->memc->add($key, $value, $ttl);	}


    public function set($key, $value, $ttl = 0)
    {	return $this->memc->set($key, $value, $ttl);	}

    public function set_multi($values, $ttl = 0)
    {	return $this->memc->setMulti($values, $ttl);	}

    public function get($key, & $succeded)
    {
        if ((($obj = $this->memc->get($key)) !== FALSE) ||
        ($this->memc->getResultCode() == Memcached::RES_SUCCESS))
        {	$succeded = TRUE;
        return $obj;
        }

        $succeded = FALSE;
        return FALSE;
    }

    public function get_multi($keys)
    {	return $this->memc->getMulti($keys);	}

    public function delete($key)
    {	return $this->memc->delete($key);	}

    public function delete_all()
    {	return $this->memc->flush();	}
}

?>
