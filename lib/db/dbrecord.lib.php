<?php
require_once(dirname(__FILE__) . '/./mysqli.lib.php');
require_once(dirname(__FILE__) . '/./dbmodelquery.lib.php');
require_once(dirname(__FILE__) . '/./dbmodel.lib.php');
require_once(dirname(__FILE__) . '/../functions.lib.php');


//! Object handling collection from 1-to-M relationship
/**
 * This object is constructed when requesting a relationship from a DBRecord.
 * Check DBRecord for more information on how to construct it.
 */
class DBRecordManyRelationship
{
	//! The constructed query
	private $query;

    //! Relationship info
    private $rel_params = array();
    
    //! Construct relationship handler
	public function __construct($local_model, $foreign_model_name, $field_value)
	{	// Construct query object
	    $foreign_model = call_user_func(array($foreign_model_name, 'model'));

	    // Save parameters
	    $this->rel_params['local_model'] = $local_model;
	    $this->rel_params['foreign_model'] = $foreign_model;
	    $this->rel_params['field_value'] = $field_value;
	    
		$this->query = DBRecord::open_query($foreign_model_name)
			->where($foreign_model->fk_field_for($local_model->name()) . ' = ?')
			->push_exec_param($field_value);
	}

	//! Get all records of this relationship
	public function all()
	{	return $this->query->execute();	}

	//! Perform a subquery on this relationship
	public function subquery()
	{	return $this->query;	}

	//! Get one only member with a specific primary key
	public function get($primary_key)
	{   $pks = $this->rel_params['foreign_model']->pk_fields();
	    $res = $this->subquery()->where("{$pks[0]} = ?")->execute($primary_key);
	    if (count($res) > 0)
	        return $res[0];
	    return NULL;
    }
}


//! Object handling collection from N-to-M relationship
/**
 * This object is constructed when requesting a relationship from a DBRecord.
 * Check DBRecord for more information on how to construct it.
 */
class DBRecordBridgeRelationship
{
    //! Relationship options
    private $rel_params;

    //! Query object
    private $query;
    
    //! Construct relationship
    public function __construct($local_model, $bridge_model_name, $foreign_model_name, $local_value)
    {   
        // Construct relationship array
        $bridge_model = call_user_func(array($bridge_model_name, 'model'));
        $foreign_model = call_user_func(array($foreign_model_name, 'model'));

        $rel = array();
		$rel['local_model_name'] = $local_model->name();
		$rel['bridge_model_name'] = $bridge_model_name;    		
		$rel['foreign_model_name'] = $foreign_model_name;
		    $pks = $local_model->pk_fields();
	    $rel['local2bridge_field'] = $pks[0];
	    $rel['bridge2local_field'] = $bridge_model->fk_field_for($local_model->name());
	    $rel['bridge2foreign_field'] = $bridge_model->fk_field_for($foreign_model_name);
	        $pks = $foreign_model->pk_fields();
	    $rel['foreign2bridge_field'] = $pks[0];
	    $rel['local_bridge_value'] = $local_value;
        
		// Construct joined query
		$this->query = DBRecord::open_query($rel['foreign_model_name'])
            ->left_join($rel['bridge_model_name'], $rel['foreign2bridge_field'], $rel['bridge2foreign_field'])
            ->where('? = l.' . $rel['bridge2local_field'])
            ->push_exec_param($rel['local_bridge_value']);

        // Save relationship
        $this->rel_params = $rel;
    }

    public function add($record)
    {   $keys = $record->key();
        $params = array(
            $this->rel_params['bridge2local_field'] => $this->rel_params['local_bridge_value'],
            $this->rel_params['bridge2foreign_field'] => $keys[0]
        );
        return DBRecord::create($params, $this->rel_params['bridge_model_name']);
    }

    public function remove($record)
    {   $keys = $record->key();
        $params = array(
            $this->rel_params['bridge2local_field'] => $this->rel_params['local_bridge_value'],
            $this->rel_params['bridge2foreign_field'] => $keys[0]
        );
        if (($bridge_record = DBRecord::open($params, $this->rel_params['bridge_model_name'])) === FALSE)
            return false;

        return $bridge_record->delete();
    }

	//! Get all records of this relationship
	public function all()
	{	return $this->query->execute();	}

    //! Perform a subquery on this relationship
	public function subquery()
	{	return $this->query;	}

}

class DBRecord
{
	//! Array with record constructors
	static $model_constr = array();

