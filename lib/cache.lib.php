<?php
require_once('functions.lib.php');

//! Abstract interface for caching engines
/**
 * To create a custom engine subclass Cache class
 * and populate all abstract methods.
 * 
 * Cache module ships with the following caching engine implementations
 * - Cache_Apc
 * - Cache_Memcached
 * - Cache_File
 * - Cache_Sqlite
 * .
 * @author sque
 */
abstract class Cache
{
	//! Set an entry in cache database
	/**
	 * Add or replace the value of a key in cache database
	 * @param $key Unique identifier of the cache entry
	 * @param $value Value of cache entry
	 * @param $ttl Maximum time, in seconds, that this entry will be valid
	 * @return
	 * - @b TRUE if value was stored succesfully.
	 * - @b FALSE on any kind of error.
	 */
	abstract public function set($key, $value, $ttl = 0);
	
	//! Set multiple entries
	/**
	 * This is like set() but it stores multiple entries at
	 * the same time. Some engines (like memcached) supports
	 * acceleration of this action. For the rest of the engines
	 * it will be emulated, without a significant performance penalty.
	 * @param $values Associative array of key-value pairs to
	 *  be stored in cache db.
	 * @param $ttl Maximum time, in seconds, that all these entry will be valid
	 * @return
	 * - @b TRUE if all values were stored succesfully.
	 * - @b FALSE on any kind of error.
	 */
	abstract public function set_multi($values, $ttl = 0);
	
	//! Add new entry in cache database
	/**
	 * Add a @b new value in database. This function will fail if
	 * there is already an entry with the same key. 
	 * @param $key Unique identifier of the cache entry
	 * @param $value Value of cache entry.
	 * @param $ttl Maximum time, in seconds, that this entry will be valid
	 * @return
	 * - @b TRUE if value was stored succesfully.
	 * - @b FALSE on any kind of error.
	 */
	abstract public function add($key, $value, $ttl = 0);
	
	//! Read an entry from cache database
	/**
	 * Retrieve an entry from cache db based on its key.
	 * @param $key Unique identifier of the cache entry
	 * @param $succeded By reference variable to store
	 * the result status of the function. @b True will be
	 * stored in case of success or @b false on any error. 
	 * @return The value of the entry in database or @b FALSE
	 * if entry was not found.
	 * 
	 * @note Return value is not always the safest way to check if the
	 * entry was succesfull, as there is a posibility the entry to have
	 * false value. For success check the value of the $succeded parameter
	 * after the execution of function.
	 */
	abstract public function get($key, & $succeded);
	
	//! Read multiple entries from cache database
	/**
	 * This works like get() but with multiple entries at
	 * the same time. Some engines (like memcached) supports
	 * acceleration of this action. For the rest of the engines
	 * it will be emulated, without a significant performance penalty.
	 * @param $keys Simple array with the keys of entries that will be read.
	 * @return Associative array with all the keys that were retrieved
	 * succesfully. Those that failed will be omited from the return result. 
	 */
	abstract public function get_multi($keys);
	
	//! Delete an entry from cache database
	/**
	 * Delete an entry based on its key.
	 * @param $key Unique identifier of the cache entry
	 * @return
	 * - @b TRUE if entry was found and deleted
	 * - @b FALSE on any kind of error
	 * .
	 */
	abstract public function delete($key);
	
	//! Empty cache from all entries
	/**
	 * It will delete all entries from the cache.
	 * database.
	 * @return
	 * - @b TRUE on success whetever was the number of deleted entries.
	 * - @b FALSE on error.
	 * 
	 * @note This function will delete @b ALL entries, even
	 * those that were not created by you.
	 */
	abstract public function delete_all();
}

//! Implementation for APC cache engine
class Cache_Apc extends Cache
{
	private $apc_key_prefix;
	
	/**
	 * @param $serialize_data A flag to serialize/unserialize data before
	 * pushing/fetching them from apc sma.
     */
	public function __construct($apc_key_prefix = '', $serialize_data = false)
	{	$this->apc_key_prefix = $apc_key_prefix;
		$this->serialize_data = $serialize_data;
	}
	
