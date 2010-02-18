<?php

//! Base class for condition evaluators of Stupid system.
/**
 * @author sque
 *
 * This class will be used to extend Stupid system by creating new
 * condition evaluators. If you are looking to add rules in
 * stupid system check Stupid::add_rule()
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
	
	//! Constructor of condition
	final public function __construct($options)
	{	$this->options = $options;	}

	//! Params for action (Return an array with the parameters)
	public function action_arguments()
	{	return $this->back_references;	}
	
	//! Published interface for evaluation
	public function evaluate($previous_backrefs= array())
	{	if ( (isset($this->options['not'])) && ($this->options['not'] === true ))
			return ! $this->evaluate_impl($previous_backrefs);	
		return $this->evaluate_impl($previous_backrefs);
	}
	
	//! @b ABSTRACT Returns the unique type of evaluator
	abstract public static function type();
	
	//! @b ABSTRACT To be implemented by evaluator
	abstract protected function evaluate_impl($previous_arguments);
}

//! Implementation of url_params StupidCondition
/**
 * A condition evaluator that can perform checks on the
 * given uri parameters.\n
 * This evaluator implements the <b> type = "url_params"</b> 
 * 
 * @par Acceptable condition options
 * - @b op [Default = equal]: equal, isset, isnumeric, regexp
 * - @b param: The parameter to check.
 * - @b param_type [Default=both]: The type of parameter from the url.\n
 *   Acceptable values are "get", "post", "both" 
 * - @b value : The value that the operand may need to cross check.
 * .
 * 
 * @par Examples
 * @code
 * // Adding a rule that checks if parameter id is set and is of type numeric
 * Stupid::add_rule("view_forum",
 *     array('type' => 'url_params', 'op' => 'isnumeric', 'param' => 'id'));
 * 
 * // A rule with two conditions
 * Stupid::add_rule("delete_forum",
 *     // Check that parameter "action" is set and is equal to "delete"
 *     array('type' => 'url_params', 'op' => 'equal', 'param' => 'action', 'value' => 'delete'),
 *     // Check that parameter "action" is set and is equal to 'id'
 *     array('type' => 'url_params', 'op' => 'isnumeric', 'param' => 'id'));
 * @endcode
 * @author sque
 */
class UrlParamsCondition extends StupidCondition
{
	public static function type()
	{	return 'url_params';	}
	
 
	public function evaluate_impl($previous_backrefs)
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

//! Implementation of url_path StupidCondition
/**
 * A condition evaluator that can perform checks on the
 * given uri path.\n
 * This evaluator implements the <b> type = "url_path"</b> 
 *
 * The evaluator can work with chunks or full path. First you need
 * to determine on which path to look for, and them you add one or more
 * rules that must be checked.
 * 
 * @par Acceptable condition options
 * - @b path_type [Default = request_uri]: request_uri, path_info
 * - @b delimiter [Default=/] : The delimiter that will be used to tokenize
 *      selected path in chunks.
 * - @b path: A regular expresion to check if path is equal to this.
 * - @b chunk[X]: A regular expression to check that this chunk conforms to.\n
 * 		@b X: A zero-based index of chunk. Negative values are accepted where -1 is the last, -2 pre-last.
 * .
 * 
 * @par Examples
 * @code
 * // Using chunks
 * Stupid::add_rule('create_news',
 *     array('type' => 'url_path', 'chunk[0]' => '/^news$/' , chunk[1] => '/([\d]+)/'));
 * // Using full path
 * Stupid::add_rule('create_news',
 *     array('type' => 'url_path', 'path' => '/^news/\+create$/'));
 * @endcode
 * @author sque
 */
class UrlPathCondition extends StupidCondition
{
	public static function type()
	{	return 'url_path';	}
 
	public function evaluate_impl($previous_backrefs)
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
			if (!isset($_SERVER['PATH_INFO']))	
				$subject_path = '';	// Non-existing path_info means empty one
			else
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

//! Implementation of auth StupidCondition
/**
 * A condition evaluator that can perform checks on the
 * WAAS and Group based on current logged on user.\n
 * This evaluator implements the <b> type = "auth"</b> 
 *
 * @par Acceptable condition options
 * - @b op [Default = equal]: ingroup, isanon, isuser
 * - @b group: The corresponding group for operands that need to define a group.
 * - @b user: The corresponding user for operands that need to define a user.
 * .
 * 
 * @par Examples
 * @code
 * // This action is accesible only from users of group admin
 * Stupid::add_rule('create_news',
 *     array('type' => 'url_path', 'path' => '/\/news\/\+create/'),
 *     array('type' => 'auth', 'op' => 'ingroup', 'group' => 'admin'));
 * @endcode
 */
class AuthenticationCondition extends StupidCondition
{
	public static function type()
	{	return 'auth';	}

	public function evaluate_impl($previous_backrefs)
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
			return ((!WAAS::current_user_is_anon()) && (Waas::current_user()->username == $options['user']));
		}
	}
}
AuthenticationCondition::register();

