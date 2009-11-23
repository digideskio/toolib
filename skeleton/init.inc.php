<?php
// Load settings
require_once('lib/phplibs/benchmark.lib.php');
require_once('config.inc.php');

// Configurate timezones
date_default_timezone_set('UTC');

// Database Connection (errors will be written to php error log)
require_once('lib/phplibs/mysqli.lib.php');
dbconn::init(Config::get('db_host'), Config::get('db_user'), Config::get('db_pass'), Config::get('db_schema'), 'error_log', true);
dbconn::query('SET NAMES utf8;');
dbconn::query("SET time_zone='+0:00';");

// Add extra needed classes
require_once('lib/phplibs/functions.lib.php');
require_once('lib/phplibs/http.lib.php');
require_once('lib/phplibs/waas.lib.php');
require_once('lib/phplibs/groups.lib.php');
require_once('lib/phplibs/sessionlog.lib.php');
require_once('lib/phplibs/dbrecord.lib.php');
require_once('lib/phplibs/stupid.lib.php');

// Enable APC
DBRecord::$apc_cache = false;
DBRecord::$apc_prefix = 'phplibs';

// Start the php session
session_start();

// Prevent session fixation with invalid ids
if (!isset($_SESSION['initialized']))
{
    $_SESSION['initialized'] = true;
    session_regenerate_id();
}
    
// Touch user session
sessionlog::touch_current();

////////////////////////////////////////
// Extra functions

//! Calculate the root path
function rpath($rel)
{   if (Config::get('site_root') == '/')
        return $rel;
    return Config::get('site_root') . $rel;
}

//! Calculate full url
function full_url($rel)
{   return (empty($_SERVER['HTTPS'])?'http':'https') . '://' . $_SERVER['HTTP_HOST'] .  rpath($rel);	}

// Implementation of layout functions
function a($lnk, $text, $esc_text = true, $guess_path = true)
{
    return '<a class="ui-clickable" href="'. ($guess_path?rpath($lnk):$lnk) . '">'. ($esc_text?esc_html($text):$text).'</a>';
}

// Implementation of js navigate
function js_navigate($lnk)
{	return 'window.location=\'' . esc_js(rpath($lnk)) . '\'';	}

//! Linkify issues
function linkify_issues($text)
{
	// /((?:http|ftp):\/\/[^\s\<]*)/im
	//	return preg_replace('/#([0-9]+)[\s\<]*/im', '<a href="' .rpath('/issues.php?action=view&id=') .'\\1">\\0</a>', $text);
	// This must be improved
	return preg_replace('/#([0-9]+)/im', '<a href="' .rpath('/issues.php?action=view&id=') .'${1}">${0}</a>', $text);
}
?>