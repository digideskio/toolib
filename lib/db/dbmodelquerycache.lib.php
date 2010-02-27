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
	
	//! Process a query
	public function process_query($query, $args, $invalidate_actions)
	{	if (self::$cache_engine === NULL)
			return false;
		
	}
	
	//! Check cache for results
	public function fetch_results($query, $args, $succ)
	{	$succ = false;
		if (self::$cache_engine === NULL)
			return false;		
	}
}
?>