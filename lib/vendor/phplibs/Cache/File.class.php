<?php

require_once(dirname(__FILE__) . '/../Cache.class.php');

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
?>
