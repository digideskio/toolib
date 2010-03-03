<?php
require_once(dirname(__FILE__) . '/./mysqli.lib.php');
require_once(dirname(__FILE__) . '/./dbmodelquery.lib.php');
require_once(dirname(__FILE__) . '/./dbmodel.lib.php');
require_once(dirname(__FILE__) . '/../functions.lib.php');

//! Collection of records
class DBRecordCollection implements ArrayAccess, Countable, Iterator
{
	//! Array of data/objects/ok of records
	private $records =  array();
	
	//! The model of contained objects
	private $model = NULL;
	
	//! Flag if we have records
	private $records_have_all_data = false;
	
	//! Build from sqldata
	public function & from_sqldata(& $model, & $sql_data)
	{	$db = new DBRecordCollection($model);
		$db->records = $sql_data;
		return $db;
		$objs =array();
		$model_name = $model->name();
				
		foreach($sql_data as $rec)
			$objs[] = new $model_name($model, $rec);
		return $objs; 
	}
	
	public function from_pks(& $model, & $pks)
	{
		
	}
	//! Construct a DBRecordCollection object
	final private function __construct(& $model)
	{
		$this->model = $model;
	}
	
	/* ArrayAccess Methods */
	public function offsetExists ($offset )
	{	return isset($this->records[$offset]);	}
	
