<?php
require_once('mysqli.lib.php');

//! Class for managing records of a table
class DBRecord
{
	//! Parameter of DBRecord to use session to cache classes
	public static $session_cache = false;
	
	//! All the classes that are using DBRecord
	protected static $classes = array();
	
	//! Data of fields
	protected $data = array();
	
	//! Class description
	protected $class_desc = false;
	
	//! Constructor of dbrecord (prevent all derived objects from direct instantiation!)
	final private function __construct($class_name)
	{
		// Save class description
		$this->class_desc = & self::$classes[$class_name];
		
		// Populate data
		foreach($this->class_desc['fields'] as $field_name => $field)
			$this->data[$field_name] = 0;
	}

	//! Create sql queries based on a class description
	private static function create_sql(& $class_desc)
	{
		// SELECT
		$query = 'SELECT ' ;
		$count = 0;
		foreach($class_desc['fields'] as $field)
		{	$count ++;				
			if ($count != 1) $query .= ', ';
			if ($field['type'] == 'datetime')
				$query .= 'UNIX_TIMESTAMP(`' . $field['sqlfield'] . '`) AS ' . $field['sqlfield'] ;
			else
				$query .= '`' . $field['sqlfield'] . '`';
		}
		$query .= ' FROM ' . $class_desc['table'] . ' WHERE ' . $class_desc['meta']['pk'][0] . ' = ?';
		$class_desc['sql']['open']['query'] = $query;
		$class_desc['sql']['open']['stmt'] = 'dbrecord-' . strtolower($class_desc['class']) . '-open';


		// INSERT
		$query = 'INSERT INTO ' . $class_desc['table'] . '(';
		$count = 0;
		foreach($class_desc['fields'] as $field)
		{	if ($field['ai'])
				continue;

			$count ++;				
			if ($count != 1) $query .= ', ';
			$query .= ' `' . $field['sqlfield'] . '`';
		}
		$query .= ') VALUES(';
		for($i = 0; $i < $count; $i++)
		{	if ($i != 0) $query .= ', ';
			$query .= '?';
		}
		$query .= ')';
		$class_desc['sql']['create']['query'] = $query;
		$class_desc['sql']['create']['stmt'] = 'dbrecord-' . strtolower($class_desc['class']) . '-create';
		
		// UPDATE
		$query = 'UPDATE ' . $class_desc['table'] . ' SET ';
		foreach($class_desc['fields'] as $field)
		$count = 0;
		foreach($class_desc['fields'] as $field)
		{	if ($field['pk'])
				continue;

			$count ++;				
			if ($count != 1) $query .= ', ';
			$query .= '`' . $field['sqlfield'] . '` = ? ';
		}
		$query .= ' WHERE ' . $class_desc['meta']['pk'][0] . '=?';
		$class_desc['sql']['update']['query'] = $query;
		$class_desc['sql']['update']['stmt'] = 'dbrecord-' . strtolower($class_desc['class']) . '-update';
		
		// Get ALL
		$class_desc['sql']['all']['query'] = 'SELECT ' . $class_desc['meta']['pk'][0] . ' FROM ' . $class_desc['table'];
		$class_desc['sql']['all']['stmt'] = 'dbrecord-' . strtolower($class_desc['class']) . '-all';
	}
	
	
	//! Initialize static parameters of the dbrecord specialization
	private static function init_static($called_class)
	{
		// Check if this is cached class
		if (!isset(self::$classes[$called_class]))
		{	
			// Keep cache in session
			if ((!self::$session_cache) || !isset($_SESSION['dbrecord-cache-2'][$called_class]))
			{
				$child_fields = get_static_var($called_class, 'fields');
				$child_table = get_static_var($called_class, 'table');
				$child_meta['pk'] = array();
				$child_meta['ai'] = array();
								
				// Check if fields are defined
				if (!is_array($child_fields))
				{	error_log('DBRecord::$fields is not defined in derived class');
					return false;
				}
		
				// Check if table is defined
				if (!is_string($child_table))
				{	error_log('DBRecord::$table is not defined in derived class');
					return false;
				}
				
				// Validate and copy all fields
				$filtered_fields = array();
				foreach($child_fields as $field_name => $field)
				{
					// Initialize field
					$filtered_field = $field;
					
					// Check if it is string
					if (is_numeric($field_name) && is_string($field))
					{	$field_name = $field;
						$filtered_field = array();
					}
					
					// sqlfield
					if (!isset($filtered_field['sqlfield']))
						$filtered_field['sqlfield'] = $field_name;
					
					// Type
					if (!isset($filtered_field['type']))
						$filtered_field['type'] = 'general';

					// PK (Primary Key)
					if (!isset($filtered_field['pk']))
						$filtered_field['pk'] = false;

					// AI (Auto Increment)
					if (!isset($filtered_field['ai']))
						$filtered_field['ai'] = false;
					
					// Find primary key(S) TODO: What about NO PK or DOUBLE PK ?
					if ($filtered_field['pk'])
					{
						$child_meta['pk'][] = $filtered_field['sqlfield'];
						if ($filtered_field['ai'])
							$child_meta['ai'][] = $filtered_field['sqlfield'];
					}
					else if ($filtered_field['ai'])
						$filtered_field['ai'] = false;
						
					$filtered_fields[$field_name] = $filtered_field;
				}
				
				// Add extra meta data
				$child_meta['total_fields'] = count($filtered_fields);
				
				// Save class characteristics
				self::$classes[$called_class] = array('fields' => $filtered_fields, 
					'table' => $child_table,
					'meta' => $child_meta,
					'class' => $called_class
				);
				$class_desc = & self::$classes[$called_class];
				 
				// Create sql-queries
				self::create_sql($class_desc);
	
				$_SESSION['dbrecord-cache-2'][$called_class] = self::$classes[$called_class];
			}
			else
				$class_desc = self::$classes[$called_class] = $_SESSION['db-record-cache-2'][$called_class];

			// Prepare sql statements
			dbconn::prepare($class_desc['sql']['open']['stmt'], $class_desc['sql']['open']['query']);
			dbconn::prepare($class_desc['sql']['create']['stmt'], $class_desc['sql']['create']['query']);
			dbconn::prepare($class_desc['sql']['update']['stmt'], $class_desc['sql']['update']['query']);
			dbconn::prepare($class_desc['sql']['all']['stmt'], $class_desc['sql']['all']['query']);
		}
//		echo '<pre>'; var_dump($class_desc); echo '</pre>';
		
		return self::$classes[$called_class];
	}
	
