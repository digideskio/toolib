<?php
/*
 *  This file is part of PHPLibs <http://phplibs.kmfa.net/>.
 *  
 *  Copyright (c) 2010 < squarious at gmail dot com > .
 *  
 *  PHPLibs is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *  
 *  PHPLibs is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License
 *  along with PHPLibs.  If not, see <http://www.gnu.org/licenses/>.
 *  
 */

namespace toolib\DB;

require_once __DIR__ . '/Connection.class.php';
require_once __DIR__ . '/Model.class.php';
require_once __DIR__ . '/ModelQueryCache.class.php';

/**
 * @brief Execute SQL queries on models.
 * 
 * This is an sql-like interface to query on models.
 * You can insert, update, select, delete with any user-defined option.
 */
class ModelQuery
{
	/**
	 * @brief Type of this query.
	 * @see getType()
	 * @var string
	 */
	protected $query_type = NULL;
	
	/**
	 * @brief Pointer to model
	 * @var \toolib\DB\Model
	 */
	protected $model = NULL;
	
	/**
	 * @brief SELECT retrieve fields
	 * @var array
	 */
	protected $select_fields = array();
	
	/**
	 * @brief UPDATE set fields
	 * @var string
	 */
	protected $set_fields = array();
	
	/**
	 * @brief INSERT fields
	 * @var array
	 */
	protected $insert_fields = array();
	
	/**
	 * @brief All the insert values
	 * @var array
	 */
	protected $insert_values = array();
	
	/**
	 * @brief Limit of affected records
	 * @var array
	 */
	protected $limit = NULL;
	
	/**
	 * @brief Order of affected records
	 * @var array
	 */
	protected $order_by = array();

	/**
	 * @brief Group rules for retrieving data
	 * @var array
	 */
	protected $group_by = array();

    /**
     * @brief Left join table
     * @var array
     */
    protected $ljoin = NULL;
	
	/**
	 * @brief WHERE conditions
	 * @var array
	 */
	protected $conditions = array();
	
	/**
	 * @brief Hash populated by the user instructions
	 * @var string
	 */
	protected $sql_hash = NULL;
	
	/**
	 * @brief The final sql string
	 * @var string
	 */
	protected $sql_query = NULL;
	
	/**
	 * @brief Data wrapper callback
	 * @var callable
	 */
	protected $data_wrapper_callback = NULL;
	
	/**
	 * @brief Query cache hints
	 * @var array
	 */
	protected $cache_hints = NULL;
	
 	/**
	 * @brief Engine for caching queries
	 * @var \toolib\DB\ModelQueryCache
	 */
	protected $query_cache;
	
	/**
	 * @brief Query execution parameters 
	 * @var array
	 */
	protected $exec_params = array();
	
	/**
	 * @brief Use \toolib\DB\Record::openQuery() factory to create ModelQuery objects
	 * @param \toolib\DB\Model $model Pass model object
	 * @param callbable $data_wrapper_callback A callback to wrap data after execution
	 */
	final public function __construct(Model $model, $data_wrapper_callback = NULL)
	{	
		// Save pointer of the model
		$this->model = & $model;
		$this->data_wrapper_callback = $data_wrapper_callback;
		$this->query_cache = ModelQueryCache::open($model);
		$this->reset();		
	}
	
	/**
	 * @brief Reset query so that it can be used again
	 * @return \toolib\DB\ModelQuery
	 */
	public function & reset()
	{	
	    // Reset all values to default
		$this->query_type = NULL;
		$this->select_fields = array();
		$this->set_fields = array();
		$this->insert_fields = array();
		$this->insert_values = array();
		$this->limit = NULL;
		$this->order_by = array();
		$this->ljoin = NULL;
		$this->conditions = array();
		$this->sql_hash = 'HASH:' . $this->model->getTable() .':';
		$this->sql_query = NULL;
		$this->cache_hints = NULL;

		return $this; 
	}
	
	/**
	 * @brief Alterable means that there can be more options on the query. 
	 * @return
	 *  - @b true if query is alterable
	 *  - @b false if the query is closed for changes. 
	 */
	public function isAlterable()
	{
	    return ($this->sql_query === NULL);
    }
	
