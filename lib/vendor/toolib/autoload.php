<?php

namespace toolib;

require_once __DIR__ . '/ClassLoader.class.php';
$loader = new \toolib\ClassLoader('toolib', __DIR__ . '/..' );
$loader->register();

function html_echo($text)
{
	echo \htmlspecialchars($text, ENT_QUOTES);
}

function template($file, $enviroment = array())
{
	foreach($enviroment as $var => $value)
		$$var = $value;
	include $file;
}