	public function add($key, $value, $ttl = 0)
	{	if ($this->serialize_data)
			return apc_add($this->apc_key_prefix . $key, serialize($value), $ttl);
		else
			return apc_add($this->apc_key_prefix . $key, $value, $ttl);
	}
	

	public function set($key, $value, $ttl = 0)
	{	if ($this->serialize_data)
			return apc_store($this->apc_key_prefix . $key, serialize($value), $ttl);
		else
			return apc_store($this->apc_key_prefix . $key, $value, $ttl);
	}

	public function set_multi($values, $ttl = 0)
	{	foreach($values as $key => $value)
			$this->set($key, $value, $ttl);
	    return true;
	}
	
	public function get($key, & $succeded)
	{	if ($this->serialize_data)
			return unserialize(apc_fetch($this->apc_key_prefix . $key, $succeded));
		else
			return apc_fetch($this->apc_key_prefix . $key, $succeded);
	}
	
	public function get_delayed($key, $callback){}
	
	public function get_multi($keys)
	{	$result = array();
		foreach($keys as $key)
		{	$value = $this->get($key, $succ);
			if ($succ === TRUE)
				$result[$key] = $value; 
		}
		return $result;
	}
	
	public function delete($key)
	{	return apc_delete($this->apc_key_prefix . $key);	}
	
	public function delete_all()
	{	return apc_clear_cache("user");	}
}

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

//! Implementation for filesystem caching
class Cache_File extends Cache
{
	//! Directory to save cache files
	private $directory;
	
	//! Prefix to add at filenames
	private $file_prefix;

	private function filename_by_key($key)
	{	return $this->directory . '/' . $this->file_prefix . md5($key);
	}
	
	public function __construct($directory = NULL, $file_prefix = 'cache_file_')
	{	$this->file_prefix = $file_prefix;
		$this->directory = $directory;
		if ($this->directory  === NULL)
			$this->directory = sys_get_temp_dir();
			
		if (!is_writeable($this->directory ))
			throw new Exception("Directory {$this->directory} is not writable by Cache_File");		
	}
	
	public function set($key, $value, $ttl = 0)
	{	if (($fh = fopen($this->filename_by_key($key),'w+')) === false)
			return false; 
		
		// Lock file
		if (flock($fh, LOCK_EX) === false)
		{	fclose($fh);	return false;	}
		
		// Write data
		fwrite($fh, serialize(array(
			'key' => $key,
			'value' => $value,
			'expires' => (($ttl > 0)?time() + $ttl:0)
		)));
		
		fclose($fh);
		return true;
	}
	
	public function set_multi($values, $ttl = 0)
	{	foreach($values as $key => $value)
			$this->set($key, $value, $ttl);
	    return true;
	}
	
	public function add($key, $value, $ttl = 0)
	{	if (file_exists($this->filename_by_key($key)))
			return false;
		return $this->set($key, $value, $ttl);
	}
	
	public function get($key, & $succeded)
	{	if (($fh = @fopen(($fname = $this->filename_by_key($key)),'r')) === false)
		{	$succeded = false;
			return false;
		}
		
		// Lock file
		if (flock($fh, LOCK_SH) === false)
		{	fclose($fh);
			$succeded = false;
			return false;
		}
		
		// Read data
		$data = file_get_contents($fname);
		fclose($fh);
		
		// Unserialize data
		if (($data = @unserialize($data)) === FALSE)
		{	unlink($fname);
			$succeded = false;
			return false;
		}
		
		// Check expired
		if (($data['expires'] !== 0) && ($data['expires'] < time()))
		{	unlink($fname);
			$succeded = false;
			return false;
		}
		
		$succeded = true;
		return $data['value'];
	}

	public function get_multi($keys)
	{	$result = array();
		foreach($keys as $key)
		{	$value = $this->get($key, $succ);
			if ($succ === TRUE)
				$result[$key] = $value; 
		}
		return $result;
	}
	
	public function delete($key)
	{	return @unlink($this->filename_by_key($key));
	}
	
