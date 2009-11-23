<?php
require_once('init.inc.php');

// Security check
if ((isset($_POST['dbreset_pass']) && ($_POST['dbreset_pass'] != 'resetme')) ||
	(!isset($_POST['dbreset_pass'])))
{	// Clean all tables
	echo '<form action="" method="POST">';
	echo '<input type="password" name="dbreset_pass"><input type="submit" value="Reset">';
	echo '</form>';
	return;
}

function ondberror()
{    
    echo '<h3 style="color: #ff0000;">DB Error: ' . dbconn::get_link()->error . '</h3>';
	exit(1);
}

// Initialize connection
dbconn::init(Config::get('db_host'), Config::get('db_user'), Config::get('db_pass'), Config::get('db_schema'), 'ondberror');

function dbquery($query)
{    echo '<pre>' . $query . '</pre>';

    if (dbconn::query($query))
        echo '<h3>OK</h3><hr>';
}

dbconn::query('SET NAMES utf8;');
dbquery('DROP TABLE IF EXISTS group_members');
dbquery('DROP TABLE IF EXISTS groups');
dbquery('DROP TABLE IF EXISTS users');
dbquery('DROP TABLE IF EXISTS session_log');

dbquery("
CREATE TABLE `users` (
	`user` CHAR(16) NOT NULL,		        -- User is maximum 16 chars and this is the primary key too.
	`password` CHAR(32) NOT NULL,	        -- The password is always md5 which is 32 characters.
	`is_enabled` BOOL DEFAULT 1,
	PRIMARY KEY(`user`)
)ENGINE=InnoDB DEFAULT CHARSET= utf8;");

dbquery("
CREATE TABLE `groups` (
	`groupname` VARCHAR(255) NOT NULL,		-- The name of this group
	PRIMARY KEY(`groupname`)
)ENGINE=InnoDB DEFAULT CHARSET= utf8;");

dbquery("
CREATE TABLE `group_members` (
	`groupname` VARCHAR(255) NOT NULL,
	`username` CHAR(16) NOT NULL,
	FOREIGN KEY (`groupname`) REFERENCES `groups`(`groupname`),
	PRIMARY KEY(`groupname`, `username`)
)ENGINE=InnoDB DEFAULT CHARSET= utf8;");

dbquery("
CREATE TABLE `session_log` (
    `log_id` INT NOT NULL auto_increment,
    `ip` VARCHAR(40) NOT NULL,
    `user` VARCHAR(32) NOT NULL,
    `user_agent` VARCHAR(255),
    `ts_start` DATETIME NOT NULL,
    `ts_lastseen` DATETIME NOT NULL,
    `expired` BOOL DEFAULT FALSE,
    PRIMARY KEY(`log_id`)
)ENGINE=InnoDB DEFAULT CHARSET= utf8;");
	
dbquery("INSERT INTO `users` (user, password, is_enabled)
    VALUES('root', MD5('root'), '1');");

dbquery("INSERT INTO `groups` (groupname)
    VALUES('admin');");

dbquery("INSERT INTO `group_members` (groupname, username)
    VALUES('admin', 'root');");

echo '<br><h1>Database installed</h1>';

?>
