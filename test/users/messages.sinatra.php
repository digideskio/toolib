<?php

use \toolib\Stupid\Template\Sinatra;

$s = new Sinatra();

$s('GET /', function(){
	echo Messages::openAll();
});

$s('GET /{id}', function($id){
	if (!$m = Messages::open($id))
		return $this->response->reply404NotFound();
	
	$this->response->setLastModified($m->last_modified);
	$this->response->setEtag($m->etag);
	if ($this->response->isNotModified($this->request))
		return $this->response->reply304NotModified();
	
	echo $m;
});

$s('POST /', function(){
	if (!$m = Message::create($this->request->getContent()->getArrayCopy()))
		return $this->response->reply400BadRequest();
	
	return $this->response->reply201Created('/' . $m->id);
});

$s('PUT /{id}', function(){
	if (!$m = Messages::open($id)) {
		return $this->response->reply404NotFound();
	}
	
	if ($id != $this->request->getContent()->get('id', '')) {
		return $this->response->reply400BadRequest();
	}
	
	foreach($this->request->getContent()->getArrayCopy() as $name => $value) {
		$m->{$name} = $value;
	}
	if (!$m->update()) {
		return $this->response->reply500InternalServerError();
	}
	
	echo $m;
});

$s('DELETE /{id}', function(){
	if (!$m = Messages::open($id)) {
		return $this->response->reply404NotFound();
	}
	$m->delete();
	$this->response->reply204NoContent();
});