	public function delete_all()
	{	if (($dh = opendir($this->directory)) === FALSE)
			return false;
			
		while((($entry = readdir($dh)) !== FALSE))
		{	if (!is_file($this->directory . '/' . $entry))
				continue;
			
			// Delete all files with that prefix
			if (substr($entry, 0, strlen($this->file_prefix)) === $this->file_prefix)
				unlink($this->directory . '/' . $entry);
		}
		return true;		
	}
}

//! Implementation for SQLite caching
class Cache_Sqlite extends Cache
{
	public $dbhandle;
	
	public function __construct($db)
	{	$new_db = false;
		if (!file_exists($db))
			$new_db = true;
		
		// Open database
		if (($this->dbhandle = sqlite_open($db, 0666, $error_message)) === FALSE)
			throw new Exception("Cannot open sqlite cache database. " . $error_message);
		
		// Create schema if needed
		if ($new_db)
		{
			$res = sqlite_query($this->dbhandle,
				'CREATE TABLE cache_sqlite(\'key\' VARCHAR(255) PRIMARY KEY, value TEXT, expir_time INTEGER);', 
				SQLITE_ASSOC,	$error_message);
			
			if ($res === FALSE)
			{	sqlite_close($this->dbhandle);
				unlink($db);
				throw new Exception("Cannot build sqlite cache database. " . $error_message);
			}
		}
	}
	
	public function __destruct()
	{	sqlite_close($this->dbhandle);	}
	
	public function set($key, $value, $ttl = 0)
	{	$expir_time = (($ttl === 0)?0:(time() + $ttl));
    	$res = @sqlite_query($this->dbhandle,
			"UPDATE cache_sqlite SET " .
				"value = '" . sqlite_escape_string(serialize($value)) . "', " .
				"expir_time = '" . $expir_time . "' " .
				"WHERE key = '" . sqlite_escape_string($key) . "';");
		if (($res !== FALSE) && (sqlite_changes($this->dbhandle) !== 0))
		    return true;
		
		return $this->add($key, $value, $ttl);		
	}
	
	public function set_multi($values, $ttl = 0)
	{	foreach($values as $key => $value)
			$this->set($key, $value, $ttl);
		return true;
	}
	
	public function add($key, $value, $ttl = 0)
	{ 	$expir_time = (($ttl === 0)?0:(time() + $ttl));
	    $res = @sqlite_query($this->dbhandle,
			"INSERT INTO cache_sqlite (key, value, expir_time) VALUES( '" .
				sqlite_escape_string($key) . "', '" .
				sqlite_escape_string(serialize($value)) . "', '" .
				$expir_time . "');");
		
		return ($res !== FALSE);
	}
	
	public function get($key, & $succeded)
	{	// Execute query
		if (($res = sqlite_query($this->dbhandle, 
				"SELECT * FROM cache_sqlite WHERE key = '" . sqlite_escape_string($key) . "' LIMIT 1;")) === FALSE)
		{	$succeded = false;
			return false;
		}
		
		// Fetch data
		if (count($data = sqlite_fetch_all($res)) != 1)
		{	$succeded = false;
			return false;
		}
		
		// Check if it is expired and erase it
		if (($data[0]['expir_time']) && ($data[0]['expir_time'] < time()))
		{	$this->delete($key);
			$succeded = false;
			return false;
		}
		$succeded = true;
		return unserialize($data[0]['value']);
	}
	
	public function get_multi($keys)
	{	$result = array();
		foreach($keys as $key)
		{	$value = $this->get($key, $succ);
			if ($succ === TRUE)
				$result[$key] = $value; 
		}
		return $result;
	}
	
	public function delete($key)
	{	$res = sqlite_query($this->dbhandle,
			"DELETE FROM cache_sqlite WHERE key = '" . sqlite_escape_string($key) . "'");
	    if (($res === false) || (sqlite_changes($this->dbhandle) === 0))
	        return false;
        return true;
	}
	
	public function delete_all()
	{	return (FALSE !== sqlite_query($this->dbhandle,
			"DELETE FROM cache_sqlite"));
	}
}
?>
