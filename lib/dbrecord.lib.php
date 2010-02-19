<?php
	require_once('mysqli.lib.php');

//! Collection of records
class DBRecordCollection implements ArrayAccess, Countable, Iterator
{
	private $records =  array();
	private $model = '';
	
	public function __construct($records, $model)
	{
		$this->records = $records;
		$this->model = $model;
	}
	
	/* ArrayAccess Methods */
	public function offsetExists ($offset )
	{	return isset($this->records[$offset]);	}
	public function offsetGet($offset)
	{	if (isset($this->records[$offset]))
		{	if ($this->records[$offset] !== FALSE)
				return $this->records[$offset];
			return $this->records[$offset] = DBRecord::open($offset, $this->model);
		}			
	}
	public function offsetSet ($offset , $value){}
	public function offsetUnset ($offset ){}
	
	/*  Iterator Methods */
	public function current()
	{	return $this->offsetGet($this->key());	}
	public function key()
	{	return key($this->records);	}
	public function next()
	{	return next($this->records);	}
	public function rewind()
	{	reset($this->records);	}
	public function valid()
	{	return ($this->key() !== NULL);	}
	
	/* Countable Methods */
	public function count()
	{	return count($this->records);	}
	
	//! Take a subset of collection
	public function slice($offset, $length = NULL)
	{	
		return new DBRecordCollection(array_slice($this->records, $offset, $length, true), $this->model);
	}

	//! Get a row of this collection based on its index in this collection
	public function row($num_offset)
	{	if ($num_offset >= $this->count())
			return FALSE;
		$keys = array_keys($this->records);
		return $this[$keys[$num_offset]];
	}
}

//! DBRecord is the primative to ORM
/**
 * @todo Multi PKs Internaly PKs are saved as an array, however
 * 	the external interface does not accept multiple pks atm
 * @todo Table relationships M-1 (this is the most common)
 * @todo Table relationships M-N 
 * 
 * DBRecord is a base class for creating "Object Relational Mapping" objects. In simple words
 * DBRecord is used to access records in database tables using the concept of objects. Here
 * each table is described by subclassing DBRecord and providing information about the table
 * name and field properties. After subclassing you can access records of this table
 * by using the public interface of DBRecord.
 * 
 * @note It support transparent data objects convertion from php native objects to sql.
 * @note Currently it does @b NOT support inter-table relationships.
 *  
 * @par Defining table mapping	
 * @b Example:
 * @code
 * class News extends DBRecord
 * {
 * 	// Table is news
 * 	public static $table = 'news';
 * 	
 * 	// Fields
 * 	public static $fields = array(
 * 		'id' => array('pk' => true, 
 * 			'sqlfield' => 'new_id', 
 * 			'ai' => true
 * 		),
 * 		'title',
 * 		'poster',
 * 		'post',
 * 		'post_time' => array('type' => 'datetime'),
 * 		'published'
 * 	);
 * }
 * @endcode
 * \n
 * @b $table: \n	
 * This is mandatory and it is the name of the database table.
 * \n	
 * @b $fields: \n
 * An array with all table fields that you want to map. Usually these are all the fields that
 * the table has, however DBRecord can work with a subset of columns as long as the SQL table definition
 * permits it. For example if you have @b not included a column that has 'NOT NULL' flag and not the 'DEFAULT'
 * value, don't expect from DBRecord to guess the default value. You will probably get an SQL error if you try to
 * create a record.\n Each entry can be either a string with the name of the field (must be the same with database field) 
 * or another array with the parameters of the field.
 * Valid paramaters of fields are:
 * 	- @b pk: [Default = false] The field that has this option set true will be consider primary key.
 * 	- @b ai: [Default = false] Autoincremet can be set true to fields that are primary keys otherwise
 * 		this option will be set to false.
 * 	- @b sqlfield: [Default = "array entry key"] If you want to create a field that has a different name
 * 		than the database field, you must set this value to the name of the SQL's field.
 * 	- @b type: [Default = "generic"] Supported types are "generic", "datetime", "serialized"
 * 		- "generic" type will cast data as string.
 * 		- "datetime" will accept only php DateTime objects and returns php DateTime objects.
 * 		- "serialized" will serialize provided data and save them to database and will unserialize them 
 * 			from database trasparently. It will not check for proper SQL field size.
 * 		.
 * 	.
 * 
 * @par Basic Usage
 * 	DBRecord after specialization by creating derived classes it support "insert", "select",
 * 	"update" and "delete" SQL commands, by using create() open() save() and delete() functions
 * 	of DBRecord objects.
 * \n
 * <b> As part of the previous example </b>
 * @code
 * // Create a news entry
 * $new = News::create(
 * 	'title' => 'We will die again this year',
 * 	'poster' => 'bob',
 * 	'post' => 'Every year ...',
 * 	'post_time' => new DateTime(),
 * 	'published' => true
 * );
 * 
 * // Change it (by direct access of fields on the object)
 * $new->post = 'Last year ...';
 * // Update database
 * $new->save();
 * 
 * // Open an old one by id
 * $old->open(15);
 * 
 * // Delete it
 * $old->delete();
 * @endcode
 */