	/**
	 * @brief Check if it i alterable otherwise throw exception
	 */
	private function assureAlterable()
	{
	    if (!$this->isAlterable())
			throw new \RuntimeException('This ModelQuery instance is no longer alterable!');
	}
	
	/**
	 * @brief Start a deletion on model
	 * @return \toolib\DB\ModelQuery
	 */
	public function & delete()
	{	
	    $this->assureAlterable();
	
		// Check if there is already a type command
		if ($this->query_type !== NULL)
			throw new \RuntimeException('This ModelQuery instance has already defined its type "' . $this->query_type . '"!');

		$this->query_type = 'delete';
		$this->sql_hash .= ':delete:';
		return $this; 
	}
	
	/**
	 * @brief Start an update on model
	 * @return \toolib\DB\ModelQuery
	 */
	public function & update()
	{	
	    $this->assureAlterable();
	
		// Check if there is already a type command
		if ($this->query_type !== NULL)
			throw new \RuntimeException('This ModelQuery instance has already defined its type "' . $this->query_type . '"!');

		$this->query_type = 'update';
		$this->sql_hash .= ':update:';
		return $this; 
	}
	
	/**
	 * @brief Start a selection query on model
	 * @param array $fields Field names that you want to fetch values from.
	 * @return \toolib\DB\ModelQuery
	 */
	public function & select($fields)
	{	
	    $this->assureAlterable();
	
		// Check if there is already a type command
		if ($this->query_type !== NULL)
			throw new \RuntimeException('This ModelQuery instance has already defined its type "' . $this->query_type . '"!');
		
		$this->query_type = 'select';
		$this->select_fields = $fields;
		$this->sql_hash .= ':select:' . implode(':', $fields);
		return $this;
	}
	
	/**
	 * @brief Start an insertation query on model
	 * @param Array $fields Field names that you will provide values for.
	 * @return \toolib\DB\ModelQuery
	 */
	public function & insert($fields)
	{	
	    $this->assureAlterable();
	
		// Check if there is already a type command
		if ($this->query_type !== NULL)
			throw new \RuntimeException('This ModelQuery instance has already defined its type "' . $this->query_type . '"!');
		
		$this->query_type = 'insert';
		$this->insert_fields = $fields;
		$this->sql_hash .= ':insert:' . implode(':', $fields);
		return $this;
	}
	
	/**
	 * @brief Define values of insert command as an array
	 * @param array $values_array Values for adding one record. The values must be
	 *  in the same order as the fields where declared in insert().
	 * @return \toolib\DB\ModelQuery
	 */
	public function & valuesArray($values)
	{	
	    $this->assureAlterable();

	    // Check if there is already a type command
		if ($this->query_type !== 'insert')
			throw new \RuntimeException('You cannot add values in a non-insert query!');
			
		if (count($values) != count($this->insert_fields))
			throw new \InvalidArgumentException('The quantity of values, must be exactly ' .
				'the same with the fields defined with insert()');
				
		$this->insert_values[] = $values;
        $this->pushExecParams($values);
        
		$this->sql_hash .= ':v' . count($values);
		return $this;
	}
	
	/**
	 * @brief Define values of insert command as arguments.
	 * 
	 * Same as valuesArray(), only this one you pass the values as function arguments
	 * @return \toolib\DB\ModelQuery
	 */
	public function & values()
	{	
	    $args = func_get_args();
		return $this->valuesArray($args);
	}
	
	/**
	 * @brief Set a field value
	 * @param string $field The field to set its value to a new one
	 * @param mixed $value [Default = false] Optional literal value to push in dynamic parameters.
	 * @return \toolib\DB\ModelQuery
	 */
	public function & set($field, $value = false)
	{	
	    $this->assureAlterable();
		$this->set_fields[] = array(
			'field' => $field,
			'value' => $value
		);
		if ($value !== false)
		    $this->pushExecParam($value);
		$this->sql_hash .= ':set:' . $field;
		return $this;
	}

