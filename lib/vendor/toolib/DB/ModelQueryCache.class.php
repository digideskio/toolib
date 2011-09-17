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

namespace toolib\DB;

class ModelQueryCache
{
	//! Query cache object per model
	static private $query_cache_repo = array();
	
	//! Global cache engine
	static private $global_cache_engine = NULL;
	
	//! Global cache ttl
	static private $global_cache_ttl = 0; 
	
	//! Set global cache engine options
	static public function setGlobalQueryCache($cache)
	{
		self::$global_cache_engine = $cache;
	}
	
	//! Set the global cache engine ttl
	static public function setGlobalQueryCacheTtl($ttl)
	{
		self::$global_cache_ttl = $ttl;
	}

	//! Get cache engine
	static public function getGlobalQueryCache($cache)
	{
		return self::$global_cache_engine;
	}
	
	//! Open a model's query cache
	static public function open($model)
	{
		if (isset(self::$query_cache_repo[$model->getName()]))
			return self::$query_cache_repo[$model->getName()];
		
		return self::$query_cache_repo[$model->getName()] = new ModelQueryCache($model);
	}

	//! The model object
	private $model = NULL;
	
	//! The models specific query cache
	private $model_query_cache = NULL;
	
	//! Model's specific query cache ttl
	private $model_query_cache_ttl = NULL;
	
	//! Model's effective cache engine
	private $model_effective_query_cache = NULL;
	
	//! Model's effective cache ttl
	private $model_effective_query_cache_ttl = 0;
	
	//! Use open()
	final private function __construct($model)
	{
		$this->model = $model;
		$model_name = $model->getName();
		
		// Check model for query settings
		if (isset($model_name::$query_cache_ttl))
			$this->setQueryCacheTtl($model_name::$query_cache_ttl);
			
		if (isset($model_name::$query_cache))
			$this->setQueryCache($model_name::$query_cache);
	}
	
	//! Set model specific query cache
	/**
	 * You can override global query cache per model.
	 * @param $cache Use one of the following options
	 * 	- @b NULL Don't override options, use the global one.
	 *  - @b FALSE Completely disable query cache for this model.
	 *  - @b Cache A new cache object that will be used for this model.
	 *  .
	 */
	public function setQueryCache($cache)
	{
		$this->model_query_cache = $cache;
	}
	
	//! Set model specific query cache ttl
	/**
	 * You can override global query cache ttl per model.
	 * @param $ttl Use one of the following options
	 * 	- @b NULL Don't override options, use the global one.
	 *  - @b INT A new ttl that will be used for this model.
	 *  .
	 */
	public function setQueryCacheTtl($ttl)
	{
		$this->model_query_cache_ttl = $ttl;
	}
	
	//! Calculate and return the effective cache for this model
	/**
	 * @return
	 *  - @b NULL if cache is disabled
	 *  - @b Cache object that is set to be used for this model
	 *  .
	 */
	public function getEffectiveCache()
	{
		$this->recalculateEffectiveCache();
		return $this->model_effective_query_cache;
	}
	

	//! Calculate and return the effective cache for this model
	/**
	 * @return @b Int The effective ttl based on global options
	 * and model's specific options.
	 */
	public function getEffectiveCacheTtl()
	{
		$this->recalculateEffectiveCache();
		return $this->model_effective_query_cache_ttl;	
	}

	//! Recalculate effective cache
	private function recalculateEffectiveCache($force = false)
	{
		// Effective cache engine
		if ($this->model_query_cache === NULL)
			$this->model_effective_query_cache = self::$global_cache_engine;
		else if ($this->model_query_cache === FALSE)
			$this->model_effective_query_cache = NULL;
		else
			$this->model_effective_query_cache = $this->model_query_cache;
			
		// Effective cache ttl
		if ($this->model_query_cache_ttl === NULL)
			$this->model_effective_query_cache_ttl = self::$global_cache_ttl;
		else
			$this->model_effective_query_cache_ttl = $this->model_query_cache_ttl;
	}
	
	//! Get the invalidation tracker key
	private function invalidationTrackerKey()
	{
		return 'QUERYCACHE[' . $this->model->getName() . '][INVAL-TRACKER]';
	}
	
