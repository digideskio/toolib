<?php
	require_once('mysqli.lib.php');

//! Class for managing records of a table
/**
	@todo Multi PKs
	@todo Table relationships M-1 and M-N
	DBRecord is a base class for creating database record handlers.
	To create a handler, you must define a derived and class and populate
	two static properties, $table and $fields. Those two properties will
	hold the description and the name of table to use.
	
	@b Example:
@code
class News extends DBRecord
{
	// Table is news
	public static $table = 'news';
	
	// Fields
	public static $fields = array(
		'id' => array('pk' => true, 
			'sqlfield' => 'new_id', 
			'ai' => true
		),
		'title',
		'poster',
		'post',
		'post_time' => array('type' => 'datetime'),
		'published'
	);
}
@endcode

	@note Currently	it only supports single-table records. 
	
	@b $table: \n	
	This is mandatory and it is the name of the table in database that holds the records.
	
	@b $fields: \n	
	An array with the columns that you want to manipulate. Usually these are all the columns that
	the table has, however DBRecord can work with a subset of columns as long as the SQL table definition
	permits it. For example if you have @b not included a column that has 'NOT NULL' flag and a 'DEFAULT'
	value, don't except from DBRecord to guess a value. You will probably get an SQL error if you try to
	create a record.\n
	$fields is an array with all the fields that of the record. Each entry can be either a string with
	the name of the field (must be the same with database field) or	another array with the parameters of the field.
	Valid paramaters of fields are:
		- @b pk: [Default = false] The field that has this option set true will be consider primary key.
		- @b ai: [Default = false] Autoincremet can be set true to fields that are primary keys otherwise
			this option will be set to false.
		- @b sqlfield: [Default = "array entry key"] If you want to create a field that has a different name
		than the database field, you must set this value to the name of the SQL's field.
		- @b type: [Default = "general"] Supported types are "general", "datetime", "serialized"
			- "general" type will cast data as string.
			- "datetime" will accept only php DateTime objects and returns php DateTime objects.
			- "serialized" will serialize provided data and save them to database and will unserialize them 
				from database trasparently. It will not check for proper SQL field size.
			.
		.

*/
class DBRecord
{
	//! Parameter of DBRecord to use session to cache classes
	public static $session_cache = false;
	
	//! Parameter for controlling apc
	public static $apc_cache = false;
	
	//! APC name prefixing
	public static $apc_prefix = '';
	
	//! All the classes that are using DBRecord
	protected static $classes = array();
	
	//! Data of record
	protected $data = array();

	//! Cache of data
	private $data_cast_cache = array();
	
	//! Class description
	protected $class_desc = false;
	
	//! Constructor of dbrecord (prevent all derived objects from direct instantiation!)
	final protected function __construct($class_name)
	{
		// Save class description
		$this->class_desc = & self::$classes[$class_name];
		
		// Populate data
		foreach($this->class_desc['fields'] as $field_name => $field)
		{	$this->data[$field_name] = NULL;
			$this->data_cast_cache[$field_name] = NULL;
		}
	}

	//! Convert field from data to user format
	private static function cast_data_to_sql($type, $obj)
	{	if ($type == 'serialized')
			return serialize($obj);
		if ($type == 'datetime')
			return $obj->format(DATE_ISO8601);
		return $obj;
	}

	//! Convert field from user format to sql format
	private static function cast_sql_to_data($type, $sql, & $obj_cache = NULL)
	{	if ($type == 'serialized')
			return ($obj_cache === NULL)?($obj_cache = unserialize($sql)):$obj_cache;
		if ($type == 'datetime')
			return ($obj_cache === NULL)?($obj_cache = new DateTime( $sql)):$obj_cache;	
		return $sql;
	}

