<?php
require_once dirname(__FILE__) . '/lib/vendor/phplibs/ClassLoader.class.php';
require_once dirname(__FILE__) . '/lib/tools.lib.php';
/**
 * Here you can write code that will be executed at the begining of each page instance
 */
 
// Autoloader for local and phplibs classes
$phplibs_loader = new ClassLoader(
    array(
        dirname(__FILE__) . '/lib/vendor/phplibs',
        dirname(__FILE__) . '/lib/local'
    ));
$phplibs_loader->set_file_extension('.class.php');
$phplibs_loader->register();

// Start code profiling
Profile::checkpoint('document.start');

// Load static library for HTML
require_once dirname(__FILE__) . '/lib/vendor/phplibs/Output/html.lib.php';

// Load configuration file
require_once dirname(__FILE__) . '/config.inc.php';

// Database connection
DB_Conn::connect(Config::get('db.host'), Config::get('db.user'), Config::get('db.pass'), Config::get('db.schema'), 'error_log', true);
DB_Conn::query('SET NAMES utf8;');
DB_Conn::query("SET time_zone='+0:00';");

// PHP TimeZone
date_default_timezone_set(Config::get('site.timezone'));

// Setup authentication
$auth = new Auth_Backend_DB(array(
    'model_user' => 'User',
    'field_username' => 'user',
    'field_password' => 'password',
    'hash_function' => 'md5'
));
Auth_Realm::set_backend($auth);

?>