	//! Array with dynamic relationships
	static $dynamic_relationships = array();
	
	//! Initialize model based on the structure of derived class
	static private function init_model($model_name)
	{
		// Create model constructor
		if (!isset(self::$model_constr[$model_name]))
			self::$model_constr[$model_name] = create_function('$sql_data, $model', 
				'$records = array();
				$model_name = $model->name();
				foreach($sql_data as $key => $rec)
					$records[] =  new $model_name($model, $rec);
				return $records;');
		
		// Open model if it exists
		if (($md = DBModel::open($model_name)) !== NULL)
			return $md;

		$fields = get_static_var($model_name, 'fields');
		$table = get_static_var($model_name, 'table');
		$rels = (isset_static_var($model_name, 'relationships')
			?get_static_var($model_name, 'relationships')
			:array()
		);
		if (isset(self::$dynamic_relationships[$model_name]))
		    $rels = array_merge($rels, self::$dynamic_relationships[$model_name]);
					
		// Check if fields are defined
		if (!is_array($fields))
			throw new InvalidArgumentException('DBRecord::$fields is not defined in derived class');

		// Check if table is defined
		if (!is_string($table))
			throw new InvalidArgumentException('DBRecord::$table is not defined in derived class');
		
		return DBModel::create($model_name, $table, $fields, $rels);
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

	//! Declare 1-to-many relationship
	static public function one_to_many($many_model_name, $one_rel_name, $many_rel_name)
	{	$model_name = get_called_class();

	    self::$dynamic_relationships[$model_name][$many_rel_name] = 
	        array('type' => 'many', 'foreign_model' => $many_model_name);


	    self::$dynamic_relationships[$many_model_name][$one_rel_name] =
	        array('type' => 'one', 'foreign_model' => $model_name);
	}

	//! Declare 1-to-many relationship
	static public function many_to_many($foreign_model_name, $bridge_model_name, $foreign_rel_name, $local_rel_name)
	{	$model_name = get_called_class();

	    self::$dynamic_relationships[$model_name][$local_rel_name] = array(
	        'type' => 'bridge',
	        'foreign_model' => $foreign_model_name,
	        'bridge_model' => $bridge_model_name
	    );


	    self::$dynamic_relationships[$foreign_model_name][$foreign_rel_name] = array(
	        'type' => 'bridge',
	        'foreign_model' => $model_name,
	        'bridge_model' => $bridge_model_name
	    );

	    var_dump(self::$dynamic_relationships);
	}
	
	//! Open the dbrecord based on its primary key
	/**
	 * 
	 * It will query database table for a record with the supplied primary key. It will
	 * read the data and return an DBRecord object for this record.
	 * 
	 * @param $primary_keys It can be a string or associative array
	 * 	- @b string The value of PK column if the PK is single-column.
	 *  - @b array The values of all PK columns if the PK is multi-column.
	 *  .
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
	{	//benchmark::checkpoint('pre-get_called');
		if ($model_name === NULL)
			$model_name = get_called_class();

		// Initialize model
		$model = & self::init_model($model_name);
		
		// Check parameters
		$pk_fields = $model->pk_fields(false);

		// 1 value to array
		if (!is_array($primary_keys))
			$primary_keys = array($pk_fields[0] => $primary_keys);
				
		// Check for given quantity
		if (count($pk_fields) != count($primary_keys))
			return false;

		// Execute query and check return value
		$q = self::open_query($model_name);
		$select_args = array();
		foreach($pk_fields as $pk_name)
		{	$q->where('? = p.' .$pk_name);
			$select_args[] = $primary_keys[$pk_name];
		}

		// Check return value
		if (count($res = call_user_func_array(array($q, 'execute'), $select_args)) !== 1)
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
	
	//! Count records of model
	static public function count($model_name = NULL)
	{	if ($model_name === NULL)
			$model_name = get_called_class();

		// Initialize model
		$model = & self::init_model($model_name);
		
		// Execute query and check return value
		$res = self::raw_query($model_name)
			->select(array('count(*)'))
			->execute();
		
		// Return results from database
		return $res[0][0];
	}	
	
	//! Create a new record in database of this model
	static public function create($args, $model_name = NULL)
	{	if ($model_name === NULL)
			$model_name = get_called_class();

	    // Initialize model
		$model = & self::init_model($model_name);

		// Prepare values
		$insert_args = array();
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
		foreach($model->pk_fields() as $pk_name)
			$pk_values[$pk_name] = $values[$pk_name];
		return DBRecord::open($pk_values, $model_name);
	}
	
	//! Data values of this instance
	protected $fields_data = array();
	
	//! Cache used for cachings casts
	protected $data_cast_cache = array();
	
	//! Track dirty fields for delta updates
	protected $dirty_fields = array();
	
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
	
	//! Save changes in database
	public function save()
	{	
		if(count($this->dirty_fields) === 0)
			return true;	// No changes
			
		// Create update query
		$update_args = array();
		$q = self::raw_query($this->model->name())
			->update()
			->limit(1);
			
		// Add delta fields
		foreach($this->dirty_fields as $field_name => $flag)
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
		$delete_args = array();
		$q = self::raw_query($this->model->name())
			->delete()
			->limit(1);
		
		// Add Where clause based on primary keys
		foreach($this->key(true) as $pk => $value)
		{	$q->where("{$pk} = ?");
			$delete_args[] = $value;
		}
		
		// Execute query
		return call_user_func_array(array($q, 'execute'), $delete_args);
	}

	//! Get the key of this record
	public function key($assoc = false)
	{	$values = array();

		if ($assoc)
			foreach($this->model->pk_fields() as $pk)
				$values[$pk] = $this->fields_data[$pk];
		else
			foreach($this->model->pk_fields() as $pk)
				$values[] = $this->fields_data[$pk];
		return $values;
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
	{	//benchmark::checkpoint('__get - start', $name);
		if ($this->model->has_field($name))
		{	// Check for data
			return $this->model->user_field_data(
				$name,
				$this->fields_data[$name]
			);
		}
		
		if ($this->model->has_relationship($name))
		{	$rel = $this->model->relationship_info($name);
			
			if ($rel['type'] === 'one')
			{
				return DBRecord::open(
					$this->__get($this->model->fk_field_for($rel['foreign_model'])),
					$rel['foreign_model']
				);
			}
			if ($rel['type'] === 'many')
			{	$pks = $this->key();
				return new DBRecordManyRelationship(
			        $this->model,
					$rel['foreign_model'],
					$pks[0]);
			}

			if ($rel['type'] === 'bridge')
			{   $pks = $this->key();
			    return new DBRecordBridgeRelationship(
			        $this->model,
			        $rel['bridge_model'],
			        $rel['foreign_model'],
			        $pks[0]
			    );
			}
			
			throw new RuntimeException("Unknown DBRecord relation type '{$rel['type']}'");
		}
		
		// Oops!
		$trace = debug_backtrace();
		throw new InvalidArgumentException("{$this->model->name()}(DBRecord)->{$name}" . 
			" is not valid field of model {$this->model->name()}, requested at {$trace[0]['file']} ".
			" on line {$trace[0]['line']}");
	}
	
	//! Set the value of a field
	public function __set($name, $value)
	{
		if ($this->model->has_field($name))
		{
			// Mark it as dirty
			$this->dirty_fields[$name] = true;
			
			// Set data
			return $this->fields_data[$name] = 
				$this->model->db_field_data(
					$name,
					$value
				);
		}
		
		if ($this->model->has_relationship($name))
		{	$rel = $this->model->relationship_info($name);
			
			if ($rel['type'] == 'one')
			{	if (is_object($value))
				{	$fm = DBModel::open($rel['foreign_model']);
					$pks = $fm->pk_fields();
					$this->__set(
					    $this->model->fk_field_for($rel['foreign_model']),
					    $value->__get($pks[0]));
				}
				else
					$this->__set(
					    $this->model->fk_field_for($rel['foreign_model']),
					    $value
					);

				return $value;
			}
			
			if ($rel['type'] == 'many')
				return false;
			
			throw new RuntimeException("Unknown DBRecord relation type '{$rel['type']}'");
		}
		
		// Oops!
	    $trace = debug_backtrace();
		throw new InvalidArgumentException("{$this->model->name()}(DBRecord)->{$name}" . 
			" is not valid field of model {$this->model->name()}, requested at {$trace[0]['file']} ".
			" on line {$trace[0]['line']}");
	}
	
	//! Serialization implementation
	public function __sleep()
	{	return array('fields_data', 'dirty_fields');
	}
	
	//! Unserilization implementation
	public function __wakeup()
	{	// Initialize static
		$this->model = self::init_model(get_class($this));
	}
}
?>