//! Implementation of func StupidCondition
/**
 * A condition evaluator that will execute a user defined function.\n
 * This evaluator implements the <b> type = "func"</b> 
 * 
 * @par Acceptable condition options
 * - @b func [@b Mandatory]: A callback to the function (see php official documentation for callbacks)
 * - @b args [Default = array()]: An array with arguments to pass on function 
 * - @b backref_as_arg[Default = true]: Pass as function arguments the backrefernces from previous evaluations in the rule.
 * - @b backref_first[Default = true]: If both @b args and @b backref exists, put backref first 
 * 		and then @b args, otherwise use @b args firstly and then @b backrefs.
 * .
 * 
 * @par Examples
 * @code
 * function is_day(){
 * 	// Check if current time is morning and return true or false
 * }
 * // Adding a rule that checks what part of day is it
 * Stupid::add_rule("view_forum",
 *     array('type' => 'func', 'func' => 'is_morning'));
 * 
 * @endcode
 * @author sque
 */
class FuncCondition extends StupidCondition
{
	public static function type()
	{	return 'func';	}
	
	public function evaluate_impl($previous_backrefs)
	{
		// Default condition values
		$defcond = array(
			'args' => array(),
			'backref_as_arg' => true,
			'backref_first' => true
		);
		
		// Merge default with user supplied parameters
		$options = array_merge($defcond, $this->options);
		
		$args = $options['args'];
		if ($options['backref_as_arg'])
			if ($options['backref_first'])
				$args = array_merge($previous_backrefs, $args);
			else
				$args = array_merge($args, $previous_backrefs);
		
		return call_user_func_array($options['func'], $args);
	}
};
FuncCondition::register();

//! A simple expert system processor
/**
 * Stupid is designed to work like a simple expert system. At first
 * you declare all the rules and actions. Starting the chain reaction it will
 * evaluate rules and trigger the most appropriate action. 
 * 
 * @note Stupid system will trigger ONLY THE FIRST matching rule and no other one. This
 * 	cannot be changed.
 * 
 * @par How to define rules
 * Stupid system is designed to be modular. Stupid class is a rule evaluator
 * by using registered StupidCondition evaluators. There are some standard
 * evaluators that ship with the engine but you can always expand it.
 * \n
 * Every evaluator has a unique @b "type", which is used when we are defining rules along
 * with specific evaluator parameters. All the parameters of each rule are given in an associative array.
 * @b Example
 * @code
 * // This rule uses grouping (parenthesis) in regex that act as backreferences
 * // and are given as argument in the action 
 * Stupid::add_rule('show_news',
 *     array('type' => 'url_path', 'path' => '/\/news\/([\d]+)/'));
 * // A rule that uses more than one condition
 * Stupid::add_rule('create_news',
 *     array('type' => 'url_path', 'path' => '/\/news\/\+create/'),
 *     array('type' => 'auth', 'op' => 'ingroup', 'group' => 'admin'));
 * // Evaluate rules and trigger apropriate action
 * Stupid::chain_reaction();
 * 
 * // Show news implementation
 * function show_news($id)
 * ...
 * 
 * // Create news implementation
 * function create_news()
 * ...
 * @endcode
 * You can see add_rule() for syntax information.
 * 
 * @par Standard Condition Evaluators
 * Stupid system ships with a set of standard evaluators, those evaluators
 * will probably fullfill the needs of most cases. Each evaluator has its
 * own parameters and you should read its documentation for more information.
 * \n
 * - UrlParamsCondition\n
 * 	<i> Condition evaluator for various checks on uri parameters </i>
 * - UrlPathCondition\n
 *  <i> Condition evaluator for checks on the path part of the uri. It suppots
 *  full path, PATH_INFO for "index.php/example" routing schema etc </i>
 * - AuthenticationCondition\n
 *  <i> Condition evaluator for checks on WAAS and Group<i>
 * .
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
	 * Its rule has one action and one or more rules.
	 * @param $action The action must be a valid php callback object
	 * 	like the name of a function or the object, method array schema.
	 * @param $conditions One or more coditions that ALL must be true
	 * 	for the action to be triggered. Each rule is given as an associative
	 *  array with the parameters of the conditions. Check condition evaluators
	 *  for acceptable options.
	 *  
	 *  @note If you want to reverse the effect of a condtion add an array entry
	 *  	named "not" =\> true
	 *  
	 * @code
	 * // A rule that uses more than one condition
	 * Stupid::add_rule('create_news',
	 *     array('type' => 'url_path', 'path' => '/\/news\/\+create/'),
	 *     array('type' => 'auth', 'op' => 'ingroup', 'group' => 'admin'));
	 * @endcode
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
	 * Brings stupid system at its initial state. All rules and default action
	 * will be deleted.
	 * @return NULL
	 */
	public static function reset()
	{
		self::$rules = array();
		self::$def_action = false;
	}
	
	//! Evaluate rules and trigger reactions
	/**
	 * It will start evaluating rules one-by-one in the same order
	 * as they were defined. At the first rule that is true it will
	 * @b reset stupid system and trigger action of this rule. After
	 * that no more actions are evaluated.
	 * 
	 * If all the rules return false, then it triggers the default action.
	 * 
	 * @note Stupid always resets the system before executing an action
	 * 	so that it is reusable inside the action.
	 * 
	 * @return NULL
	 */
	public static function chain_reaction()
	{
		foreach(self::$rules as $rule)
		{	$cond_res = true;
			$action_args = array();
			foreach($rule['conditions'] as $condition)
				if (! ($cond_res = $condition->evaluate($action_args)))
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
	/**
	 * The action that will be executed in case that no rule is true. 
	 * @param $func The callback function 
	 * @return NULL
	 */
	public static function set_default_action($func)
	{	self::$def_action = $func;	}
}
?>