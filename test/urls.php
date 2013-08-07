<?php
return array(
	'users.index' => '/users',
	'users.create' => '/users',
	'users.get' => '/users/{slug}',
	'users.update' => '/users/{id}',				/* user->id   user */  /* url('users.update', array('id' => $user->id))
																url('users.update', $user)*/
	'users.delete' => '/users/{user.id}/{user}',	/* user */	/*url('users.update', array('user' => $user)*/
	'users.delete' => '/users/{user.id}/{user}',	/* user */
	'photos.index' => '/users/{photo.owner.id}/photos',
	'photos.create' => '/users/{photo.owner.id}/photos',
	'photos.get' 	=> '/users/{photo.owner.id}/photos/{photo.id}',
	'photos.update' => '/users/{photo.owner.id}/photos/{photo.id}',
	'photos.delete' => '/users/{photo.owner.id}/photos/{photo.id}',
	
	'admin' => array('secure' => true, 'pattern' => '/admin'),
	'admin.api.editor' => array('secure' => true, 'pattern' => '/admin/api/pages'),
	'admin.editor' => array('secure' => true, 'pattern' => '/admin/pages'),
	
	'api.pages.index' => '/_control/pages',
	'api.pages.create' => '/_control/pages',
	'api.pages.get' => '/_control/pages/{id}',
	'api.pages.update' => '/_control/pages/{id}',
	'api.pages.delete' => '/_control/pages/{id}',
	'api.users.index' => '/_control/users',
	'api.users.create' => '/_control/users',
	'api.users.get' => '/_control/users/{id}',
	'api.users.update' => '/_control/users/{id}',
	'api.users.delete' => '/_control/users/{id}',
);