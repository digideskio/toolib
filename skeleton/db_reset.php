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
dbconn::init($GS_db_host, $GS_db_user, $GS_db_pass, $GS_db_schema, 'ondberror');

function dbquery($query)
{    echo '<pre>' . $query . '</pre>';

    if (dbconn::query($query))
        echo '<h3>OK</h3><hr>';
}

dbconn::query('SET NAMES utf8;');
dbquery('DROP TABLE IF EXISTS users');
dbquery('DROP TABLE IF EXISTS session_log');

dbquery("
CREATE TABLE `users` (
	`user` CHAR(16) NOT NULL,		        -- User is maximum 16 chars and this is the primary key too.
	`password` CHAR(32) NOT NULL,	        -- The password is always md5 which is 32 characters.
	`is_enabled` BOOL DEFAULT 1,
	PRIMARY KEY(`user`))
ENGINE=InnoDB
DEFAULT CHARACTER SET = utf8
");

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
)
ENGINE=InnoDB
DEFAULT CHARACTER SET = utf8;");

dbquery("INSERT INTO `users` (user, password, is_enabled)
    VALUES('root', MD5('root'), '1');");

echo '<br><h1>Database installed</h1>';

?>
