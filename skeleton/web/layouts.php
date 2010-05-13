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


// Enable XHTML Mode
Output_HTMLTag::$default_render_mode = 'xhtml';

///////////////////////////////////
// Layout "default"
$dl = Layout::create('default')->activate();
$dl->get_document()->title = Config::get('site.title');
$dl->get_document()->add_ref_css(surl('/static/css/default.css'));
etag('div id="header"',
tag('h1', Config::get('site.title')),
tag('div id="menu"')
);
$def_content = etag('div id="content"');
etag('div id="sidebar"');
etag('div id="footer"', tag('a', 'PHPlibs', array('href' => 'http://phplibs.kmfa.net')), ' skeleton');
$dl->set_default_container($def_content);

// Menu for default layout
$dl->menu = new SmartMenu(array('class' => 'menu'));
$dl->events()->connect('pre-flush',
create_function('$event', '$layout = $event->arguments["layout"];
    $layout->get_document()->get_body()->getElementById("menu")->append($layout->menu->render());'));
$dl->menu->create_link('Home', '/')->set_autoselect_mode('equal');
$dl->menu->create_link('Section 1', '/section1');
$dl->menu->create_link('Section 2', '/section2');
$dl->deactivate();

?>
