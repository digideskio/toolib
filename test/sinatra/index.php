<?php

session_start();

require_once __DIR__ . '/../../lib/vendor/toolib/autoload.php';
$app = new \toolib\Stupid\Template\Sinatra();

$app->predo(function() use($app) {
	$app->response->setCachePublic();
	$app->response->addHeader('X-Powered-By', 'Toolib');
	$app->url->createMultiple(array(
		'item.index' => '/',
		'item.get' => '/{key}',
		'item.generate' => '/+generate',
	));
});

$app->get('/', function() use($app){
	if (isset($_SESSION['last_time']))
		$app->response->setLastModified($_SESSION['last_item']);
	if ($app->response->isNotModified($app->request))
		return $app->response->reply304NotModified();
		
	return toolib\template( __DIR__ . '/index.html.php', array('request' => $app->request, 'url' => $app->url));
});

$app->get('/+generate', function() use($app){
	return  toolib\template( __DIR__ . '/generate.html.php');
});

$app->post('/+generate', function() use($app){
	$key = sha1(rand());
	return $app->response->reply303SeeOther('/projects/toolib/test/sinatra/index.php/' . $key);
});

$app->get('/{key}', function($key) use($app){
	if (!isset($_SESSION[$key])) {
		$_SESSION[$key] = date_create();
		$_SESSION['last_item'] = $_SESSION[$key];
	}
	$gen_time = $_SESSION[$key];
		
	$app->response->setLastModified($gen_time);
	if ($app->response->isNotModified($app->request)) {
		return $app->response->reply304NotModified();
	}

	return toolib\template( __DIR__ . '/obj.html.php', array('request' => $app->request, 'key' => $key, 'gen_time' => $gen_time));
});
$app();