	// Create a new record
	/** 
		Parameters will be passed as a simple or associative array. If the
		array is simple, the values must be given in the order that were declared
		at fields. In associative array each item must have a keyname that exists
		in fields and the desired value. Fields that are ommitted are set the value
		'' (empty string).
	*/
	public static function create()
	{	$called_class = get_called_class();

		// Initialize static
		if (($class_desc = self::init_static($called_class)) === false)
			return false;

		// Prepare variables;
		$args = func_get_args();
		$create_params = array();
		foreach($class_desc['fields'] as $field_name => $field)
			if (!$field['ai']) $create_params[$field_name] = '';
		$exec_params = array($class_desc['sql']['create']['stmt'], str_repeat("s", count($create_params)));

		// Check parameters
		if ((count($args) == 1) && (is_array($args[0]))) 	// Trick to get arguments as an array
			$args = $args[0];								// or as parameters.
		
		if (count($args) == 0)
			return false;

		// Check if it is associative or numeric array	
		$is_numeric = false;
		foreach($args as $arg_key => $arg_value)
		{	if (is_numeric($arg_key) && ($arg_key == 0))
				$is_numeric = true;
			break;
		}
		
		// Create array with parameters
		if ($is_numeric)
		{	// Check if we same or less values
			if (count($args) > count($create_params))
				return false;
			
			$count = 0;
			foreach($create_params as $param_name => &$param_value)
			{	if ($count >= count($args)) break;

				$param_value = $args[$count];
				$count ++;
			}
			unset($param_value);
		}
		else
		{
			// Parameters as associative array
			foreach($args as $arg_key => $arg_value)
			{
				if (!array_key_exists($arg_key, $create_params))
					return false;
				$create_params[$arg_key] = $arg_value;
			}
		}
		$exec_params = array_merge($exec_params, $create_params);

		// Execute query
		if (($res_array = call_user_func_array(array('dbconn', 'execute'), $exec_params)) === false)
			return false;
		
		// Open object
		return DBRecord::open(dbconn::last_insert_id(), $called_class);
	}
	
