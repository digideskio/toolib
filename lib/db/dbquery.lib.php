<?php 
require_once(dirname(__FILE__) . '/./dbmodel.lib.php');
require_once(dirname(__FILE__) . '/../functions.lib.php');

//! Execute SQL queries on models
/**
 * This is an sql-like interface to query on models.
 * You can insert,update,select,delete with any user-defined option
 * but only on the same model.
 * @author sque
 *
 */
class DBModelQuery
{
	//! Query type
	protected $query_type = NULL;
	
	//! Pointer to model
	protected $model = NULL;
	
	//! SELECT retrieve fields
	protected $select_fields = NULL;
	
	//! UPDATE set fields
	protected $set_fields = array();
	
	//! INSERT fields
	protected $insert_fields = array();
	
	//! All the insert values
	protected $insert_values = array();
	
	//! Limit of affected records
	protected $limit = NULL;
	
	//! Order of output data (on select only)
	protected $order_by = NULL;
	
	//! WHERE conditions
	protected $conditions = array();
	
	//! Hash populated by the user instructions
	protected $sql_hash = NULL;
	
	//! The final sql string
	protected $sql_export = NULL;
	
	//! Data wrapper callback
	protected $data_wrapper_callback = NULL;
	
	//! Use DBRecord::query() factory to create DBModelQuery objects
	/**
	 * @see DBRecord::query() on how to create objects of this class.
	 * @param $model Pass model object
	 * @param $data_wrapper_callback A callback to wrap data after execution
	 */
	final public function __construct($model, $data_wrapper_callback = NULL)
	{	
		// Save pointer of the model
		$this->model = & $model;
		$this->data_wrapper_callback = $data_wrapper_callback;
		$this->reset();
	}
	
	//! Reset query so that it can be used again
	public function & reset()
	{	// Reset all values to default
		$this->query_type = NULL;
		$this->select_fields = array();
		$this->set_fields = array();
		$this->insert_fields = array();
		$this->insert_values = array();
		$this->limit = NULL;
		$this->order_by = NULL;
		$this->conditions = array();
		$this->sql_hash = 'HASH:' . $this->model->table() .':';
		$this->sql_export = NULL;

		return $this; 
	}
	
	//! Check if statement is alterablee
	/**
	 * Alterable means that there can be more options on the query. 
	 * @return @b TRUE if query is alterable, @b FALSE if the query is closed for changes. 
	 */
	public function is_alterable()
	{	return ($this->sql_export === NULL);	}
	
	//! Check if it i alterable otherwise throw exception
	private function assure_alterable()
	{	if (!$this->is_alterable())
			throw new RuntimeException('This DBModelQuery instance is no longer alterable!');
	}
	
	//! Start a deletion on model
	public function & delete()
	{	$this->assure_alterable();
		$this->query_type = 'delete';
		$this->sql_hash .= ':delete:';
		return $this; 
	}
	
	//! Start an update on model
	public function & update()
	{	$this->assure_alterable();
		$this->query_type = 'update';
		$this->sql_hash .= ':update:';
		return $this; 
	}
	
	//! Start a selection query on model
	public function & select($fields)
	{	$this->assure_alterable();
		$this->query_type = 'select';
		$this->select_fields = $fields;
		$this->sql_hash .= ':select:' . implode(':', $fields);
		return $this;
	}
	
	//Start an insertation query on model
	public function & insert($fields)
	{	$this->assure_alterable();
		$this->query_type = 'insert';
		$this->insert_fields = $fields;
		$this->sql_hash .= ':insert:' . implode(':', $fields);
		return $this;
	}
	
	//! Define values of insert command
	public function & values()
	{	$this->assure_alterable();
		$args = func_get_args();
		if (count($args) != count($this->insert_fields))
			throw new InvalidArgumentException('The quantity of values must exactly ' .
				'the same with the fields defined with insert()');
		$this->insert_values[] = $args;
		$this->sql_hash .= ':' . implode(':', $args);
		return $this;
	}
	
	//! Set a field value
	public function & set($field, $value = NULL)
	{	$this->assure_alterable();
		$this->set_fields[] = array(
			'field' => $field,
			'value' => $value
		);
		$this->sql_hash .= ':set:' . $field . ':' . $value;
		return $this;
	}

