
PHPLibs.kmfa.net Skeleton Site
==============================

A. Installation
-------------------

* Copy all the files to the published web folder of your web server.
* Edit config.inc.php with your favorite editor and change the parameter
  values to match the configuration of your mysql sever. Change any other
  parameter that you wish like the site title etc.
* Open .htaccess and update RewriteBase parameter with your site virtual directory.
* Open your favorite webbrowser and visit http://<location>/<sitepath>/db_reset.php
  Execute the script to reset your database with the latest structure, the password is "resetme".
  The password it is not there to protect you from others,
  but to protect from reseting by mistake the database.
  
  NOTICE: This will drop any previous tables with the same names.
  NOTICE 2: DELETE db_reset.php file from the final site!!!!
  A test user is also created with username "root" and password "root".

* You are ready to start editing your site and extend it.


visit http://phplibs.kmfa.net/api.php for more information


B. TroubleShoot
---------------

* Page is rendered without style (white page with black letters no graphics)
  > Edit config.inc.php and properly configure the $GS_site_root to reflect the relative root of your site.
* Clicking on any link other than home page results in 404 error page
  > Edit .htaccess and change RewriteBase to properly reflect relative root of your site.
  > If the previous was not enough, check your apache configuration that permits .htaccess and you have mod_rewrite enabled.