	//! Generate the cache key
	private function cacheKey($query, & $args)
	{	
		return 'QUERYCACHE[' . $this->model->getName() .
			'][QUERY]' . $query->hash() .
			'(' . implode(',', $args) . ')';
	}
	
	private function getInvalidationTracker()
	{
		$it_tracker = $this->model_effective_query_cache->get($this->invalidationTrackerKey(), $succ);
		if (!$succ)
			$it_tracker = array(
				'update',
				'insert',
				'delete',
				'stats' => 
					array('unsets' => 0)
			);
		
		return $it_tracker;
	}
	
	private function setInvalidationTracker(&$tracker)
	{
		return $this->model_effective_query_cache->set($this->invalidationTrackerKey(), $tracker);
	}
	
	//! Process and store a select query
	private function process_select_query($query, & $args, & $results)
	{
		$hints = $query->cacheHints();
	    if (!$hints['cachable'])
	        return false;       // This query is not cachable

	    $cache_key = $this->cacheKey($query, $args);
        
		// Cache it
		$invalidate_on = array(
				array('update', '*'),
				array('insert', '*'),
				array('delete', '*')
		);
		$this->model_effective_query_cache->set(
			$cache_key.'[RESULTS]', 
			$results,
			$this->model_effective_query_cache_ttl);
		$this->model_effective_query_cache->set(
			$cache_key.'[INVALIDATE_ON]',
			$invalidate_on,
			$this->model_effective_query_cache_ttl);
				
		// Save invalidators
		$itracker = $this->getInvalidationTracker();
		$itracker['update']['*'][] = $cache_key;
		$itracker['delete']['*'][] = $cache_key;
		$itracker['insert']['*'][] = $cache_key; 
		$this->setInvalidationTracker($itracker);
	}
	
	//! Remove a query from the cache
	private function invalidateQuery(& $itracker, $query_key)
	{	
		// Get query invalidation_on ptrs
		$inv_ptrs = $this->model_effective_query_cache->get($query_key.'[INVALIDATE_ON]', $succ);
		if (!$succ) $inv_ptrs = array();
		
		// Remove other pointers before removing this query
		foreach($inv_ptrs as $ptr) {
			$key = array_search($query_key, $itracker[$ptr[0]][$ptr[1]], true);
			unset($itracker[$ptr[0]][$ptr[1]][$key]);
			
			// Increase unset pointer
			$itracker['stats']['unsets'] ++;
		}
		
		// Remove query
		$this->model_effective_query_cache->delete($query_key.'[RESULTS]', $succ);
		$this->model_effective_query_cache->delete($query_key.'[INVALIDATE_ON]', $succ);		
	}
	
	//! Process an invalidator query
	private function processInvalidatorsQuery($query, & $args)
	{
		$itracker = $this->getInvalidationTracker();

		// Search itrackers	
		foreach($itracker[$query->getType()]['*'] as $idx => $cache_key) {
			if ($cache_key != NULL)
				$this->invalidateQuery($itracker, $cache_key);	
		}
		
		// The key to cleanup unsets
		if ($itracker['stats']['unsets'] > 100) {
			foreach(array('update', 'delete', 'insert') as  $action)
				foreach($itracker[$action] as $update_rule => $ptrs )
				$itracker[$action][$update_rule] = array_values($itracker[$action][$update_rule]);
			$itracker['stats']['unsets'] = 0;
		}
		
		// save tracker
		$this->setInvalidationTracker($itracker);
	}
	
	//! Process a query
	public function processQuery($query, & $args, & $results)
	{
		if ($this->getEffectiveCache() === NULL)
			return false;
		
		// Select pushes data in cache
		if ($query->getType() === 'select')
			return $this->process_select_query($query, $args, $results);
			
		// Others invalidates data in cache
		return $this->processInvalidatorsQuery($query, $args);
	}
	
	//! Check cache for results
	public function fetchResults($query, & $args, & $succ)
	{
		$succ = false;
		if (($this->getEffectiveCache()) === NULL)
			return false;

		$ret = $this->model_effective_query_cache
			->get($this->cacheKey($query, $args) . '[RESULTS]', $succ);

		if ($succ)
			return $ret;
					
		return NULL;
	}
}