	//! Where is the expression
	public function & where($exp, $bool_op = 'AND')
	{	$this->assure_alterable();
		$this->conditions[] = array(
			'expression' => $exp,
			'bool_op' => $bool_op,
			'op' => NULL,
			'lvalue' => NULL,
			'ltype' => NULL,
			'rvalue' => NULL,
			'rtype' => NULL,
		);
		$this->sql_hash .= ':where:' . $bool_op . ':' . $exp;
		return $this;
	}
	
	//! Limit the query
	public function & limit($length, $offset = NULL)
	{	$this->assure_alterable();
		$this->limit = array('length' => $length, 'offset' => $offset);
		$this->sql_hash .= ':limit:' . $length . ':' . $offset;
		return $this;
	}
	
	//! Select order by
	public function & order_by($field, $order = 'ASC')
	{	$this->assure_alterable();
		$this->order_by = array('field' => $field, 'order' => $order);
		$this->sql_hash .= ':order:' . $field . ':' . $order;
		return $this;
	}
	
	//! Generate SELECT query
	private function generate_select_query()
	{	$query = 'SELECT';
		foreach($this->select_fields as $field)
			$fields[] = "`" . $this->model->field_info($field, 'sqlfield') . "`";

		$query .= ' ' . implode(', ', $fields);
		$query  .= ' FROM ' . $this->model->table();
		if (count($this->conditions) > 0)
		{	$query .= ' WHERE';
			$first = true;
			foreach($this->conditions as & $cond)
			{	$matched = 
					preg_match_all('/^[\s]*([\w\d]+|\?|\'[^\']+\')[\s]*([=<>]+|like|between|in)[\s]*([\w\d]+|\?|\'[^\']+\')[\s]*$/',
					$cond['expression'], $matches);
				
				if ($matched != 1)
					throw new InvalidArgumentException("Invalid WHERE expression '{$cond['expression']}' was given.");
				
				$cond['op'] = $matches[2][0];
				$cond['lvalue'] = $matches[1][0];
				$cond['rvalue'] = $matches[3][0];
				
				if ($first)
					$first = false;
				else
					$query .= ' ' . $cond['bool_op'];
				$query .= " {$cond['lvalue']} {$cond['op']} {$cond['rvalue']}";
					
			}
			unset($cond);
		}
		
		// Order by
		if ($this->order_by !== NULL)
			$query .= ' ORDER BY ' . $this->model->field_info($this->order_by['field'], 'sqlfield') .
				' ' . $this->order_by['order'];

		// Limit
		if ($this->limit !== NULL)
		{	if ($this->limit['offset'] !== NULL)
				$query .= " LIMIT {$this->limit['offset']},{$this->limit['length']}";
			else
				$query .= " LIMIT {$this->limit['length']}";
		}
		return $query;
	}
	
	//! Generate UPDATE query
	private function generate_update_query()
	{	$query = 'UPDATE ' . $this->model->table() . ' SET';
	
		if (count($this->set_fields) === 0)
			throw new InvalidArgumentException("Cannot execute update() command without using set()");
			
		foreach($this->set_fields as $params)
		{
			$set_query = "`" . $this->model->field_info($params['field'], 'sqlfield') . "` = ";
			if ($params['value'] === NULL)
				$set_query .= '?';
			else
				$set_query .= "'" . dbconn::escape_string($params['value']) . "'"; 
			$fields[] = $set_query;
		}
		$query .= ' ' . implode(', ', $fields);
		if (count($this->conditions) > 0)
		{	$query .= ' WHERE';
			$first = true;
			foreach($this->conditions as & $cond)
			{	$matched = 
					preg_match_all('/^[\s]*([\w\d]+|\?|\'[^\']+\')[\s]*([=<>]+|like|between|in)[\s]*([\w\d]+|\?|\'[^\']+\')[\s]*$/',
					$cond['expression'], $matches);
				
				if ($matched != 1)
					throw new InvalidArgumentException("Invalid WHERE expression '{$cond['expression']}' was given.");
				
				$cond['op'] = $matches[2][0];
				$cond['lvalue'] = $matches[1][0];
				$cond['rvalue'] = $matches[3][0];
				
				if ($first)
					$first = false;
				else
					$query .= ' ' . $cond['bool_op'];
				$query .= " {$cond['lvalue']} {$cond['op']} {$cond['rvalue']}";
					
			}
			unset($cond);
		}
		
		// Order by
		if ($this->order_by !== NULL)
			$query .= ' ORDER BY ' . $this->model->field_info($this->order_by['field'], 'sqlfield') .
				' ' . $this->order_by['order'];
		// Limit
		if ($this->limit !== NULL)
			$query .= " LIMIT {$this->limit['length']}";
		return $query;
	}
	
