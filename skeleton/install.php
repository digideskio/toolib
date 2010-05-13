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


require_once dirname(__FILE__) . '/bootstrap.php';

// File names
$fn_config = dirname(__FILE__) . '/config.inc.php';
$fn_htaccess = dirname(__FILE__) . '/.htaccess';


$dl = Layout::create('debug')->activate();
$dl->get_document()->add_ref_css(surl('/static/css/debug.css'));
$dl->get_document()->title = 'Installation';

etag('h2', 'PHPLibs Skeleton');
etag('h3', 'Installation process');

// Make checks for writable files
if (! is_writable($fn_config))
{
    etag('div class="error" nl_escape_on', 'Cannot continue installing ' . Config::get('site.title') .'
        The configuration file "config.inc.php" must be writable, you can change
        permissions and retry installation.');
    exit;
}

$f = new UI_InstallationForm($fn_config, dirname(__FILE__) . '/install/build.sql');
etag('div', $f->render());
?>
