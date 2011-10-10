<?php

namespace toolib\Stupid\Condition;
use toolib\Stupid\Condition;

/**
 * @brief Static boolean operator conditions
 */
class Bool extends Condition
{
	public function evaluate(){	}

	/**
	 * @brief Boolean OR operator
	 */
	public static function opOr($cond1)
	{
		$conditions = func_get_args();
		if (count($conditions) == 1)
			return $conditions[0];
		
		return function($knowledge)use(&$conditions) {
			foreach($conditions as $cond) {
				if ($cond($knowledge)) {
					return true;
				}
			}
			return false;
		};		
	}

	/**
	 * @brief Boolean AND operator
	 */
	public static function opAnd($cond1)
	{
		$conditions = func_get_args();
		if (count($conditions) == 1)
			return $conditions[0];
		
		return function($knowledge)use($conditions) {
			foreach($conditions as $cond) {
				if (! $cond($knowledge)) {
					return false;
				}
			}
			return true;
		};
	}
	
	/**
	 * @brief Boolean NOT operator
	 * @param callable $cond Condition be executed and inverted.
	 */
	public static function opNot($cond)
	{
		return function ($knowledge) use($cond){
			return ! (boolean)$cond($knowledge);
		};
	}
}