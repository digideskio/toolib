<?php

/**
 * @brief Base class for condition evaluators of Stupid system.
 * @author sque
 *
 * This class will be used to extend Stupid system, if you need to define
 * a condition you don't need this.
 */
abstract class StupidCondition
{
	//! Internal array with all evaluators saved in associative array by their type.
	private static $evaluators = array();
	
	//! Called by derived classes to register them selfs
	public static function register()
	{	$called_class = get_called_class();	
		self::$evaluators[eval("return $called_class::type();")] = $called_class;
	}

	//! Called to create a condition object based on parameters
	public static function create($cond_options)
	{
		// Check if there is an implementation of this condition type
		if (!isset($cond_options['type']))
		{	trigger_error("Cannot create StupidCondition without defining its \"type\"");
			return false;
		}
		if (!isset(self::$evaluators[$cond_options['type']]))
		{	trigger_error("There is no register condition evaluator that can understand " . $cond_options['type']);
			return false;
		}
		
		// Save condition options		
		return $evaluator =  new self::$evaluators[$cond_options['type']]($cond_options);
	}
	
	//! Back references exported by this condtion
	protected $back_references = array();
	
	//! Internal constructor
	final public function __construct($options)
	{	$this->options = $options;	}

	//! Params for action (Return an array with the parameters)
	public function action_arguments()
	{	return $this->back_references;	}
	
	//! Published interface for evaluation
	public function evaluate()
	{	if ( (isset($this->options['negative'])) && ($this->options['negative'] === true ))
			return ! $this->evaluate_impl();	
		return $this->evaluate_impl();
	}
	
	//! Returns the type of evaluator
	abstract public static function type();
	
	//! Implemented by evaluator
	abstract protected function evaluate_impl();
}

/**
 * @brief Implementation of UrlParamsCondition StupidCondition
 * @author sque
 *
 * The accepted options are
 * - op [Default = equal]: equal, isset, isnumeric, regexp
 * - param: The parameter to check.
 * - param_type [Default=both]: The type of parameter from the url. Acceptable values are "get", "post", "both" 
 * - value The value that the operand may need to cross check.
 * .
 */
class UrlParamsCondition extends StupidCondition
{
	public static function type()
	{	return 'url_params';	}
	
 
	public function evaluate_impl()
	{
		// Default condition values
		$defcond = array(
			'op' => 'equal',
			'param' => '',
			'value' => '',
			'param_type' => 'both',
		);
		
		// Merge default with user supplied parameters
		$options = array_merge($defcond, $this->options);
		
		// Check that parameter is set
		if (($value = param::get($options['param'], $options['param_type'])) === NULL)
			return false;
			
		// Per operand
		switch($options['op']){
		case 'isset':
			return true;
		case 'equal':
			return $value == $options['value'];
		case 'regexp':
			return (preg_match($options['value'], $value) == 1);
		case 'isnumeric':
			return is_numeric($value);
		}
		return false;		
	}
};
UrlParamsCondition::register();

/**
 * @brief Implementation of UrlPathCondition
 * @author sque
 * @todo rename to URL...
 *
 * The accepted options are
 * - path_type [Default = full]: request_uri, path_info
 * - delimiter [Default=/] :  <any string>
 * - path: A regular expresion to check if path is equal to this.
 * - chunk[X]: A regular expression to check that this chunk conforms to.
 * 		X: A zero-based index of chunk. Negative values are accepted where -1 is the last, -2 pre-last.
  * .
 */
class UrlPathCondition extends StupidCondition
{
	public static function type()
	{	return 'url_path';	}
 