	/**
	 * @brief Add a general conditional expresion on query
	 * @param string $exp A single operand expression between fields and dynamic parameters (exclamation mark).
	 *  If you want to pass a literal value, use combination of dynamic (?) and pushExecParam().\n
     *  @b Examples:
     *  - @code 'title = ?' @endcode
     *  - @code '? = ?' @endcode
     *  - @code 'title LIKE ?' @endcode
     *  - @code 'title NOT LIKE ?' @endcode
     *  - @code 'title IS NULL' @endcode
     *  - @code 'title IS NOT FALSE' @endcode
     *  - @code 'title IS UNKNOWN' @endcode
     *  .
     * @param $bool_op [Default = "AND"]: <strong> [AND|OR|XOR] [NOT] </strong>
     *  - @b 'AND' Use boolean @b AND operator between this expression and the previous one.
     *  - @b 'OR' Use boolean @b NOT operator between this expression and the previous one.
     *  - @b 'XOR' Use boolean @b XOR operator between this expression and the previous one.
     *  .
     *  @b Examples:
     *  - @code 'AND' @endcode
     *  - @code 'NOT' @endcode
     *  - @code 'OR NOT' @endcode
     *  - @code 'XOR NOT' @endcode
     *  .
     * @return \toolib\DB\ModelQuery
     */
	public function & where($exp, $bool_op = 'AND')
	{	
	    $this->assureAlterable();
		$this->conditions[] = $cond = array(
			'expression' => $exp,
			'bool_op' => strtoupper($bool_op),
			'op' => NULL,
			'lvalue' => NULL,
			'rvalue' => NULL,
			'require_argument' => false,
		);

		$this->sql_hash .= ':where:' . $cond['bool_op'] . ':' . $exp;
		return $this;
	}
	
	/**
	 * @brief Add an "IN" conditional expression on query
	 * @param string $field_name The name of the field to be checked for beeing equal with an array entity.
     * @param array $values
     *  - @b integer The number of dynamic values
     *  - @b array An static values to pass on where clause.
     *  .
     * @param $bool_op [Default = "AND"]: <strong> [AND|OR|XOR] [NOT] </strong>
     *  - @b 'AND' Use boolean @b AND operator between this expression and the previous one.
     *  - @b 'OR' Use boolean @b NOT operator between this expression and the previous one.
     *  - @b 'XOR' Use boolean @b XOR operator between this expression and the previous one.
     *  .
     *  @b Examples:
     *  - @code 'AND' @endcode
     *  - @code 'NOT' @endcode
     *  - @code 'OR NOT' @endcode
     *  - @code 'XOR NOT' @endcode
     *  .
     * @return \toolib\DB\ModelQuery
     */
    public function & whereIn($field_name, $values, $bool_op = 'AND')
    {
	    $this->assureAlterable();
		$this->conditions[] = $cond = array(
			'bool_op' => strtoupper($bool_op),
			'op' => 'IN',
			'lvalue' => $field_name,
			'rvalue' => is_array($values)?count($values):$values,
			'require_argument' => false,
		);
		
		// Push execute parameters
		if (is_array($values))
		    $this->pushExecParams($values);

		$this->sql_hash .= ':where:' . $cond['bool_op'] . ':' . (is_array($values)?count($values):$values);
		return $this;
    }

	/**
	 * @brief Declare left join table (for extra criteria only).
	 * 
	 * After declaring left join you can use it in criteria by refering to it with "l" shortcut.
	 * Example l.title = ?
	 * @param string $model_name The left joined model.
	 * @param string $primary_field [Default: null] The local field of the join.
	 * @param string  $joined_field [Default: null] The foreing field of the join.
	 * @note If there is a declared relationship between this model and the left join, you can
	 *  ommit string  $primary_field and $joined_field as it can implicitly join on the declared reference key.
	 * @return \toolib\DB\ModelQuery
	 */
	public function & leftJoin($model_name, $primary_field = null, $joined_field = null)
	{   
	    $this->assureAlterable();

	    // Check if there is already a type command
		if ($this->query_type !== 'select')
			throw new \RuntimeException('You cannot declare left_join on ModelQuery that is not of SELECT type!');

		$this->ljoin = array(
		    'model_name' => $model_name,
		    'join_local_field' => $primary_field,
		    'join_foreign_field' => $joined_field
		);
		$this->sql_hash .= ':ljoin:' . $model_name . ':' . $primary_field . ':' . $joined_field;
	    return $this;
	}

