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

/**
 * @brief Implementation for SQLite3 caching
 */
class Sqlite3 extends \toolib\Cache
{
    /**
     * @brief The SQLite3 connection object
     * @var \SQLite3
     */
	public $db;
	
	/**
	 * @brief Statement to set values;
	 * @var \SQLite3Stmt
	 */
	private $set_stmt;
	
	/**
	* @brief Statement to add values;
	* @var \SQLite3Stmt
	*/
	private $add_stmt;
	
	/**
	* @brief Statement to get values;
	* @var \SQLite3Stmt
	*/
	private $get_stmt;
	
	/**
	* @brief Statement to delete values;
	* @var \SQLite3Stmt
	*/
	private $delete_stmt;
	
	/**
	 * @brief Construct a new sqlite3 based caching engine
	 * @param string $db The file name of the database to open/create.
	 */
	public function __construct($db)
	{
	    $new_db = false;
		if (!file_exists($db))
			$new_db = true;
		
		// Open database
		$this->db = new \SQLite3($db);
		
		// Create schema if needed
		if ($new_db) {
			$res = $this->db->query(
				'CREATE TABLE cache_sqlite(\'key\' VARCHAR(255) PRIMARY KEY, value TEXT, expir_time INTEGER);');
			
			if ($res === false) {
				$this->db->close();
				unlink($db);
				throw new Exception("Cannot build sqlite cache database. " . $error_message);
			}
		}
	}
	
	public function set($key, $value, $ttl = 0)
	{
		if ($this->set_stmt == null) {
			if (!$this->set_stmt = $this->db->prepare(
				'UPDATE cache_sqlite SET value=:value, expir_time=:expir_time ' .
				'WHERE key=:key')) {
				throw new \RuntimeException('Error preparing update statement.');
			}
		}
		
		$expir_time = (($ttl === 0)?0:(time() + $ttl));
		$this->set_stmt->bindValue('key', $key, SQLITE3_TEXT);
    	$this->set_stmt->bindValue('value', serialize($value), SQLITE3_BLOB);
    	$this->set_stmt->bindValue('expir_time', $expir_time);

    	$res = @$this->set_stmt->execute();
		if (($res !== false) && ($this->db->changes() !== 0)) {
			return true;
		}
		
		return $this->add($key, $value, $ttl);
	}
	
	public function setMulti($values, $ttl = 0)
	{
		foreach($values as $key => $value)
			$this->set($key, $value, $ttl);
		return true;
	}
	
	public function add($key, $value, $ttl = 0)
	{
		if ($this->add_stmt == null) {
			if (!$this->add_stmt = $this->db->prepare('INSERT INTO cache_sqlite ' .
				'(key, value, expir_time) VALUES(:key, :value, :expir_time)')) {
				throw new \RuntimeException('Error preparing insert statement.');
			}
		}
		
		$expir_time = (($ttl === 0)?0:(time() + $ttl));
		$this->add_stmt->bindValue('key', $key, SQLITE3_TEXT);
		$this->add_stmt->bindValue('value', serialize($value), SQLITE3_BLOB);
		$this->add_stmt->bindValue('expir_time', $expir_time);
		
		$res = @$this->add_stmt->execute();
	    return ($res !== false && $this->db->changes() != 0);
	}
	
	public function get($key, & $succeded)
	{
		if ($this->get_stmt == null) {
			if (!$this->get_stmt = $this->db->prepare('SELECT * FROM cache_sqlite WHERE key=:key LIMIT 1;')) {
				throw new \RuntimeException('Error preparing select statement.');
			}
		}
		
		// Execute query
		$this->get_stmt->bindValue('key', $key, SQLITE3_TEXT);
		if (($res = $this->get_stmt->execute()) === false) {
			$succeded = false;
			return false;
		}
		
		// Fetch data
		if (!($data = $res->fetchArray(SQLITE3_BOTH))) {
			$succeded = false;
			return false;
		}
		
		// Check if it is expired and erase it
		if (($data['expir_time']) && ($data['expir_time'] < time())) {
			$this->delete($key);
			$succeded = false;
			return false;
		}
		$succeded = true;
		return unserialize($data['value']);
	}
	
	public function getMulti($keys)
	{
		$result = array();
		foreach($keys as $key) {
			$value = $this->get($key, $succ);
			if ($succ === true)
				$result[$key] = $value;
		}
		return $result;
	}
	
	public function delete($key)
	{
		if ($this->delete_stmt == null) {
			if (!$this->delete_stmt = $this->db->prepare('DELETE FROM cache_sqlite WHERE key=:key;')) {
				throw new \RuntimeException('Error preparing delete statement.');
			}
		}
		
		$this->delete_stmt->bindValue('key', $key, SQLITE3_TEXT);
		$res = $this->delete_stmt->execute();
	    if (($res === false) || (($this->db->changes()) === 0))
	        return false;
        return true;
	}
	
	public function deleteAll()
	{
		return (false !== $this->db->querySingle('DELETE FROM cache_sqlite'));
	}
}