	//! Open the dbrecord based on its primary key
	public static function open($primary_key, $called_class = NULL)
	{	if ($called_class === NULL)
			$called_class = get_called_class();

		// Initialize static
		if (($class_desc = self::init_static($called_class)) === false)
			return false;

		// Execute query
		if (($res_array = dbconn::execute_fetch_all($class_desc['sql']['open']['stmt'], 's', $primary_key)) === false)
			return false;
		
		// Check that we have 1 answer
		if (count($res_array) != 1)
			return false;
		
		// Create object
		$obj = new $called_class($called_class);
		
		// Populate data
		foreach($class_desc['fields'] as $field_name => $field)
			if ($class_desc['fields'][$field_name]['type'] == 'datetime')
				$obj->data[$field_name] = new DateTime('@' . $res_array[0][$field['sqlfield']]);
			else
				$obj->data[$field_name] = $res_array[0][$field['sqlfield']];
		return $obj;
	}
	
	//! Open multiple records at the same time
	public static function open_many($pks, $called_class = NULL)
	{	if ($called_class === NULL)
			$called_class = get_called_class();
		
		$recs = array();
		foreach($pks as $pk)
			$recs[] = self::open($pk, $called_class);
		return $recs;
	}
	
	//! Open all records
	public static function open_all($called_class = NULL)
	{	if ($called_class === NULL)
			$called_class = get_called_class();
		
		// Initialize static
		if (($class_desc = self::init_static($called_class)) === false)
			return false;
			
		// Execute query
		if (($res_array = dbconn::execute_fetch_all($class_desc['sql']['all']['stmt'])) === false)
			return false;
		
		$groups = array();
		foreach($res_array as $res)
			$groups[] = Group::open($res['groupname']);
			
		return $groups;	
	}
			
	//! Save object
	public function save()
	{
		// Update parameters
		$upd_params = array($this->class_desc['sql']['update']['stmt'], str_repeat("s", $this->class_desc['meta']['total_fields']));

		$pk = array();
		foreach($this->class_desc['fields'] as $field_name => $field)
		{	if ($field['pk'])
				$pk[] = $this->data[$field_name];
			else
			{
				if ($this->class_desc['fields'][$field_name]['type'] == 'datetime')
					$upd_params[] = $this->data[$field_name]->format(DATE_ISO8601);
				else
					$upd_params[] = $this->data[$field_name];
			}
		}
		$upd_params = array_merge($upd_params, $pk);
		
		// Execute query
		if (($res_array = call_user_func_array(array('dbconn', 'execute'), $upd_params)) === false)
			return false;

		return true;
	}
	
	//! Get field of record
	public function __get($name)
	{	if (!isset($this->class_desc['fields'][$name]))
			return NULL;

		return $this->data[$name];
	}
	
	//! Set field of record
	public function __set($name, $value)
	{	if (!isset($this->class_desc['fields'][$name]))
			return NULL;
			
		// Check type of data
		if ($this->class_desc['fields'][$name]['type'] == 'datetime')
		{	if (!is_object($value)) $value = new DateTime($value);
			return $this->data[$name] = $value;
		}
		else			
			return $this->data[$name] = $value;
	}
	
	//! Get a list with all fields
	public function fields()
	{	return array_keys($this->class_desc['fields']);
	}
	
	//! Get the list of data
	public function data()
	{	return $this->data;	}
	
}
?>