<?php


use toolib\Stupid;
use toolib\Stupid\Condition as Cond;
use toolib\Http;

require_once __DIR__ . '/../lib/vendor/toolib/ClassLoader.class.php';
$loader = new \toolib\ClassLoader('toolib', __DIR__ . '/../lib/vendor');
$loader->register();

require_once __DIR__ . '/users/photos.rules.php';

$stupid = new Stupid();

$users_key_req = '[[:digit:]]+';
/*
 * INDEX
 */
$stupid->createRule('users.index',
	Cond\Request::create()->methodIsGet(),
	Cond\Bool::opOr(
		Cond\Request::create()->pathIs('/users/index'),
		Cond\Request::create()->pathIs('/users')
	)
)->addAction(function($request){
	echo 'Users.index';
});

/*
 * GET a user
 */
$stupid->createRule('users.get',
	Cond\Request::create()->pathPatternIs('/users/{id}', array('id' => $users_key_req))
)->addAction(function(Stupid\Knowledge $knowledge){
	echo 'Requesting user ' . $knowledge->results['request.params']['id'];
});

/*
 * POST a new user
 */
$stupid->createRule('users.create',
	Cond\Request::create()->pathIs('/users')->methodIs('post')
)->addAction(function($id){
	/*
	if (Authnz::getInstance()->isAnonymous())
		$this->getResponse()->replyNotAuthorized();
	echo 'Photos.create';
	*/
});

/*
 * DELETE a user
 */
$stupid->createRule('users.delete',
	Cond\Request::create()->pathIs('/users')->methodIsDelete()
)->addAction(function($request){
	echo ' deleting user ';
});

/*
 * PUT a user (update)
 */
$stupid->createRule('users.update',
	Cond\Request::create()->methodIsPut()
		->pathPatternIs('/users/{id}', array('id' => $users_key_req))
)->addAction(function($request, $id){
	echo 'users.update';
});

/*
* USER photos
*/
$stupid->createRule('users.photos',
	Cond\Request::create()->pathPatternIs('/users/{id}/photos', array('id' => $users_key_req))
)->addActionChainToClass('UserPhotosRest');

$e = function($event){ echo $event->name . ' ' . $event->arguments['rule']->getName() . "</br>"; };
$stupid->events()->connect('rule.process.begin', $e);
$stupid->events()->connect('rule.process.end', $e);
$stupid->events()->connect('rule.process.succeeded', $e);
$stupid->events()->connect('rule.action.executed', $e);

$gw = new Http\Cgi\Gateway();
var_dump('Worked! ', $stupid->execute(new Stupid\Knowledge(array('request.gateway' => $gw)))->getName());
