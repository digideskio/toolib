<?php
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
$html->add_ref_css(rpath('/themes/default/layout.css'));
$html->add_ref_css(rpath('/themes/default/blue.css'));
$html->add_ref_css(rpath('/themes/default/phplibs-extra.css'));

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
$layout->s('footer')->get_from_ob();
echo 'Copyright (C) 2009 ' . a('/', $GS_site_title );

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