	public function offsetGet($offset)
	{	if (isset($this->records[$offset]))
		{	if ($this->records[$offset] === FALSE)
				return $this->records[$offset] = DBRecord::open($offset, $this->model);		

			else if (is_array($this->records[$offset]))
			{	$model_name = $this->model->name();
				return $this->records[$offset] = new $model_name($this->model, $this->records[$offset]);
			}
			return $this->records[$offset];
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

class DBRecord
{
	//! Array with record constructors
	static $model_constr = array();
	
	//! Initialize model based on the structure of derived class
	static private function init_model($model_name)
	{
		// Create model constructor
		if (!isset(self::$model_constr[$model_name]))
			self::$model_constr[$model_name] = create_function('$sql_data, $model', 
				"return DBRecordCollection::from_sqldata(\$model, \$sql_data, true);");
		
		// Open model if it exists
		if (($md = DBModel::open($model_name)) !== NULL)
			return $md;

		$fields = get_static_var($model_name, 'fields');
		$table = get_static_var($model_name, 'table');
					
		// Check if fields are defined
		if (!is_array($fields))
			throw new InvalidArgumentException('DBRecord::$fields is not defined in derived class');

		// Check if table is defined
		if (!is_string($table))
			throw new InvalidArgumentException('DBRecord::$table is not defined in derived class');
		
		return DBModel::create($model_name, $table, $fields);
	}
	
	//! Perform arbitary query on model and get raw sql results
	static public function raw_query($model_name = NULL)
	{	if ($model_name === NULL)
			$model_name = get_called_class();
		
		$model = & self::init_model($model_name);
		
		return new DBModelQuery($model);
	}
	
	//! Perform a query and return model objects of this query
	static public function open_query($model_name = NULL)
	{	if ($model_name === NULL)
			$model_name = get_called_class();
		
		$model = & self::init_model($model_name);
		
		$query = new DBModelQuery($model, self::$model_constr[$model_name]);
		return $query->select($model->fields());
	}
	
	
	//! Get the model of this record
	static public function model()
	{	$model_name = get_called_class();
		return self::init_model($model_name);
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
	 * 	- @b NULL If the record could not be found.
	 * 	- A DBRecords derived class instance specialized for this record.
	 * 	.
	 * 
	 * @code
	 * // Example reading a news from database with id 14
	 * $n = News::open(14);
	 * @endcode
	*/
	public static function open($primary_keys, $model_name = NULL)
	{	benchmark::checkpoint('pre-get_called');
		if ($model_name === NULL)
			$model_name = get_called_class();

		// Initialize model
		$model = & self::init_model($model_name);
		
		// Execute query and check return value
		$q = self::open_query($model_name);
		foreach($model->pk_fields(false) as $pk)
			$q->where($pk . ' = ?');
		if (count($res = $q->execute('s', $primary_keys)) !== 1)
			return false;	
		return $res[0];
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
	public static function open_all($model_name = NULL)
	{	if ($model_name === NULL)
			$model_name = get_called_class();

		// Initialize model
		$model = & self::init_model($model_name);
		
		// Execute query and check return value
		return self::open_query($model_name)
			->execute();
	}
	
	//! Create a new record in database of this model
	static public function create($args)
	{	// Initialize model
		$model = & self::init_model($model_name = get_called_class());

		// Prepare values
		$insert_args = array('');
		$values = array();
		foreach($model->fields(true) as $field_name => $field)
		{	if ($field['ai'])
				continue;	// We cannot set values for ai fields
			if (isset($args[$field_name]))
				$values[$field_name] = $model->db_field_data($field_name, $args[$field_name]);
			else if ($field['default'] != FALSE)
				$values[$field_name] = $model->db_field_data($field_name, $field['default']);
			else if ($field['pk'])
				throw new RuntimeException("You cannot create a {$model_name} object  without defining ". 
					"non auto increment primary key '{$field['name']}'");
			else
				continue;	// No user input and no default values
				
			$insert_args[0] .= 's';
			$insert_args[] = $values[$field_name]; 
		}
		
		// Prepare query
		$q = self::raw_query($model_name)
			->insert(array_keys($values))
			->values_array(array_fill(0, count($values), NULL));
		
		if (($ret = call_user_func_array(array($q, 'execute'),$insert_args)) === FALSE)
			return false;
	
		// Fill autoincrement fields
		if (count($model->ai_fields()) > 0)
		{	$ai = $model->ai_fields(false);
			$values[$ai[0]] = dbconn::last_insert_id();
		}
		
		// If we have all the attributes of model, directly create object,
		// otherwise open object from database.
		if (count($values) === count($model->fields()))
		{	// Translate data to sql based key
			$sql_fields = array();
			foreach($values as $field_name => $value)
				$sql_fields[$model->field_info($field_name, 'sqlfield')] = $value;			

			return new $model_name($model, $sql_fields);
		}
		
		// Open data based on primary key.
		$pks = $model->pk_fields();
		return DBRecord::open($values[$pks[0]], $model_name);
	}
	
	//! Data values of this instance
	protected $fields_data = array();
	
	//! Cache used for cachings casts
	protected $data_cast_cache = array();
	
	//! Track altered fields for delta updates
	protected $altered_fields = array();
	
	//! Model meta data pointer
	protected $model = NULL;
	
	//! Final constructor of dbrecord 
	/**
	 * Constructor is declared final to prohibit direct instantiantion
	 * of this class.
	 * @remarks
	 * You DON'T use @b new to create objects manually instead use create()
	 * and open() functions that will create objects for you.
	 * 
	 * @param $model_meta The meta data of the model that the instance is build from.
	 * @param $sql_data Data to fill the $fields_data given in assoc array using @i sqlfield as key
	 */
	final public function __construct(& $model, $sql_data = NULL)
	{	$this->model = & $model;
	
		// Populate fields data
		foreach($model->fields(true) as $field_name => $field)
		{	$this->fields_data[$field_name] = (isset($sql_data[$field['sqlfield']]))?$sql_data[$field['sqlfield']]:NULL;
			$this->data_cast_cache[$field_name] = NULL;			
		}
	}
	
	public function save()
	{	
		if(count($this->altered_fields) === 0)
			return true;	// No changes
			
		// Create update query
		$update_args = array(str_repeat('s', 
			count($this->altered_fields) + count($this->model->pk_fields()))
		);
		$q = self::raw_query($this->model->name())
			->update()
			->limit(1);
			
		// Add delta fields
		foreach($this->altered_fields as $field_name => $flag)
		{	$q->set($field_name);
			$update_args[] = $this->fields_data[$field_name];
		}
		
		// Add Where clause based on primary keys
		foreach($this->model->pk_fields() as $pk)
		{	$q->where("{$pk} = ?");
			$update_args[] = $this->fields_data[$pk];
		}
		
		// Execute query
		return call_user_func_array(array($q, 'execute'), $update_args);
	}
	
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
	{	
		// Create delete query
		$delete_args = array(str_repeat('s', count($this->model->pk_fields())));
		$q = self::raw_query($this->model->name())
			->delete()
			->limit(1);
		
		// Add Where clause based on primary keys
		foreach($this->model->pk_fields() as $pk)
		{	$q->where("{$pk} = ?");
			$delete_args[] = $this->fields_data[$pk];
		}
		
		// Execute query
		return call_user_func_array(array($q, 'execute'), $delete_args);
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
	{
		if (!$this->model->has_field($name))
		{	// Oops!
		    $trace = debug_backtrace();
			throw new InvalidArgumentException("{$this->model->name()}(DBRecord)->{$name}" . 
				" is not valid field of model {$this->model->name()}, requested at {$trace[0]['file']} ".
				" on line {$trace[0]['line']}");			
		}
		
		// Check for data
		return $this->model->user_field_data(
			$name,
			$this->fields_data[$name]
		);
	}
	
	//! Set the value of a field
	public function __set($name, $value)
	{
	if (!$this->model->has_field($name))
		{	// Oops!
		    $trace = debug_backtrace();
			throw new InvalidArgumentException("{$this->model->name()}(DBRecord)->{$name}" . 
				" is not valid field of model {$this->model->name()}, requested at {$trace[0]['file']} ".
				" on line {$trace[0]['line']}");			
		}
		
		// Marke it as altered
		$this->altered_fields[$name] = true;
		
		// Set data
		return $this->fields_data[$name] = 
			$this->model->db_field_data(
				$name,
				$value
			);		
	}
	
	//! Serialization implementation
	public function __sleep()
	{	return array('fields_data', 'altered_fields');
	}
	
	//! Unserilization implementation
	public function __wakeup()
	{	// Initialize static
		$this->model = self::init_model(get_class($this));
	}
}
?>