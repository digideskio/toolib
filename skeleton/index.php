<?php
require_once('init.inc.php');

// Login/logout rules
Stupid::add_rule(create_function('', 'require_once(\'login.php\');'),
	array('type' => 'url_path', 'chunk[-1]' => '/\+login/'));
Stupid::add_rule(create_function('', 'require_once(\'login.php\');'),
	array('type' => 'url_path', 'chunk[-1]' => '/\+logout/'));

// Section rules
Stupid::add_rule(create_function('', 'require_once(\'section1.php\');'),
	array('type' => 'url_path', 'chunk[1]' => '/^section1$/'));
Stupid::add_rule(create_function('', 'require_once(\'section2.php\');'),
	array('type' => 'url_path', 'chunk[1]' => '/^section2$/'));

// Home
Stupid::add_rule(create_function('', 'require_once(\'home.php\');'),
	array('type' => 'url_path', 'path' => '/^$/')
);
Stupid::set_default_action('not_found');
Stupid::chain_reaction();


function not_found($subject = NULL)
{	header("HTTP/1.1 404 Not Found");
	require_once('layout.inc.php');
	
	if ($subject === NULL)
		$subject = $_SERVER['REQUEST_URI'];
	$GLOBALS['html']->title = Config::get('site_title') . ' | Not Found';
	etag('div class="not-found"',
		tag('h1 class="error"', "Not Found: \"$subject\" "),
		tag('p', 'Sorry we were unable to find any information about this object. ' .
			'If you copied an external link make sure '.
			'that you copied the url correctly. If you are watching an ', tag('strong', 'under devolpement'),
			' documentation come back later to see if it is fixed.'),
		tag('em', 'The administrator will be notified to check if there ' .
			'is any technical problem with the server.')
	);
	exit;
}

?>