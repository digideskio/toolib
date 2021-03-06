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

namespace toolib\DB\Record;
use toolib\DB\Record;

/**
 * @brief Collection for 1-to-M relationship.
 * 
 * This object is constructed when requesting a relationship from a \\toolib\\DB\\Record.
 */
class RelationshipMany
{
    /**
     * @brief Relationship options
     * @var array
     */
    private $rel_params;

    /**
     * @brief Query object
     * @var \toolib\DB\ModelQuery
     */
    private $query;
    
    /**
     * @brief Construct relationship handler 
     */
	public function __construct($local_model, $foreign_model_name, $field_value)
	{	
		// Construct query object
	    $foreign_model = $foreign_model_name::getModel();

	    // Save parameters
	    $this->rel_params['local_model'] = $local_model;
	    $this->rel_params['foreign_model'] = $foreign_model;
	    $this->rel_params['field_value'] = $field_value;
	    
		$this->query = $foreign_model_name::openQuery()
			->where($foreign_model->getFkFieldFor($local_model->getName()) . ' = ?')
			->pushExecParam($field_value);
	}

	/**
	 * @brief Get all records of this relationship
	 * @return array of \toolib\DB\Record
	 */ 
	public function all()
	{	
		return $this->query->execute();
	}

    /**
	 * @brief Perform a subquery on this relationship
	 * @return \toolib\DB\ModelQuery
	 */ 
	public function subquery()
	{
		return $this->query;
	}

	/**
	 * @brief Get one only member with a specific primary key
	 * @param mixed $primary_key String of primary key value, or array of values
	 */
	public function get($primary_key)
	{
		$pks = $this->rel_params['foreign_model']->getPkFields();
	    $res = $this->subquery()->where("{$pks[0]} = ?")->execute($primary_key);
	    if (count($res) > 0)
	        return $res[0];
	    return null;
    }
}
