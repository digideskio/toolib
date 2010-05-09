
PHPLibs is a set of php libraries that can help to easily develop a php site
without relying on strict and complex frameworks. It was originaly developed
to create the kmfa.net but it was then released to the public to help anyone.

/lib folder
-----------
	Contains all the files of PHPLibs structured under PSR-0 standrad, thus
	you can	use any PSR-0 class loader to load PHPLibs classes.
	
/skeleton folder
----------------
	To make it easier for the starter an example site was created that can
	be downloaded and start expanding it. This is like a skeleton to start
	building your own site using PHPlibs. This folder contains the skeleton
	site with a copy of /lib folder in the proper place, that can be copied
	in your webserved folder. Read README.txt inside /skeleton folder for
	more information.
	
/tests folder
-------------
	PHPLibs comes with a test unit system to track bugs and API conformance.
	You usually dont need to run tests as it has been done for you before
	release. However if you really want you can read README.txt inside tests
	folder to check how you can do it. 