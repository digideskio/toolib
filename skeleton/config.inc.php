<?php
/**
 * Configure your site.
 */
require_once('lib/config.lib.php');

// Database host name
Config::add('db_host', 'localhost');

// Database connection username
Config::add('db_user', 'user');

// Database connection password
Config::add('db_pass', 'pass');

// Database schema
Config::add('db_schema', 'test');

// The title of this site
Config::add('site_title', 'My New Site');

// Path of this site as it is exposed on webserver
Config::add('site_root', '/');

// Enabling this will force browser to avoid caching css (usefull on development)
Config::add('css_anticache', true);

// You can enable this by specifying your google analytics ID
Config::add('google_analytics', false);
?>