	/**
	 * @brief Limit the records affected by this query
	 * @param number $length The number of records to be retrieved or affected
	 * @param number $offset The offset of records that query will start to retrive or affect.
	 * @return \toolib\DB\ModelQuery
	 */
	public function & limit($length, $offset = NULL)
	{	
	    $this->assureAlterable();
		$this->limit = array('length' => $length, 'offset' => $offset);
		$this->sql_hash .= ':limit:' . $length . ':' . $offset;
		return $this;
	}
	
	/**
	 * @brief Add an order by rule in query
	 * @param string $expression A field name, column reference or an expression to be evaluated for each row.
	 * @param string $direction The direction of ordering.
	 * @return \toolib\DB\ModelQuery
     */
	public function & orderBy($expression, $direction = 'ASC')
	{	
	    $this->assureAlterable();
		$this->order_by[] = array(
		    'expression' => $expression,
		    'direction' => $direction
        );
		$this->sql_hash .= ':order:' . $expression . ':' . $direction;
		return $this;
	}

	/**
	 * @brief Add a group by by rule in query
	 * @param string $expression A field name, column reference or an expression to be evaluated for each row.
	 * @param string $direction The direction of ordering prior grouping.
	 * @return \toolib\DB\ModelQuery
     */
	public function & groupBy($expression, $direction = 'ASC')
	{	
	    $this->assureAlterable();
		$this->group_by[] = array(
		    'expression' => $expression,
		    'direction' => $direction
        );
		$this->sql_hash .= ':group:' . $expression . ':' . $direction;
		return $this;
	}


	/**
	 * @brief Set the callback wrapper function
	 * @param callable $callback
	 * @return \toolib\DB\ModelQuery
	 */
	public function & setDataWrapper($callback)
	{   
	    $this->assureAlterable();
	    $this->data_wrapper_callback = $callback;
	    return $this;
	}
	
	/**
	 * @brief Push an execute parameter 
	 * @param mixed $value
	 * @return \toolib\DB\ModelQuery
	 */
	public function & pushExecParam($value)
	{	    
	    $this->exec_params[] = $value;
		return $this;
	}
	
	/**
	 * @brief Push an array of execute parameters
	 * @param array $values
	 * @return \toolib\DB\ModelQuery
	 */
	public function & pushExecParams($values)
	{
	    foreach($values as $v)
    	    $this->exec_params[] = $v;
	    return $this;
	}
	
	/**
	 * @brief Get the type of query
	 */
	public function getType()
	{   
	    return $this->query_type;
	} 
	
	/**
	 * @brief Get query hash
	 */
	public function hash()
	{
	    return $this->sql_hash;
    }

    /**
     * @brief Analyze an already parsed column reference.
     * @param string $table_shorthand The table shorthand of the column ("p" or "l").
     * @param string $column The column friendly name as parsed.
     */
    private function analyzeColumnReference($table_shorthand, $column)
    {
    	$result = array(
			'table_short' => (empty($table_shorthand)?'p':$table_shorthand),
			'column' => $column,
			'column_sqlfield' => null,
			'query' => ''
		);
	         
		if ($result['table_short'] === 'p') {
			$result['column_sqlfield'] = $this->model->getFieldInfo($column, 'sqlfield');
		} else if ($result['table_short'] === 'l') {
			if ($this->ljoin === NULL)
				throw new \InvalidArgumentException("You cannot use \"l\" shorthand in EXPRESION when there is no LEFT JOIN!");
			$result['column_sqlfield'] = $this->ljoin['model']->getFieldInfo($column, 'sqlfield');
		}

		if ($result['column_sqlfield'] === NULL)
			throw new \InvalidArgumentException(
				"There is no field with name \"{$column}\" in model \"{$this->model->getName()}\"");
	         
		// Construct valid sql query
		$result['query'] = (($this->ljoin !== NULL)?$result['table_short'] . '.':'') . '`' . $result['column_sqlfield'] . '`';
		return $result;
    }
	
