<?php
/**
 * Change settings or expand with custom ones.
 */
// Database host name
Config::add('db.host', 'localhost');

// Database connection username
Config::add('db.user', 'root');

// Database connection password
Config::add('db.pass', 'root');

// Database schema
Config::add('db.schema', 'test');

// The title of this site
Config::add('site.title', 'Kick Ass Site');

// The time zone to be used on this site
Config::add('site.timezone', 'UTC');

// You can enable this by specifying your google analytics ID
Config::add('site.ga', false);
?>
