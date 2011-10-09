<?php

namespace toolib\Stupid\Condition;
use toolib\Stupid\Condition;

class Bool extends Condition
{
	public function evaluate(){	}
	
	public static function opOr()
	{
		
		$conditions = func_get_args();
		//var_dump($conditions);
		if (count($conditions) == 0)
			return function(){ return true; };
		else if (count($conditions) == 1)
			return $conditions[0];
		
		return function($knowledge)use(&$conditions) {
			//var_dump($conditions);
			foreach($conditions as $cond) {
				if ($cond($knowledge)) {
					return true;
				}
			}
			return false;
		};		
	}
	
	public static function opAnd()
	{
		$conditions = func_get_args();
		return function($knowledge)use($conditions) {
			foreach($conditions as $cond) {
				if (! $cond($knowledge)) {
					return false;
				}
			}
			return true;
		};
	}
}