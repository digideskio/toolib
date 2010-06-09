
PHPLib SKELETON

1. Introduction
---------------

	PHPlibs skeleton is an example usage of phplibs. It has defined well defined 
	directory structure, and also it has embeded url routing, configuration 
	and bootstrap system. All you need to do is change site parameters in config.inc.php
	either by editing file directly or by running /install

2. Installation
---------------

2.1 Manual
	You can edit directly config.inc.php and change database options to reflect your
	needs. You can also change any other configuration that you believe its usefull, or
	expand it with custom ones.

2.2 Using /install
	Skeleton now comes with /install script in its root folder, which you can navigate
	through browser and use it to configure database and otherparameters. It is also 
	more helpfull as it tries to connect to database before storing changes.
  
3. Cool urls
------------

	By default Skeleton comes with cool url support and transparent script which is achieved
	through a standard shipped .htaccess file. However it may need a tweak to reflect your
	servers configuration. If you used /install it will alert if .htaccess needs any extra
	manual editing.  
