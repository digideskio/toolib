<?php

//! Object handling collection from 1-to-M relationship
/**
 * This object is constructed when requesting a relationship from a DB_Record.
 * Check DBRecord for more information on how to construct it.
 */
class DB_Record_RelationshipMany
{
	//! The constructed query
	private $query;

    //! Relationship info
    private $rel_params = array();
    
    //! Construct relationship handler
	public function __construct($local_model, $foreign_model_name, $field_value)
	{	// Construct query object
	    $foreign_model = DB_Record::model($foreign_model_name);

	    // Save parameters
	    $this->rel_params['local_model'] = $local_model;
	    $this->rel_params['foreign_model'] = $foreign_model;
	    $this->rel_params['field_value'] = $field_value;
	    
		$this->query = DB_Record::open_query($foreign_model_name)
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

?>
