<?php
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
$dl->menu = new SmartMenu();
$dl->events()->connect('pre-flush', 
    create_function('$event', '$layout = $event->arguments["layout"];
    $layout->get_document()->get_body()->getElementById("menu")->append($layout->menu->render());'));
$dl->menu->add_link('Home', '/', 'equal');
$dl->menu->add_link('Section 1', '/section1');
$dl->menu->add_link('Section 2', '/section2');
$dl->deactivate();

?>