class DBRecord
{
	//! Parameter for controlling cacher
	public static $cacher = NULL;
	
	//! All the classes that are using DBRecord
	protected static $classes = array();
	
	//! Data of record
	protected $data = array();

	//! Cache of data
	protected $data_cast_cache = array();
	
	//! Class description
	protected $class_desc = false;

	//! Final constructor of dbrecord 
	/**
	 * Constructor is declared final and protected to prohibit direct instantiantion
	 * of this class.
	 * @remarks
	 * You DON'T use @b new to create objects manually instead use create()
	 * and open() functions that will create objects for you.
	 */
	final protected function __construct($class_name)
	{
		// Save class description
		$this->class_desc = & self::$classes[strtolower($class_name)];
		
		// Populate data
		foreach($this->class_desc['fields'] as $field_name => $field)
		{	$this->data[$field_name] = NULL;
			$this->data_cast_cache[$field_name] = NULL;
		}
	}
	
	//! Open a connection of relation ships
	private function open_relationship($field, $key)
	{	if (($res_array = dbconn::execute_fetch_all($this->class_desc['sql']['rels'][$field['foreign_model']]['stmt'], 
			's', $key)) === FALSE)
			return false;

		$records = array();
		foreach($res_array as $rec)
			$records[$rec[0]] = FALSE;
		return new DBRecordCollection($records, $field['foreign_model']);
	}

	//! Convert field from data to user format
	private static function cast_todb($field, $what)
	{	if ($field['type'] == 'serialized')
			return serialize($what);
		else if ($field['type'] == 'datetime')
			return $what->format(DATE_ISO8601);
		else if ($field['type'] == 'relationship')
			return $description;
		return $what;
	}

	//! Convert field from user format to sql format
	private static function cast_fromdb($field, $what, & $obj_cache = NULL, $pthis = NULL)
	{	if ($field['type'] == 'serialized')
			return ($obj_cache === NULL)?($obj_cache = unserialize($what)):$obj_cache;
		else if ($field['type'] == 'datetime')
			return ($obj_cache === NULL)?($obj_cache = new DateTime( $what)):$obj_cache;
		else if ($field['type'] == 'foreign')
			return call_user_func(array($field['foreign_model'], 'open'), 
					1, $field['foreign_model']);	//! Zong with custom late static binding we have to force class
		else if ($field['type'] == 'relationship')
			return $pthis->open_relationship($field, $what);		
		return $what;
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
		
		// RELATIONSHIPS
		foreach($class_desc['fields'] as $field_name => $field)
		{	if (($field['type'] != 'relationship') || ($field['has_many'] != true))
				continue;

			$class_desc['sql']['rels'][$field_name]['query'] = 'SELECT ' .
				' id ' . ' FROM ' . $field['foreign_model'] . ' WHERE ' . $field['foreign_field'] . ' = ?';
			$class_desc['sql']['rels'][$field_name]['stmt'] = 'dbrecord-' . 
				strtolower($class_desc['class']) . '-rel-' . strtolower($field_name);
		}
	}

