<?php

class DBModelQueryCache
{
	//! Query cache object per model
	static private $model_query_cache = array();
	
	//! Cache engine
	static private $cache_engine = NULL;
	
	//! Set cache engine
	static public function set_query_cache($cache)
	{	self::$cache_engine = $cache;	}

	//! Get cache engine
	static public function get_query_cache($cache)
	{	return self::$cache_engine;	}
	
	//! Open a model's query cache
	static public function open($model)
	{
		if (isset(self::$model_query_cache[$model->name()]))
			return self::$model_query_cache[$model->name()];
		
		return self::$model_query_cache[$model->name()] = new DBModelQueryCache($model);
	}

	//! The model object
	private $model = NULL;
	
	//! Use open()
	final private function __construct($model)
	{
		$this->model = $model;
	}
	
	//! Get the invalidation tracker key
	private function invalidation_tracker_key()
	{
		return 'QUERYCACHE[' . $this->model->name() . '][INVAL-TRACKER]';
	}
	
	//! Generate the cache key
	private function cache_key($query, & $args)
	{	
		return 'QUERYCACHE[' . $this->model->name() . '][QUERY]' . $query->hash() . '(' . implode(',', $args) . ')';
	}
	
	private function get_invalidation_tracker()
	{
		$it_tracker = self::$cache_engine->get($this->invalidation_tracker_key(), $succ);
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
	
	private function set_invalidation_tracker(&$tracker)
	{
		return self::$cache_engine->set($this->invalidation_tracker_key(), $tracker);
	}
	
	//! Process and store a select query
	private function process_select_query($query, & $args, & $results)
	{	$cache_key = $this->cache_key($query, $args);
				
		// Cache it
		$invalidate_on = array(
				array('update', '*'),
				array('insert', '*'),
				array('delete', '*')
		);
		self::$cache_engine->set($cache_key.'[RESULTS]', $results);
		self::$cache_engine->set($cache_key.'[INVALIDATE_ON]', $invalidate_on);
				
		// Save invalidators
		$itracker = $this->get_invalidation_tracker();
		$itracker['update']['*'][] = $cache_key;
		$itracker['delete']['*'][] = $cache_key;
		$itracker['insert']['*'][] = $cache_key; 
		$this->set_invalidation_tracker($itracker);
	}
	
	//! Remove a query from the cache
	private function invalidate_query(& $itracker, $query_key)
	{	// Get query invalidation_on ptrs
		$inv_ptrs = self::$cache_engine->get($query_key.'[INVALIDATE_ON]', $succ);
		if (!$succ) $inv_ptrs = array();
		
		// Remove other pointers before removing this query
		foreach($inv_ptrs as $ptr)
		{
			$key = array_search($query_key, $itracker[$ptr[0]][$ptr[1]], true);
			unset($itracker[$ptr[0]][$ptr[1]][$key]);
			
			// Increase unset pointer
			$itracker['stats']['unsets'] ++;
		}
		
		// Remove query
		self::$cache_engine->delete($query_key.'[RESULTS]', $succ);
		self::$cache_engine->delete($query_key.'[INVALIDATE_ON]', $succ);		
	}
	
	//! Process an invalidator query
	private function process_invalidators_query($query, & $args)
	{	$itracker = $this->get_invalidation_tracker();

		// Search itrackers		
		foreach($itracker[$query->type()]['*'] as $idx => $cache_key)
		{	if ($cache_key != NULL)
				$this->invalidate_query($itracker, $cache_key);	
		}
		
		// The key to cleanup unsets
		if ($itracker['stats']['unsets'] > 100)
		{	foreach(array('update', 'delete', 'insert') as  $action)
				foreach($itracker[$action] as $update_rule => $ptrs )
				$itracker[$action][$update_rule] = array_values($itracker[$action][$update_rule]);
			$itracker['stats']['unsets'] = 0;
		}
		
		// save tracker
		$this->set_invalidation_tracker($itracker);
	}
	
	//! Process a query
	public function process_query($query, & $args, & $results)
	{	if (self::$cache_engine === NULL)
			return false;
		
		// Select pushes data in cache
		if ($query->type() === 'select')
			return $this->process_select_query($query, $args, $results);
			
		// Others invalidates data in cache
		return $this->process_invalidators_query($query, $args);
	}
	
	//! Check cache for results
	public function fetch_results($query, & $args, & $succ)
	{	$succ = false;
		if (self::$cache_engine === NULL)
			return false;

		$ret = self::$cache_engine->get($this->cache_key($query, $args) . '[RESULTS]', $succ);
		if ($succ)
			return $ret;
					
		return NULL;
	}
}
?>