	/**
	 * @brief Analyze single expresison side value
	 */
	private function analyzeExpSideValue(& $cond, $side, $string)
	{
	    $matched = preg_match_all(
	        '/^[\s]*' . // Pre-field space
	        '(' .
	            '(?P<wildcard>\?)' .                        // prepared statement wildcard
	            '|((?P<table>p|l)\.)?(?P<column>[\w\-]+)' .  // column reference
	        ')' . 
	        '[\s]*/', // Post-field space
	        $string,
	        $matches
	    );

	    if ($matched != 1)
		    throw new \InvalidArgumentException("Invalid EXPRESSION '{$cond['expression']}' was given.");

	    if ($matches['wildcard'][0] === '?') {   
	        $cond['require_argument'] = true;
	        $cond[$side] = '?';
	    } else {
			$anl = $this->analyzeColumnReference($matches['table'][0], $matches['column'][0]);
			$cond[$side] = $anl['query'];
	    }
	}
	
	/**
	 * @brief Analyze single expression of the form l-Value OP r-Value
	 * @param unknown_type $cond
	 * @param unknown_type $expression
	 * @throws \InvalidArgumentException
	 */
	private function analyzeSingleExpression(& $cond, $expression)
	{
        $matched = 
		    preg_match_all('/^[\s]*(?<lvalue>([\w\.\?])+)[\s]*' .
		        '(?P<not_pre_op>not\s)?[\s]*' .
			    '(?P<op>[=<>]+|like|is)[\s]*' .
		        '(?P<not_post_op>\snot)?[\s]*' .
			    '(?P<rvalue>([\w\.\?])+)[\s]*$/i',
			    $expression, $matches);

	    if ($matched != 1)
		    throw new \InvalidArgumentException("Invalid EXPRESSION '{$expression}' was given.");

        // Operator
	    $cond['op'] = strtoupper($matches['not_pre_op']['0']) . 
	        strtoupper($matches['op'][0]) . strtoupper($matches['not_post_op'][0]);
        $cond['require_argument'] = false;
        
        // Check operator
        if (! in_array($cond['op'], array('=', '>', '>=', '<', '<=', '<>', 'LIKE', 'NOT LIKE', 'IS', 'IS NOT')))
            throw new \InvalidArgumentException("Invalid EXPRESSION operand '{$cond['op']}' was given.");
		
        // L-value
        $this->analyzeExpSideValue($cond, 'lvalue', $matches['lvalue'][0]);
        
        // R-value
        if (substr($cond['op'], 0, 2) == 'IS') {
            $cond['rvalue'] = strtoupper($matches['rvalue'][0]);
            if (! in_array($cond['rvalue'], array('NULL', 'TRUE', 'FALSE', 'UNKNOWN')))
                throw new \InvalidArgumentException("Invalid IS r-value '{$cond['rvalue']}' was given.");
        } else {
            $this->analyzeExpSideValue($cond, 'rvalue', $matches['rvalue'][0]);
        }
        
        // Generated condition
        $cond['query'] = "{$cond['lvalue']} {$cond['op']} {$cond['rvalue']}";
	}
	
	/**
	 * @brief Analyze WHERE conditions and return query
	 * @return \toolib\DB\ModelQuery
	 */
	private function generateWhereConditions()
	{	
	    $query = '';
		if (count($this->conditions) > 0) {
		    $query = ' WHERE';
			$first = true;
			foreach($this->conditions as & $cond) {
			    // Check boolean operation
			    $matched = 
		            preg_match_all('/^[\s]*(?<op>\bAND|OR|XOR\b)?[\s]*(?<not>\bNOT\b)?[\s]*$/',
	                $cond['bool_op'], $matches);
	            if ($matched != 1)
			        throw new \InvalidArgumentException("The boolean operator \"{$cond['bool_op']}\" is invalid");
                $cond['bool_op'] = array('op' => (empty($matches['op'][0])?'AND':$matches['op'][0]));
                $cond['bool_op']['not'] = ($matches['not'][0] == 'NOT');
                
			    if ($cond['op'] === null)
			        $this->analyzeSingleExpression($cond, $cond['expression']);
                else if($cond['op'] === 'IN') {
                    // L-value
                    $this->analyzeExpSideValue($cond, 'lvalue', $cond['lvalue']);

                    $array_size = (integer) $cond['rvalue'];
                    $cond['rvalue'] = '(' . implode(', ', array_fill(0, $array_size, '?')) . ')';
                    $cond['query'] = "{$cond['lvalue']} {$cond['op']} {$cond['rvalue']}";
                }
                
				if ($first)
					$first = false;
				else
					$query .= ' ' . $cond['bool_op']['op'];
				$query .= ($cond['bool_op']['not']?' NOT':'') . ' ' . $cond['query'];
			}
			unset($cond);
		}
		return $query;
	}
	
