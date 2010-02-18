<?php
require_once('init.inc.php');

// Hard-coded controllers
Stupid::add_rule(create_function('', 'require_once(\'login.php\');'),
	array('type' => 'url_path', 'chunk[-1]' => '/\+login/')
);
Stupid::add_rule(create_function('', 'require_once(\'login.php\');'),
	array('type' => 'url_path', 'chunk[-1]' => '/\+logout/')
);
Stupid::add_rule(create_function('', 'require_once(\'home.php\');'),
	array('type' => 'url_path', 'path' => '/^$/')
);

// Include all controlers under /web
function is_valid_controller($cont){	return is_file("controllers/$cont.php");	}
Stupid::add_rule(create_function('$a', 'require_once("controllers/$a.php");'),
	array('type' => 'url_path', 'chunk[1]' => '/^([a-z]+)$/'),
	array('type' => 'func', 'func' => 'is_valid_controller')
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
		tag('p', 'Sorry we were unable to find any information about this object. ')
	);
	exit;
}

?>