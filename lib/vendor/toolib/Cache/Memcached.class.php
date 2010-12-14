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

namespace toolib\Cache;

require_once(__DIR__ . '/../Cache.class.php');

//! Implementation using PECL/Memcached interface
class Memcached extends \toolib\Cache
{
	//! Memcached object
	public $memc;
	
	//! Construct a new memcached caching engine.
	/**
	 * @param string $host The ip/dns of memcached server.
	 * @param integer $port The listening port of server.
	 */	 
	public function __construct($host, $port = 11211)
	{
	    $this->memc = new \Memcached();
		if ($this->memc->addServer($host, $port) === FALSE)
			throw new RuntimeException("Cannot connect to memcached server $host:$port");	
	}
	
	public function add($key, $value, $ttl = 0)
	{
		return $this->memc->add($key, $value, $ttl);
	}
	

	public function set($key, $value, $ttl = 0)
	{
		return $this->memc->set($key, $value, $ttl);
	}
	
	public function setMulti($values, $ttl = 0)
	{
		return $this->memc->setMulti($values, $ttl);
	}
	
	public function get($key, & $succeded)
	{	
		if ((($obj = $this->memc->get($key)) !== FALSE)
			|| ($this->memc->getResultCode() == \Memcached::RES_SUCCESS))
		{
			$succeded = TRUE;
			return $obj;
		}
		
		$succeded = FALSE;
		return FALSE;
	}
	
	public function getMulti($keys)
	{
		return $this->memc->getMulti($keys);
	}
	
	public function delete($key)
	{
		return $this->memc->delete($key);
	}
	
	public function deleteAll()
	{
		return $this->memc->flush();
	}
}