	//! Initialize static parameters of the dbrecord specialization
	private static function init_static($called_class)
	{	// Check if this is cached class
		if (!isset(self::$classes[strtolower($called_class)]))
		{	
			// Initialize values
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
				$default_field_options = array(
					'sqlfield' => $field_name,	
					'type' => 'generic',
					'pk' => false,
					'ai' => false,
				);
				$filtered_field = array_merge($default_field_options, $field);
				
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
			self::$classes[strtolower($called_class)] = array(
				'fields' => $filtered_fields, 
				'table' => $child_table,
				'meta' => $child_meta,
				'class' => $called_class
			);
			$class_desc = & self::$classes[strtolower($called_class)];			
			 
			// Create sql-queries
			self::craft_sql($class_desc);

			// Prepare sql statements
			foreach($class_desc['sql'] as $entry_name => $entry)
				if ($entry_name == 'rels')
					foreach($entry as $rel)
						dbconn::prepare($rel['stmt'], $rel['query']);
				else
					dbconn::prepare($entry['stmt'], $entry['query']);
		}
		return self::$classes[strtolower($called_class)];
	}
	

	//! Create a new record
	/**
	 * 
	 * Parameters can be passed in 3 ways.
	 * 	- As simple function arguments create(a,b,c) but a, b and c must be given
	 * 		in the order that were declared in fields.
	 * 	- As a simple array, create(array(a,b,c)) this is like the previous one
	 * 		but arguments are encapsulated in array. Again parameters must be given
	 * 		in the same order as they were declared.
	 * 	- As an associative array create(array('field1' => a, 'field3' => c, 'field2' =>b))
	 * 		It is like simple array but the key of each entry is the field that will be set
	 * 		with the followed value. In this type the order has no meaning.
	 * .
	 * @remarks In all cases fields that are ommitted are set the value '' (empty string).
	 * 
	 * @code
	 * // Example using arguments
	 * $n = News::create('My special title', 'A big post ...');
	 * 
	 * // Example using simple array:
	 * $n = News::create(array('My special title', 'A big post ...'));
	 * 
	 * // Example using associative array
	 * $n = News::create(array('post' => 'A big post ...', 'title' => 'My special title',));
	 * @endcode
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

		// Prepare default values;
		$field_values = array();
		foreach($class_desc['fields'] as $field_name => $field)
			if (!$field['ai']) $field_values[$field_name] = '';

		// Check if it is numeric array
		if (array_keys($args) === range(0, count($args) - 1))
		{	// Check if we same or less values
			if (count($args) > count($field_values))
				return false;
				
			// Convert numeric array to associative
			$field_values = array_combine(array_keys($field_values), array_pad($args, count($field_values), ''));
		}
		else
			$field_values = array_merge($field_values, $args);
		$insert_pk = $field_values[$class_desc['meta']['pk'][0]];
		
		// Convert user data to sql
		foreach($field_values as $name => $value)
			$field_values[$name] = self::cast_todb($class_desc['fields'][$name], $value);
		

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
	 * 
	 * It will query database table for a record with the supplied primary key. It will
	 * read the data and return an DBRecord object for this record.
	 * 
	 * @param $primary_key The primary key value of the desired record.
	 * @param $called_class This parameter must be @b ALWAYS NULL. It would be better
	 * 	if you never used it all, as it is a reserved one for internal use to simulate
	 * 	"Late static binding" on PHP version earlier than PHP5.3
	 * @return 
	 * 	- @b false If the record could not be found.
	 * 	- A DBRecords derived class instance specialized for this record.
	 * 	.
	 * 
	 * 
	 * @code
	 * // Example reading a news from database with id 14
	 * $n = News::open(14);
	 * @endcode
	*/
	public static function open($primary_key, $called_class = NULL)
	{	if ($called_class === NULL)
			$called_class = get_called_class();

		if (self::$cacher !== NULL)
		{	$obj = self::$cacher->get('dbrecord: ' . $called_class . '-' . $primary_key, $succ);
			if ($succ === true)
				return $obj;
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

		if (self::$cacher !== NULL)
			self::$cacher->set('dbrecord: ' . $called_class . '-' . $primary_key, $obj);
		return $obj;
	}
	
	//! Open multiple records at the same time
	/**
	 * 
	 * It will query database table for records with the supplied primary keys.
	 * 
	 * @param $pks An array of primary key values of the desired records.
	 * @param $called_class This parameter must be @b ALWAYS NULL. It would be better
	 * 	if you never used it all, as it is a reserved one for internal use to simulate
	 * 	"Late static binding" on PHP version earlier than PHP5.3
	 * @return 
	 * 	- @b false If the records could not be found.
	 * 	- An @b array of DBRecords derived class instances for each record.
	 * 	.	
	 *
	 * @code
	 * // Example reading a news from database with id 14
	 * $ns = News::open_many(array(11,10, 14));
	 * @endcode
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
	 * It will query database table and return all the records of the table.
	 * 
	 * @param $called_class This parameter must be @b ALWAYS NULL. It would be better
	 * 	if you never used it all, as it is a reserved one for internal use to simulate
	 * 	"Late static binding" on PHP version earlier than PHP5.3
	 * @return 
	 * 	- @b false If any error occurs
	 * 	- An @b DBRecordCollection for all database records.
	 * 	.	
	 * 
	 * @code
	 * // Example reading a news from database with id 14
	 * $all_news = News::open_all();
	 * @endcode
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
			$recs[$res[0]] = FALSE;
			
		return new DBRecordCollection($recs, $called_class);
	}

	/**
	 * @todo make it REAL criteria!
	 * Acceptable criteria are
	 * 'limit', 'offset'
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
	 * This could also be done by executing:
	 * @code
	 * $total_news = count(News::open_all());
	 * @endcode
	 * but it would be VERY expensive process for a simple number. That
	 * is why this function was implemented; cheap row counting.
	 * 
	 * @param $called_class This parameter must be @b ALWAYS NULL. It would be better
	 * 	if you never used it all, as it is a reserved one for internal use to simulate
	 * 	"Late static binding" on PHP version earlier than PHP5.3
	 * @return 
	 * - @b false If any error occurs
	 * - The number of records in table
	 * .	
	 * 
	 * @code
	 * // Example reading a news from database with id 14
	 * $total_news = News::count_all();
	 * @endcode
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
	 * If you change the field values of a DBRecord object they are not
	 * saved directly on database, but you must execute save().
	 * 
	 * It will take all current data of this object instance
	 * and dump the in the database based on the primary key of this
	 * instance.
	 * 
	 * @return
	 * 	- @b true If the data were saved successfuly in database.
	 * 	- @b false On any error
	 * 	.
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
		if (self::$cacher !== NULL)
			self::$cacher->delete('dbrecord: ' . $this->class_desc['class'] . '-' . $pk[0]);
		return true;
	}
	
	//! Get the value of a field
	/**
	 * It will return data of any field that you request. Data will be 
	 * converted from sql format to user format before returned. This means
	 * that fields of type "datetime" will be converted to php native DateTime object,
	 * "serialized" fields will be unserialized before returned to user.
	 * 
	 * @param $name
	 * @return 
	 * 	- The data of the field converted in user format.
	 * 	- @b NULL if there is no field with that name. In that case a php error will be triggered too.
	 *	.
	 *
	 * @note __get() and __set() are php magic methods and can be declare to overload the 
	 *  standard procedure of accesing object properties. It is @b not not nessecary to
	 *  use them as function @code echo $record->__get('myfield'); @endcode but use them as
	 *  object properties @code echo $record->myfield; @endcode
	 * 
	 * @see __set()
	 */
	public function __get($name)
	{	if (!isset($this->class_desc['fields'][$name]))
		{	// Raise a notice!!!
		    $trace = debug_backtrace();
			trigger_error('DBRecord('. $this->class_desc['class'] . ')->' . $name . 
				' is not valid field in ' . $trace[0]['file'] .
				' on line ' . $trace[0]['line']);
	
			return NULL;
		}

		// Check for data
		return self::cast_fromdb(
			$this->class_desc['fields'][$name], 
			$this->data[$name],
			$this->data_cast_cache[$name],
			$this
		);
	}
	
	//! Set the value of a field
	/**
	 * It will set the value of a a field. Data must be given in user format.
	 * This means that that fields of type "datetime" a DateTime object must be given
	 * etc.
	 * 
	 * @param $name The name of the field as it was given in the $fields.
	 * @param $value The new value of field given in user format. 
	 * @return 
	 * 	- On success it will return the same value as $value.
	 * 	- @b NULL if there is no field with that name. In that case a php error will be triggered too.
	 *	.
	 *
	 * @note __get() and __set() are php magic methods and can be declare to overload the 
	 *  standard procedure of accesing object properties. It is @b not not nessecary to
	 *  use them as function @code $record->__set('myfield', 'myname'); @endcode but use them as
	 *  object properties @code $record->myfield = "my name"; @endcode
	 * 
	 * @remarks Changing field data does not save them in database. You
	 * 	must execute save() to dump changes in database.
	 * @see __get() save()
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
		$this->data[$name] = self::cast_todb(
			$this->class_desc['fields'][$name],
			$value
		);
		return $value;
	}
	
	//! Get a list with all field names
	/**
	 * 
	 * @return With an array with all field names (not the sqlfield names in case
	 * 	of alias)
	*/
	public function field_names()
	{	return array_keys($this->class_desc['fields']);
	}
	
	//! Get a reference to records data
	/**
	 * Get the a reference to the associative array 
	 * which holds all the data of this record. The keys to the
	 * array are the field names (not the sqlfield names in case of alias).
	 * @remarks
	 * Be carefull when changing data as the internal storage is not the same as
	 * the one that one used by __get() and __set(). Internaly data are stored in SQL format, 
	 * for example for fields of type "datatime" data are stored in a string with datetime 
	 * in ISO format. For "serilizable" fields data are saved in serialized format. If you just
	 * need to change field values use direct member access more info at __get(), __set().
	 * 
	 * @note
	 * Changing data does NOT upadte record in database you must execute save() to update database.
	 * @return Reference to internal associative array which holds all the data of fields
	 * @see save() __get() __set()
	*/
	public function & data()
	{	return $this->data;		}
	
	//! Delete this record
	/**
	 * It will delete the record from database. However the object
	 * will not be destroyed so be carefull to dump it after deletion.
		
	 * @note DBRecord supports a special function @b on_delete(). If
	 * 	this function is declared in the derived class it will be executed
	 * 	before actually deleting anything. If this function returns true
	 * 	the process will continue, if false the process will be stopped
	 * 	leaving data and objects intact.
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
			
		if (self::$cacher !== NULL)
			self::$cacher->delete('dbrecord: ' . $this->class_desc['class'] . '-' . $this_pk);
		return true;
	}
	 
	//! Serialization implementation
	public function __sleep()
	{	return array('data');
	}
	
	//! Unserilization implementation
	public function __wakeup()
	{	// Initialize static
		self::init_static(get_class($this));
		$this->class_desc = & self::$classes[get_class($this)];
	}
}
?>