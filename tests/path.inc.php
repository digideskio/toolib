<?php
/*
 *  This file is part of PHPLibs <http://phplibs.kmfa.net/>.
 *  
 *  Copyright (c) 2010 < squarious at gmail dot com > .
 *  
 *  PHPLibs is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *  
 *  PHPLibs is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License
 *  along with PHPLibs.  If not, see <http://www.gnu.org/licenses/>.
 *  
 */


require_once __DIR__ . '/../lib/vendor/toolib/ClassLoader.class.php';
require_once __DIR__ . '/../lib/SplClassLoader.class.php';

$loader = new SplClassLoader('toolib', __DIR__ . '/../lib/vendor');
$loader->setFileExtension('.class.php');
$loader->register();

// Register phplibs
/*$loader = new ClassLoader();
$loader->register_directory(__DIR__ . '/../lib/vendor/toolib');
$loader->set_file_extension('.class.php');
$loader->register();
*/