	//! Generate INSERT query
	private function generate_insert_query()
	{	$query = 'INSERT INTO ' . $this->model->table();
	
		if (count($this->insert_fields) === 0)
			throw new InvalidArgumentException("Cannot execute insert() with no fields!");
			
		foreach($this->insert_fields as $field)
			$fields[] = "`" . $this->model->field_info($field, 'sqlfield') . "`";

		$query .= ' (' . implode(', ', $fields) . ') VALUES';
		if (count($this->insert_values) === 0)
			throw new InvalidArgumentException("Cannot insert() with no values, use values() to define them.");

		foreach($this->insert_values as $values_series)
		{	$values = array();
			foreach($values_series as $value)
				if ($value === NULL)
					$values[] = '?';
				else
					$values[] = "'" . dbconn::escape_string($value) . "'";
			$query .= ' (' . implode(', ', $values) . ')'; 
		}
		return $query;
	}
	
	//! Generate DELETE query
	private function generate_delete_query()
	{	$query = 'DELETE FROM ' . $this->model->table();
	
		if (count($this->conditions) > 0)
		{	$query .= ' WHERE';
			$first = true;
			foreach($this->conditions as & $cond)
			{	$matched = 
					preg_match_all('/^[\s]*([\w\d]+|\?|\'[^\']+\')[\s]*([=<>]+|like|between|in)[\s]*([\w\d]+|\?|\'[^\']+\')[\s]*$/',
					$cond['expression'], $matches);
				
				if ($matched != 1)
					throw new InvalidArgumentException("Invalid WHERE expression '{$cond['expression']}' was given.");
				
				$cond['op'] = $matches[2][0];
				$cond['lvalue'] = $matches[1][0];
				$cond['rvalue'] = $matches[3][0];
				
				if ($first)
					$first = false;
				else
					$query .= ' ' . $cond['bool_op'];
				$query .= " {$cond['lvalue']} {$cond['op']} {$cond['rvalue']}";
					
			}
			unset($cond);
		}
		
		// Order by
		if ($this->order_by !== NULL)
			$query .= ' ORDER BY ' . 
				$this->model->field_info($this->order_by['fielld'], 'sqlfield') .
				' ' . $this->order_by['order'];
		
		// Limit
		if ($this->limit !== NULL)
			$query .= " LIMIT {$this->limit['length']}";
		return $query;
	}
	
	//! Create the sql command for this query
	/**
	 * Executing sql() will make query non-alterable and fixed,
	 * however you can use execute() multiple times.
	 * @return The string with SQL command.
	 */
	public function sql()
	{	// Check if sql has been already crafted
		if ($this->sql_export !== NULL)
			return $this->sql_export;
		
		// Check cache
		$query = $this->model->fetch_cache($this->sql_hash, $succ);
		if ($succ)
		{	$this->sql_export = $query;
			return $this->sql_export;
		}
		
		if ($this->query_type === 'select')
			$this->sql_export = $this->generate_select_query();
		else if ($this->query_type === 'update')
			$this->sql_export = $this->generate_update_query();
		else if ($this->query_type === 'delete')
			$this->sql_export = $this->generate_delete_query();
		else if ($this->query_type === 'insert')
			$this->sql_export = $this->generate_insert_query();
		else
			throw new RuntimeException('Query is not finished to be exported.' .
				' You have to use at least one of the main commands insert()/update()/delete()/select(). ');

		// Save in cache
		$this->model->push_cache($this->sql_hash, $this->sql_export);
		
		return $this->sql_export;
	}
	
	//! Force preparation of statement
	/**
	 * Prepare this statement if it is not yet. Otherwise don't do nothing.
	 * @note Statements are prepared automatically at execution time.
	 * @return NULL
	 */
	public function prepare()
	{	if (!dbconn::is_key_used($this->sql_hash))
			return dbconn::prepare($this->sql_hash, $this->sql());
	}
	
	//! Execute statement and return the results
	public function execute()
	{	$this->prepare();	
		$args = func_get_args();
		$args = array_merge(array($this->sql_hash), $args);
		$data = call_user_func_array(array('dbconn','execute_fetch_all'), $args);
		
		if ($this->data_wrapper_callback)
			$data = call_user_func($this->data_wrapper_callback, $data);
		return $data;
	}
}

?>