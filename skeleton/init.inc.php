<?php
// Load settings
require_once('config.inc.php');

// Configurate timezones
date_default_timezone_set('UTC');

// Database Connection (errors will be written to php error log)
require_once($GS_libs . 'lib/mysqli.lib.php');
dbconn::init($GS_db_host, $GS_db_user, $GS_db_pass, $GS_db_schema, 'error_log', true);
dbconn::query('SET NAMES utf8;');
dbconn::query("SET time_zone='UTC';");

// Add extra needed classes
require_once($GS_libs . 'lib/functions.lib.php');
require_once($GS_libs . 'lib/waas.lib.php');
require_once($GS_libs . 'lib/group.lib.php');
require_once($GS_libs . 'lib/sessionlog.lib.php');
require_once($GS_libs . 'lib/jquery.lib.php');

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
{   global $GS_site_root;

    if ($GS_site_root == '/')
        return $rel;
    return $GS_site_root . $rel;
}

// Implementation of layout functions
function a($lnk, $text)
{
    return '<a class="ui-clickable" href="'.rpath($lnk).'">'.esc_html($text).'</a>';
}
?>