<?php
require_once('init.inc.php');
benchmark::checkpoint('start');
require_once('lib/phplibs/layout.lib.php');
require_once('lib/phplibs/html.lib.php');

// Create an HTML Page
$GLOBALS['html'] = new HTMLDoc();
$html = $GLOBALS['html'];

// Set title
$html->title = Config::get('site_title');

// Add javascript
$html->add_ref_js(rpath('/js/jquery.js'));
$html->add_ref_js(rpath('/js/jeditable.js'));

// Add theme
$html->add_ref_css(rpath('/themes/default/layout.css') . ((Config::get('css_anticache'))?'?'.rand():''));
$html->add_ref_css(rpath('/themes/default/blue.css') . ((Config::get('css_anticache'))?'?'.rand():''));

// Layouts
$GLOBALS['layout'] = new Layout();
$layout = $GLOBALS['layout'];
    
// NAVIGATION
function layout_create_navigation()
{   global $sel_menu;

    if (!isset($sel_menu)) $sel_menu = 'home';
	echo '<span id="site-title">' . Config::get('site_title') . '</span>';
	echo '<div class="ui-menu">';
    echo '<a href="' . rpath('/') . '"><span class="ui-clickable' . (($sel_menu == 'home')?' ui-selected':'') . '">Home</span></a>';
	echo '<a href="' . rpath('/section1') . '"><span class="ui-clickable' . (($sel_menu == 'section1')?' ui-selected':'') . '">Section 1</span></a>';
	echo '<a href="' . rpath('/section2') . '"><span class="ui-clickable' . (($sel_menu == 'section2')?' ui-selected':'') . '">Section 2</span></a>';
	if (Group::open('admin')->has_current_user())
		echo '<a href="' . rpath('/manage.php') . '"><span class="ui-clickable ui-hot' . (($sel_menu == 'manage')?' ui-selected':'') . '">Settings</span></a>';	
    echo '</div>';
    
    
    $lgdiv = tag('div id="login-panel"');
    if (WAAS::current_user_is_anon())
    	$lgdiv->append(
    		tag('a', array('href' => rpath("/register.php")), 'Register'),
    		' / ',
    		tag('a', array('href' => rpath($_SERVER['PATH_INFO']. '/+login')), 'Login')
    	);
    else
    	$lgdiv->append('[ ' . WAAS::current_user()->username . ' ]',
    		tag('a', array('href' => rpath($_SERVER['PATH_INFO']. '/+logout')), 'logout')
    	);
    echo $lgdiv;

/*
    echo '<div id="search-panel">';
    echo '<form action="search.php"><input type="text" name="query"></form>';
    echo '</div>';
  */
}
$layout->s('navigation')->set_render_func('layout_create_navigation');

// MAIN SECTION
$layout->s('main');

// FOOTER
function layout_create_footer()
{	benchmark::checkpoint('end');
	//benchmark::html_dump();
	
	etag('span class="copyrights"', 'Copyright (C) 2009 ',
		tag('a', array('href' => rpath('/')), Config::get('site_title'))
	);
	etag('br'); 
	etag('span class="ui-process-time"', sprintf('%4.3f secs, %4.2f/%4.1f MB memory',
		benchmark::elapsed('start','end', 0),
		memory_get_peak_usage(true)/1048576, ini_get('memory_limit'))
	);
	if (Config::get('google_analytics'))
		ga_code(Config::get('google_analytics'));
}
$layout->s('footer')->set_render_func('layout_create_footer');

// Switch to main section rednering
$layout->s('main')->get_from_ob();

// Auto renderer
class auto_render
{   public function __destruct()
    {   $GLOBALS['html']->append_data($GLOBALS['layout']->render());
    	echo $GLOBALS['html']->render(); 
    }
}
$GLOBALS['ar'] = new auto_render();
?>
