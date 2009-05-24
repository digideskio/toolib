http://phplibs.kmfa.net             |
                                    |
by kmfa.net                         |
------------------------------------|


Site Skeleton
=============

A. Installation
-------------------

* Copy all the files to the published web folder of your web server.
* Edit config.inc.php with your favorite editor and change the parameter
  values to match the configuration of your mysql sever. Change any other
  parameter that you wish like the site title etc.
* Open your favorite webbrowser and visit http://<location>/<sitepath>/db_reset.php
  Execute the script to reset your database with the latest structure.
  There is a "password" that must be supplied in order to reset the database, however
  it is not there to protect you from other but to protect from reseting by mistake the database.
  The password is "resetme".
  NOTICE: This will drop any previous tables with the same names.
  NOTICE 2: DELETE db_reset.php file from the final site!!!!
  A test user is also created with username "root" and password "root".
  
* You are ready to start editing your site and extend it.


visit http://phplibs.kmfa.net/api.php for more information