	//! Craft sql queries based on a class description and save them in $class_desc
	private static function craft_sql(& $class_desc)
	{
		// SELECT
		$query = 'SELECT ' ;
		foreach($class_desc['fields'] as $field)
			$sel_fields[] .= '`' . $field['sqlfield'] . '`';
		$query .= implode(', ', $sel_fields) . ' FROM ' . $class_desc['table'] . ' WHERE ' . 
			$class_desc['fields'][$class_desc['meta']['pk'][0]]['sqlfield'] . ' = ?';
		$class_desc['sql']['open']['query'] = $query;
		$class_desc['sql']['open']['stmt'] = 'dbrecord-' . strtolower($class_desc['class']) . '-open';

		// INSERT
		foreach($class_desc['fields'] as $field)
		{	if ($field['ai'])
				continue;
			$ins_fields[] = '`' . $field['sqlfield'] . '`';
		}
		$query = 'INSERT INTO ' . $class_desc['table'] . '(' . implode(', ', $ins_fields) . 
			') VALUES(' . implode(', ', array_fill(0, count($ins_fields), '?')) . ')';
		$class_desc['sql']['create']['query'] = $query;
		$class_desc['sql']['create']['stmt'] = 'dbrecord-' . strtolower($class_desc['class']) . '-create';
		
		// UPDATE
		$upd_fields = array();
		foreach($class_desc['fields'] as $field)
		{	if ($field['pk'])
				continue;
			$upd_fields[] = '`' . $field['sqlfield'] . '` = ? ';
		}
		$query = 'UPDATE ' . $class_desc['table'] . ' SET ' . implode(', ', $upd_fields) 
			. ' WHERE ' . $class_desc['fields'][$class_desc['meta']['pk'][0]]['sqlfield'] . '=?';
		$class_desc['sql']['update']['query'] = $query;
		$class_desc['sql']['update']['stmt'] = 'dbrecord-' . strtolower($class_desc['class']) . '-update';
		
		// DELETE
		$query = 'DELETE FROM ' . $class_desc['table'] . ' WHERE ' 
			. $class_desc['fields'][$class_desc['meta']['pk'][0]]['sqlfield'] . '=? LIMIT 1';
		$class_desc['sql']['delete']['query'] = $query;
		$class_desc['sql']['delete']['stmt'] = 'dbrecord-' . strtolower($class_desc['class']) . '-delete';
		
		// OPEN ALL
		$class_desc['sql']['all']['query'] = 'SELECT ' . 
			$class_desc['fields'][$class_desc['meta']['pk'][0]]['sqlfield'] . ' FROM ' . $class_desc['table'];
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
			{	// Initialize values
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
				{	// Check if it was given as number entry or associative entry
					if (is_numeric($field_name) && is_string($field))
					{	$field_name = $field; 
						$field = array();
					}
					
					// Setup default values of fields
					$default_field_values = array(
						'sqlfield' => $field_name,	
						'type' => 'general',
						'pk' => false,
						'ai' => false,
					);
					$filtered_field = array_merge($default_field_values, $field);
					
					// Find primary key(s)
					if ($filtered_field['pk'])
					{
						$child_meta['pk'][] = $field_name;
						if ($filtered_field['ai'])
							$child_meta['ai'][] = $field_name;
					}
					else if ($filtered_field['ai'])
						$filtered_field['ai'] = false;
						
					$filtered_fields[$field_name] = $filtered_field;
				}
				
				// Add extra meta data
				$child_meta['total_fields'] = count($filtered_fields);
				
				// Save class characteristics
				self::$classes[$called_class] = array(
					'fields' => $filtered_fields, 
					'table' => $child_table,
					'meta' => $child_meta,
					'class' => $called_class
				);
				$class_desc = & self::$classes[$called_class];
				 
				// Create sql-queries
				self::craft_sql($class_desc);
	
				$_SESSION['dbrecord-cache-2'][$called_class] = self::$classes[$called_class];
			}
			else
			{	self::$classes[$called_class] = $_SESSION['db-record-cache-2'][$called_class];
				$class_desc = & self::$classes[$called_class];
			}

			// Prepare sql statements
			foreach($class_desc['sql'] as $entry_name => $entry)
				dbconn::prepare($entry['stmt'], $entry['query']);
		}
//		echo '<pre>'; var_dump($class_desc); echo '</pre>';
		return self::$classes[$called_class];
	}
	
	//! Create a new record
	/** 
		Parameters can be passed in 3 ways.
			- As simple function arguments create(a,b,c) but a, b and c must be given
			in the order that were declared in fields.
			- As a simple array, create(array(a,b,c)) this is like the previous one
			but arguments are encapsulated in array. Again parameters must be given
			in the same order as they were declared.
			- As an associative array create(array('field1' => a, 'field3' => c, 'field2' =>b))
			It is like simple array but the key of each entry is the field that will be set
			with the followed value. In this type the order has no meaning.
			.
		@remarks In all cases fields that are ommitted are set the value '' (empty string).
		
@code
// Example using arguments
$n = News::create('My special title', 'A big post ...');

// Example using simple array:
$n = News::create(array('My special title', 'A big post ...'));

// Example using associative array
$n = News::create(array('post' => 'A big post ...', 'title' => 'My special title',));
@endcode
	*/
	public static function create()
	{	// Initialize static
		if (($class_desc = self::init_static($called_class = get_called_class())) === false)
			return false;

		// Check parameters
		$args = func_get_args();
		if ((count($args) == 1) && (is_array($args[0]))) 	// Trick to get arguments as an array
			$args = $args[0];								// or as parameters.
		
		if (count($args) == 0)
			return false;

		// Prepare variables;
		$field_values = array();
		foreach($class_desc['fields'] as $field_name => $field)
			if (!$field['ai']) $field_values[$field_name] = '';

		// Check if it is associative or numeric array	
		$is_numeric = (array_keys($args) === range(0, count($args) - 1));
		
		// Create array with parameters
		if ($is_numeric)
		{	// Check if we same or less values
			if (count($args) > count($field_values))
				return false;
			
			$count = 0;
			foreach($field_values as $param_name => &$param_value)
			{	if ($count >= count($args)) break;

				// Cast data to sql type
				$param_value = self::cast_data_to_sql($class_desc['fields'][$param_name]['type'], $args[$count]);

				if ($class_desc['fields'][$param_name]['pk'])
					$insert_pk = $param_value;
				$count ++;
			}
			unset($param_value);
		}
		else
		{
			// Parameters as associative array
			foreach($args as $arg_key => $arg_value)
			{	if (!array_key_exists($arg_key, $field_values))
					return false;

				$field_values[$arg_key] = self::cast_data_to_sql($class_desc['fields'][$arg_key]['type'], $arg_value);

				if ($class_desc['fields'][$arg_key]['pk'])
					$insert_pk = $arg_value;
			}
		}

		// Execute query
		$exec_params = array($class_desc['sql']['create']['stmt'], str_repeat("s", count($field_values)));
		$exec_params = array_merge($exec_params, $field_values);
		if (($res_array = call_user_func_array(array('dbconn', 'execute'), $exec_params)) === false)
			return false;
		
		// Open object
		if (count($class_desc['meta']['ai']) > 0)
			return DBRecord::open(dbconn::last_insert_id(), $called_class);
		else
			return DBRecord::open($insert_pk, $called_class);
	}
	
	//! Open the dbrecord based on its primary key
	/**
		It will query database table for a record with the supplied primary key. It will
		read the data and return an DBRecord object for this record.
		
		@param $primary_key The primary key value of the desired record.
		@return 
			- @b false If the record could not be found.
			- A DBRecords derived class instance specialized for this record.
			.		
@code
// Example reading a news from database with id 14
$n = News::open(14);
@endcode
	*/
	public static function open($primary_key, $called_class = NULL)
	{	if ($called_class === NULL)
			$called_class = get_called_class();

		if (self::$apc_cache)
		{	$obj = apc_fetch('dbrecord-' . self::$apc_prefix . '-' . $called_class . '-' . $primary_key, $succ);
			if ($succ === true)
				return $obj;
			unset($obj);
		}

		// Initialize static
		if (($class_desc = self::init_static($called_class)) === false)
			return false;
					
		// Execute query and check return value
		if (count($res_array = dbconn::execute_fetch_all($class_desc['sql']['open']['stmt'], 's', $primary_key)) !== 1)
			return false;
				
		// Create dbrecord object
		$obj = new $called_class($called_class);
		
		// Populate data
		foreach($class_desc['fields'] as $field_name => $field)
				$obj->data[$field_name] = $res_array[0][$field['sqlfield']];

		if (self::$apc_cache)
			apc_store('dbrecord-' .self::$apc_prefix . '-' . $called_class . '-' . $primary_key, $obj);
		return $obj;
	}
	
	//! Open multiple records at the same time
	/**
		It will query database table for records with the supplied primary keys.
				
		@param $pks An array of primary key values of the desired records.
		@return 
			- @b false If the records could not be found.
			- An @b array of DBRecords derived class instances for each record.
			.	
		
@code
// Example reading a news from database with id 14
$ns = News::open_many(array(11,10, 14));
@endcode
	*/
	public static function open_many($pks, $called_class = NULL)
	{	if ($called_class === NULL)
			$called_class = get_called_class();
		
		$recs = array();
		foreach($pks as $pk)
			$recs[] = self::open($pk, $called_class);
		return $recs;
	}
	
	//! Open all records of this table
	/**
		It will query database table and return all the records
				
		@return 
			- @b false If any error occurs
			- An @b array of DBRecords derived class instances for all database records.
			.	
		
@code
// Example reading a news from database with id 14
$all_news = News::open_all();
@endcode
	*/
	public static function open_all($called_class = NULL)
	{	if ($called_class === NULL)
			$called_class = get_called_class();
		
		// Initialize static
		if (($class_desc = self::init_static($called_class)) === false)
			return false;
			
		// Execute query
		if (($res_array = dbconn::execute_fetch_all($class_desc['sql']['all']['stmt'])) === false)
			return false;
		
		$recs = array();
		foreach($res_array as $res)
			$recs[] = self::open($res[0], $called_class);
			
		return $recs;	
	}

	/**
		Acceptable criteria are
			'limit', 'offset'
	*/
	public static function open_by_criteria($order_by = array(), $options = array(), $called_class = NULL)
	{	if ($called_class === NULL)
			$called_class = get_called_class();
		
		// Initialize static
		if (($class_desc = self::init_static($called_class)) === false)
			return false;
			
		$defoptions = array('limit' => false, 'offset' => false);
		$options = array_merge($defoptions, $options);
		
		// Start up query	
		$query = 'SELECT ' . $class_desc['fields'][$class_desc['meta']['pk'][0]]['sqlfield'] . 
			' FROM ' . $class_desc['table'];
		
		// Order list
		$first = true;
		foreach($order_by as $field => $order)
		{	if (!isset($class_desc['fields'][$field]))
				return false;

			if ($first)
			{	$first = false; $query .= ' ORDER BY ';	}
			else
				$query .= ', ';

			$query .= $class_desc['fields'][$field]['sqlfield'] . ((strtolower($order) =='asc')?' ASC':' DESC');
		}
		
		// Options
		if ($options['limit'])
			$query .= ' LIMIT ' . (($options['offset'] !== false)?$options['offset'] . ', ':'') . $options['limit'];

		if (($res_array = dbconn::query_fetch_all($query)) === false)
			return false;
		
		$recs = array();
		foreach($res_array as $res)
			$recs[] = self::open($res[0], $called_class);
			
		return $recs;	
	}

	//! Count all records of the table
	/**
		This could also be done by executing:
		@code
$total_news = count(News::open_all());
		@endcode
		but it would be VERY expensive process for a simple number. That
		is why this function was implemented; cheap row counting.
						
		@return 
			- @b false If any error occurs
			- The number of records in table
			.	
		
@code
// Example reading a news from database with id 14
$total_news = News::count_all();
@endcode
	*/
	public static function count_all($called_class = NULL)
	{	if ($called_class === NULL)
			$called_class = get_called_class();
		
		// Initialize static
		if (($class_desc = self::init_static($called_class)) === false)
			return false;
			
		$query = 'SELECT count(*) FROM ' . $class_desc['table'];
		$res = dbconn::query_fetch_all($query);
		if (count($res) != 1)
			return false;
		
		return $res[0][0];
	}
	
	//! Save changes in database
	/**
		If you change the field values of a DBRecord object they are not
		saved directly on database, but you must execute save().
		
		It will take all current data of this object instance
		and dump the in the database based on the primary key of this
		instance.
		
		@return
			- @b true If the data were saved successfuly in database.
			- @b false On any error
			.
	*/		
	public function save()
	{
		// Update parameters
		$upd_params = array($this->class_desc['sql']['update']['stmt'], str_repeat("s", $this->class_desc['meta']['total_fields']));

		$pk = array();
		foreach($this->class_desc['fields'] as $field_name => $field)
		{	if ($field['pk'])
				$pk[] = $this->data[$field_name];
			else
				$upd_params[] = $this->data[$field_name];
		}
		$upd_params = array_merge($upd_params, $pk);
		
		// Execute query
		if (call_user_func_array(array('dbconn', 'execute'), $upd_params) === false)
			return false;

		// Remove cache
		if (self::$apc_cache)
			apc_delete('dbrecord-' . self::$apc_prefix . '-' . $this->class_desc['class'] . '-' . $pk[0]);

		return true;
	}
	
	//! Get field of record
	/**
		It will return data of any field that you request. If
		the field is not declared it will return NULL.
	*/
	public function & __get($name)
	{	if (!isset($this->class_desc['fields'][$name]))
		{	// Raise a notice!!!
		    $trace = debug_backtrace();
			trigger_error('DBRecord('. $this->class_desc['class'] . ')->' . $name . 
				' is not valid field in ' . $trace[0]['file'] .
				' on line ' . $trace[0]['line']);
	
			return NULL;
		}

		return self::cast_sql_to_data(
			$this->class_desc['fields'][$name]['type'], 
			$this->data[$name],
			$this->data_cast_cache[$name]
		);
	}
	
	//! Set field of record
	/**
		It will change the fields data in this instance.
		If the field name is not a valid field it will
		change nothing and it will return NULL.
		
		@remarks Changing field data does not save them in database. You
		must execute save() to dump changes in database.
	*/		
	public function __set($name, $value)
	{	if (!isset($this->class_desc['fields'][$name]))
		{	// Raise a notice!!!
		    $trace = debug_backtrace();
			trigger_error('DBRecord('. $this->class_desc['class'] . ')->' . $name . 
				' is not valid field in ' . $trace[0]['file'] .
				' on line ' . $trace[0]['line']);
	
			return NULL;
		}
			
		$this->data_cast_cache[$name] = $value;
		return $this->data[$name] = self::cast_data_to_sql(
			$this->class_desc['fields'][$name]['type'],
			$value
		);
	}
	
	//! Get a list with all fields
	/**
		It returns a sanitized verion of the user supplied
	*/
	public function fields()
	{	return array_keys($this->class_desc['fields']);
	}
	
	//! Get the list of data
	/**
		Returns an associative array will all data of this instance.
	*/
	public function & data()
	{	return $this->data;		}
	
	//! Delete this record
	/**
		It will delete the record from database. However the object
		will not be destroyed so be carefull to dump it after deletion.
		
		@note DBRecord supports a special function @b on_delete(). If
		this function is declared in the derived class it will be executed
		before actually deleting anything. If this function returns true
		the process will continue, if false the process will be stopped
		leaving data and objects intact.
	*/
	public function delete()
	{	$this_pk = array();
		foreach($this->class_desc['fields'] as $field_name => $field)
		{	if ($field['pk'])
				$this_pk = $this->data[$field_name];
		}
		
		// Call pre-delete function
		if (method_exists($this, 'on_delete'))
			if ($this->on_delete() === false)
				return false;

		if (dbconn::execute($this->class_desc['sql']['delete']['stmt'], 's', $this_pk) === false)
			return false;
			
		if (self::$apc_cache)
			apc_delete('dbrecord-' . self::$apc_prefix . '-' . $this->class_desc['class'] . '-' . $this_pk);
		
		return true;
	}
	
	public function __sleep()
	{	return array('data');
	}
	
	public function __wakeup()
	{	// Initialize static
		self::init_static(get_class($this));
		$this->class_desc = & self::$classes[get_class($this)];
	}
}
?>