	public function evaluate_impl()
	{
		// Default condition values
		$defcond = array(
			'path_type' => 'path_info',
			'delimiter' => '/',
			'bref' => array()						
		);

		// Merge default with user supplied options
		$options = array_merge($defcond, $this->options);

		// Type of path
		switch($options['path_type']){
		case 'request_uri':
			$subject_path = $_SERVER['REQUEST_URI'];
			break;
		case 'path_info':
			if (!isset($_SERVER['PATH_INFO']))	return false;
			$subject_path = $_SERVER['PATH_INFO'];
			break;
		}
		
		// Check if there is a path constrain
		if (isset($options['path']))
		{	if (preg_match($options['path'], $subject_path, $matches) != 1)
				return false;
				
			// Push back references
			if (count($matches) > 1)
				$this->back_references = array_merge($this->back_references, array_slice($matches, 1));
		}
		
		// Pre-process chunk[X] options
		$chunk_checks = array();
		foreach($options as $key => $value)
		{	
			if (preg_match('/chunk\[([-]?[\d]+)\]/', $key, $matches) == 1)
				$chunk_checks[$matches[1]] = $value;
		}
		
		// Post-process chunk[X] options
		if (count($chunk_checks) > 0)
		{
			// Explode path
			$chunks = $subject_path;
			$chunks = explode($options['delimiter'], $chunks);
		
			//var_dump($chunks);
			foreach($chunk_checks as $chunk_index => $regex)
			{	// Check out of boundries
				if (($chunk_index >= 0) && ($chunk_index >= count($chunks)))
					return false;
				if (($chunk_index < 0) && ((abs($chunk_index)-1) >= count($chunks)))
					return false;
					
				// Normalize oposite
				if ($chunk_index < 0) $chunk_index = count($chunks) + $chunk_index;
				
				// Back-references
				if (preg_match($regex, $chunks[$chunk_index], $matches) != 1)
					return false;
				
				// Push back references
				if (count($matches) > 1)
					$this->back_references = array_merge($this->back_references, array_slice($matches, 1));
			}
		}
		return true;
	}
};
UrlPathCondition::register();

/**
 * @brief Implementation of Authentication StupidCondition
 * @author sque
 *
 * The accepted options are
 * - op [Default = equal]: ingroup, isanon, isuser
 * - group: The corresponding group for operands that need to define a group.
 * - user: The corresponding user for operands that need to define a user.
 */
class AuthenticationCondition extends StupidCondition
{
	public static function type()
	{	return 'auth';	}

	public function evaluate_impl()
	{
		// Default condition values
		$defcond = array(
			'op' => 'ingroup', 'isanon', 'isuser',
			'group' => '',
			'user' => ''
		);
		$options = array_merge($defcond, $this->options);

		// Per operand
		switch($options['op']){
		case 'ingroup':
			if (($gp = Group::open($options['group'])) === false)
				return false;
			return $gp->has_current_user();
		case 'isanon';
			return WAAS::current_user_is_anon();
		case 'isuser':
			return ((!WAAS::current_user_is_anon()) && (Waas::current_user()->$username == $options['user']));
		}
	}
}
AuthenticationCondition::register();

//! A simple expert system processor
/**
 * Stupid is designed to work like a simple expert system. Feeding
 * it with rules and actions, and triggering a chain reaction it will
 * evaluate rules and trigger the most appropriate action. 
 * 
 * @remarks Stupid system by design will trigger ONLY THE FIRST matching rule and no other one.
 * 
 * @par Modular design
 * Stupid system is designed to be modular
 * 
 * @author sque
 *
 */
class Stupid
{
	//! Rules registered in stupid system
	private static $rules = array();

	//! Default action of system
	private static $def_action = false;

	//! Add a new rule in stupid system
	/**
	 * @param $action
	 * @param $conditions ...
	 * @return NULL
	 */
	public static function add_rule()
	{	// Analyze function arguments
		$args = func_get_args();
		if (count($args) < 2)
			return false;
		$action = $args[0];
		$conditions = array_slice($args, 1);
			
		$processed_conditions = array();
		foreach($conditions as $condition)
		{
			if (($cond_obj = StupidCondition::create($condition)) === false)
				return false;
			$processed_conditions[]  = $cond_obj;
		}
		$rule['conditions'] = $processed_conditions;
		$rule['action'] = $action;
		self::$rules[] = $rule;
	}
	
	//! Reset system to initial state
	/**
	 * Brings stupid system at its initial state. All rules will be deleted 
	 * and the default action.
	 * @return NULL
	 */
	public static function reset()
	{
		self::$rules = array();
		self::$def_action = false;
	}
	
	//! Evaluate rules and trigger reactions
	public static function chain_reaction()
	{
		foreach(self::$rules as $rule)
		{	$cond_res = true;
			$action_args = array();
			foreach($rule['conditions'] as $condition)
				if (! ($cond_res = $condition->evaluate()))
					break;
				else
					$action_args = array_merge($action_args, $condition->action_arguments());

			if ($cond_res)
			{	self::reset();
				call_user_func_array($rule['action'], $action_args);
				return;
			}
		}
		
		// Nothing matched default action
		if (self::$def_action !== false)
		{	$def_action = self::$def_action; 
			self::reset();
			call_user_func($def_action);
		}
	}
	
	//! Set the default action of system
	public static function set_default_action($func)
	{	self::$def_action = $func;	}
}
?>