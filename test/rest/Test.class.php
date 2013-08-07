<?php

class TestTable extends \toolib\DB\Record
{
	public static $table = 'test';
	public static $fields = array(
		'id' => array('pk' => true, 'ai' => true),
		'name',
		'value',
		'created_at' => array('type' => 'datetime'),
		'modified_at' => array('type' => 'datetime')
	);
	
	public function __toString()
	{
		return $this->name;
	}
}

TestTable::events()->connect('pre-create', function(\toolib\Event $e){
	$e->filtered_value['created_at'] = date_create();
	$e->filtered_value['modified_at'] = date_create();
});

TestTable::events()->connect('pre-update', function(\toolib\Event $e){
	$e->arguments['record']->modified_at = date_create();
});