	/**
	 * @brief Generate LIMIT clause
	 */
	private function generateLimit()
	{   
	    // No limit
	    if ($this->limit === NULL)
	        return '';
		
		// Limit
        if (($this->limit['offset'] !== NULL) && ($this->query_type === 'select'))
			return " LIMIT {$this->limit['offset']},{$this->limit['length']}";

        return " LIMIT {$this->limit['length']}";
    }
    
    /**
     * @brief Analyze * BY clause
     * @param unknown_type $by_rules
     */
    private function analyzeByRules($by_rules)
    {
        if (empty($by_rules))
            return '';

	    $gen_rules = array();
        foreach($by_rules as $rule)
        {
            // Check direction string
            $rule['direction'] = (strtoupper($rule['direction']) === 'ASC'?'ASC':'DESC');
        
            // Check for field name and column name
            $matched = preg_match_all(
	            '/^[\s]*' . // Pre space
	            '(' .
	                '(?P<wildcard>\?)' .                        // prepared statement wildcard
	                '|(?P<num_ref>[\d]+)' .                     // numeric column reference
	                '|((?P<table>p|l)\.)?(?P<column>[\w\-]+)' .  // named column reference,
	            ')' . 
	            '[\s]*$/', // Post space
	        $rule['expression'],
	        $matches);


	        if ($matched != 1) {
	            // Not found lets try single expression analysis
	            $exp_params = array();
	            $this->analyzeSingleExpression($exp_params, $rule['expression']);
	            $gen_rules[] = $exp_params['query'] . ' ' . $rule['direction'];
	            continue;
	        }
    
	        if ($matches['wildcard'][0] === '?') {   
	            $cond['require_argument'] = true;
	            $cond[$side] = '?';
	        } else if ($matches['num_ref'][0] !== '') {
	            $col_ref = $matches['num_ref'][0];
	            $total_cols = count($this->select_fields);
	            if (($col_ref > $total_cols) or ($col_ref < 1))
	                throw new \InvalidArgumentException("The column numerical reference \"$col_ref\" " .
	                    "exceeded the boundries of retrieved fields");
	                    
                $gen_rules[] = (string)$col_ref . ' ' . $rule['direction'];
	        } else {
                $anl = $this->analyzeColumnReference($matches['table'][0], $matches['column'][0]);
                $gen_rules[] = $anl['query'] . ' ' . $rule['direction'];
	        }
        }
        
        return implode(', ', $gen_rules);
    }
    
    /**
     * @brief Generate ORDER BY clause
     */
    private function generateOrderBy()
    {
        $rules = $this->analyzeByRules($this->order_by);
        if ($rules == '')
            return '';
        return ' ORDER BY ' . $rules;
    }
    
