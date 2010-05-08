
PHPLib SKELETON

1. Introduction
---------------

	PHPlibs skeleton is an example usage of phplibs. It has defined well defined 
	directory structure, and also it has embeded url routing, configuration 
	and bootstrap system. All you need to do is change site parameters in config.inc.php
	either by editing file directly or by running install.php

2. Installation
---------------

2.1 Manual
	You can edit directly config.inc.php and change database options to reflect your
	needs. You can also change any other configuration that you believe its usefull, or
	expand it with custom ones.

2.2 Using install.php
	Skeleton now comes with install.php script in its root folder, which you can navigate
	through browser and use it to configure database parameters. It is also more helpfull
	as it tries to connect to database before storing changes.
  
3. Cool urls
------------

	By default Skeleton comes with cool url support but it does not hide script name, this
	can only be done using url redirection in .htaccess file. If you used install.php it shows
	the proper data to add in .htaccess which is
  
	php_flag magic_quotes_gpc off
	
	RewriteEngine On
	RewriteBase /change/this/path/to/sub/folder
	
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteCond %{REQUEST_FILENAME} !-d
	RewriteRule ^(.*)$ ./index.php/$1 [PT,L,QSA]"  