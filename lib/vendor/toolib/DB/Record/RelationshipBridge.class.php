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
 * Object handling collection from N-to-M relationship.
 * 
 * This object is constructed when requesting a relationship from a DB_Record.
 * Check DB_Record for more information on how to construct it.
 */
class RelationshipBridge
{
    /**
     * Relationship options
     * @var array
     */
    private $rel_params;

    /**
     * Query object
     * @var \toolib\DB\ModelQuery
     */
    private $query;
    
    //! Construct relationship
    public function __construct($local_model, $bridge_model_name, $foreign_model_name, $local_value)
    {   
        // Construct relationship array
        $bridge_model = $bridge_model_name::getModel();
        $foreign_model = $foreign_model_name::getModel();

        $rel = array();
		$rel['local_model_name'] = $local_model->getName();
		$rel['bridge_model_name'] = $bridge_model_name;    		
		$rel['foreign_model_name'] = $foreign_model_name;
		    $pks = $local_model->getPkFields();
	    $rel['local2bridge_field'] = $pks[0];
	    $rel['bridge2local_field'] = $bridge_model->getFkFieldFor($local_model->getName());
	    $rel['bridge2foreign_field'] = $bridge_model->getFkFieldFor($foreign_model_name);
	        $pks = $foreign_model->getPkFields();
	    $rel['foreign2bridge_field'] = $pks[0];
	    $rel['local_bridge_value'] = $local_value;
        
		// Construct joined query
		$this->query = $rel['foreign_model_name']::openQuery()
            ->leftJoin($rel['bridge_model_name'], $rel['foreign2bridge_field'], $rel['bridge2foreign_field'])
            ->where('? = l.' . $rel['bridge2local_field'])
            ->pushExecParam($rel['local_bridge_value']);

        // Save relationship
        $this->rel_params = $rel;
    }

    /**
     * @param \toolib\DB\Record $record
     * @return \toolib\DB\Record
     */
    public function add(\toolib\DB\Record $record)
    {   
    	$keys = $record->getKeyValues();
        $params = array(
            $this->rel_params['bridge2local_field'] => $this->rel_params['local_bridge_value'],
            $this->rel_params['bridge2foreign_field'] => current($keys)
        );
        return Record::create($params, $this->rel_params['bridge_model_name']);
    }

    /**
     * @param \toolib\DB\Record $record The record to remove from bridge table
     */
    public function remove(\toolib\DB\Record $record)
    {   
    	$keys = $record->getKeyValues();
        $params = array(
            $this->rel_params['bridge2local_field'] => $this->rel_params['local_bridge_value'],
            $this->rel_params['bridge2foreign_field'] => current($keys)
        );
        if (($bridge_record = Record::open($params, $this->rel_params['bridge_model_name'])) === FALSE)
            return false;

        return $bridge_record->delete();
    }

	/**
	 * Get all records of this relationship
	 * @return array of \toolib\DB\Record
	 */ 
	public function all()
	{	
		return $this->query->execute();
	}

    /**
	 * Perform a subquery on this relationship
	 * @return \toolib\DB\ModelQuery
	 */ 
	public function subquery()
	{
		return $this->query;
	}
}
