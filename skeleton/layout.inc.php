<?php
$start_time = microtime(true);

require_once('init.inc.php');
require_once($GS_libs . 'lib/layout.lib.php');
require_once($GS_libs . 'lib/html.lib.php');

// Create an HTML Page
$html = new HTMLPage();

// Set title
$html->title = ".: " . $GS_site_title . " :.";

// Add javascript
$html->add_ref_js(rpath('/js/jquery.js'));

// Add theme
$html->add_ref_css(rpath('/themes/default/layout.css') . (($GS_css_anticache)?'?'.rand():''));
$html->add_ref_css(rpath('/themes/default/blue.css') . (($GS_css_anticache)?'?'.rand():''));

// Layouts
$layout = new Layout();
    
// NAVIGATION
function layout_create_navigation()
{   global $GS_site_title, $sel_menu;

    if (!isset($sel_menu)) $sel_menu = 'home';
	echo '<span id="site-title">'.$GS_site_title.'</span>';
	echo '<div class="ui-menu">';
    echo '<a href="' . rpath('/') . '"><span class="ui-clickable' . (($sel_menu == 'home')?' ui-selected':'') . '">Home</span></a>';
    echo '<a href="' . rpath('/section1.php') . '"><span class="ui-clickable' . (($sel_menu == 'section1')?' ui-selected':'') . '">Section 1</span></a>';
    echo '<a href="' . rpath('/section2.php') . '"><span class="ui-clickable' . (($sel_menu == 'section2')?' ui-selected':'') . '">Section 2</span></a>';
   	if (Group::open('admin')->has_current_user())
		echo '<a href="' . rpath('/manage.php') . '"><span class="ui-clickable ui-hot' . (($sel_menu == 'manage')?' ui-selected':'') . '">Settings</span></a>';	

    echo '</div>';

    echo '<div id="login-panel">';
    if (WAAS::current_user_is_anon())
        echo a('/register.php', 'Register') .' / ' . a('/login.php', 'Login');
    else
        echo '[ ' . esc_html(WAAS::current_user()->username) . ' ] ' . a('/login.php?logout=yes', 'logout');
    echo '</div>';
}
$layout->s('navigation')->set_render_func('layout_create_navigation');

// MAIN SECTION
$layout->s('main');

// Footer
function layout_create_footer()
{
	echo 'Copyright (C) 2009 ' . a('/', $GLOBALS['GS_site_title'] );
	printf(' <br>Built on <a href="http://phplibs.kmfa.net">PHPLibs</a><br><span class="ui-process-time">%4.3f secs</span>', (microtime(true) - $GLOBALS['start_time']));
	if (isset($GLOBALS['GS_ga']))
		ga_code($GLOBALS['GS_ga']);
}
$layout->s('footer')->set_render_func('layout_create_footer');

// Switch to main section rednering
$layout->s('main')->get_from_ob();

// Auto renderer
class auto_render
{   public function __destruct()
    {   global $layout, $html;
        $html->append_data($layout->render());
        echo $html->render(); 
    }
}
$ar = new auto_render();
?>