    /**
     * @brief Generate GROUP BY
     */
    private function generateGroupBy()
    {
        $rules = $this->analyzeByRules($this->group_by);
        if ($rules == '')
            return '';
        return ' GROUP BY ' . $rules;
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function generateLeftJoin()
    {
        if ($this->ljoin === NULL)
            return '';

        // Add foreign model name
        if (! isset($this->ljoin['model'])) {
            $lmodel_name = $this->ljoin['model_name'];
            if ( !($this->ljoin['model'] = Model::open($lmodel_name)))
                throw new \InvalidArgumentException("Cannot find model with name \"{$lmodel_name}\".");
        }
        
        // Add explicit relationship
        if (($this->ljoin['join_foreign_field'] !== null) && ($this->ljoin['join_local_field'] !== null)) {
            $lfield = $this->ljoin['model']->getFieldInfo($this->ljoin['join_foreign_field'], 'sqlfield');
            if (!$lfield)
                throw new \InvalidArgumentException(
                    "There is no field with name \"{$this->ljoin['join_foreign_field']}\" on model \"{$lmodel_name}\".");
            $pfield = $this->model->getFieldInfo($this->ljoin['join_local_field'], 'sqlfield');
            if (!$pfield)
                throw new \InvalidArgumentException(
                    "There is no field with name \"{$this->ljoin['join_local_field']}\" on model \"{$this->model->getName()}\".");
        } else {
            // Add implicit relationship
            if (($pfield = $this->model->getFkFieldFor($lmodel_name, true))) {
                $pfield = $pfield['sqlfield'];
                list($lfield) = $this->ljoin['model']->getPkFields();
                $lfield = $this->ljoin['model']->getFieldInfo($lfield, 'sqlfield');
            } else if (($lfield = $this->ljoin['model']->getFkFieldFor($this->model->getName(), true))) {
                $lfield = $lfield['sqlfield'];
                list($pfield) = $this->model->getPkFields(false);
                $pfield = $this->model->getFieldInfo($pfield, 'sqlfield');
            } else {
                // No relationship found
                throw new \InvalidArgumentException(
                    "You cannot declare a left join of \"{$this->model->getName()}\" ".
                     "with \"{$lmodel_name}\" without explicitly defining join fields.");
            }
        }
        return " LEFT JOIN `{$this->ljoin['model']->getTable()}` l ON l.`{$lfield}` = p.`{$pfield}`";
    }

	/**
	 * @brief Generate SELECT query
	 */
	private function generateSelectQuery()
	{
	    $query = 'SELECT';
		foreach($this->select_fields as $field) {
			if (strcasecmp($field, 'count(*)') === 0) {	
			    $fields[] = 'count(*)';
				continue;
			}
			$fields[] = (($this->ljoin !== NULL)?'p.':'') . "`" . $this->model->getFieldInfo($field, 'sqlfield') . "`";
		}

		$query .= ' ' . implode(', ', $fields);
		$query .= ' FROM `' . $this->model->getTable() . '`' . (($this->ljoin !== NULL)?' p':'');

        // Left join
        $query .= $this->generateLeftJoin();
        
		// Conditions
		$query .= $this->generateWhereConditions();
		
		// Group by
		$query .= $this->generateGroupBy();
		
        // Order by
        $query .= $this->generateOrderBy();

		// Limit
		$query .= $this->generateLimit();
		
		return $query;
	}
	
	/**
	 * @brief Generate UPDATE query
	 */
	private function generateUpdateQuery()
	{	
	    $query = 'UPDATE `' . $this->model->getTable() . '` SET';
	
		if (count($this->set_fields) === 0)
			throw new \InvalidArgumentException("Cannot execute update() command without using set()");
			
		foreach($this->set_fields as $params) {
		    if (!($sqlfield = $this->model->getFieldInfo($params['field'], 'sqlfield')))
    			throw new \InvalidArgumentException("Unknown field {$params['field']} in update() command.");
		        
			$set_query = "`" . $sqlfield . "` = ?";
            $fields[] = $set_query;
		}
		
		$query .= ' ' . implode(', ', $fields);
		$query .= $this->generateWhereConditions();
		
        // Order by
        $query .= $this->generateOrderBy();
        
		// Limit
		$query .= $this->generateLimit();

		return $query;
	}
	
	/**
	 * @brief Generate INSERT query
	 */
	private function generateInsertQuery()
	{
	    $query = 'INSERT INTO `' . $this->model->getTable() . '`';
	
		if (count($this->insert_fields) === 0)
			throw new \InvalidArgumentException("Cannot execute insert() with no fields!");
			
		foreach($this->insert_fields as $field)
			$fields[] = "`" . $this->model->getFieldInfo($field, 'sqlfield') . "`";

		$query .= ' (' . implode(', ', $fields) . ') VALUES';
		if (count($this->insert_values) === 0)
			throw new \InvalidArgumentException("Cannot insert() with no values, use values() to define them.");

        $query .= str_repeat(
            ' (' . implode(', ', array_fill(0, count($this->insert_fields), '?')) . ')',
            count($this->insert_values)
        );

		return $query;
	}
	
	/**
	 * @brief Analyze DELETE query
	 */
	private function generateDeleteQuery()
	{	
	    $query = 'DELETE FROM `' . $this->model->getTable() . '`';
		$query .= $this->generateWhereConditions();
		
        // Order by
        $query .= $this->generateOrderBy();
		
		// Limit
		$query .= $this->generateLimit();
		
		return $query;
	}


	/**
	 * @brief Get cache hint for caching query results
	 * @return array of properties
	 *  - cachable : If this result can be cached.
	 */
	public function cacheHints()
	{   
	    // Return if it is already generated
	    if ($this->cache_hints !== NULL)
	        return $this->cache_hints;

        // Check that it is no longer altera ble
	    if ($this->sql_query === NULL)
	        return NULL;
	        
	    // Initialize array
	    $this->cache_hints = array(
	        'cachable' => ($this->query_type === 'select'),
	        'invalidate_on' => array()
	    );

        // Left joins are not cachable
	    if ($this->ljoin !== NULL)
	        $this->cache_hints['cachable'] = false;

	    return $this->cache_hints;
	}
	
	/**
	 * @brief Create the sql command for this query.
	 * 
	 * Executing sql() will make query non-alterable and fixed,
	 * however you can use execute() multiple times.
	 * @return string The string with SQL command.
	 */
	public function sql()
	{	
	    // Check if sql has been already crafted
		if ($this->sql_query !== NULL)
			return $this->sql_query;
		
		// Check model cache
		$cache = $this->model->cacheFetch($this->sql_hash, $succ);
		if ($succ) {
			$this->sql_query = $cache['query'];
		    $this->cache_hints = $cache['cache_hints'];
			return $this->sql_query;
		}
		
		if ($this->query_type === 'select')
			$this->sql_query = $this->generateSelectQuery();
		else if ($this->query_type === 'update')
			$this->sql_query = $this->generateUpdateQuery();
		else if ($this->query_type === 'delete')
			$this->sql_query = $this->generateDeleteQuery();
		else if ($this->query_type === 'insert')
			$this->sql_query = $this->generateInsertQuery();
		else
			throw new \RuntimeException('Query is not finished to be exported.' .
				' You have to use at least one of the main commands insert()/update()/delete()/select(). ');

        // Cache hints
        $this->cacheHints();
        
		// Save in cache
		$this->model->cachePush($this->sql_hash, 
		    array(
		        'query' => $this->sql_query,
		        'cache_hints' => $this->cache_hints
		    )
		);
        
		return $this->sql_query;
	}
	
	/**
	 * @brief Force preparation of statement.
	 * 
	 * Prepare this statement if it is not yet. Otherwise don't do nothing.
	 * @note Statements are prepared automatically at execution time.
	 * @return NULL
	 */
	public function prepare()
	{	
	    if (!Connection::isKeyUsed($this->sql_hash))
			return Connection::prepare($this->sql_hash, $this->sql());
	}
	
	/**
	 * @brief Execute statement and return the results
	 * @return mixed The output data.
	 */
	public function execute()
	{	
	    // Merge pushed parameters with functions
		$params = func_get_args();		
		$params = array_merge($this->exec_params, $params);
		
		// Prepare query
		$this->prepare();

		// Check cache if select
		if ($this->query_type === 'select') {
			$data = $this->query_cache->fetchResults($this, $params, $succ);
			if ($succ)
				return $data;
		}

		// Execute query
		if ($this->query_type === 'select')
			$data = Connection::executeFetchAll($this->sql_hash, $params);
		else
			$data = Connection::execute($this->sql_hash, $params);

		// User wrapper
		if ($this->data_wrapper_callback !== NULL)
		    $data = call_user_func($this->data_wrapper_callback, $data, $this->model);

		// Cache it
		$this->query_cache->processQuery($this, $params, $data);
		
		// Return data
		return $data;
	}
}
