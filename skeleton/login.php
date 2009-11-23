<?php
require_once('layout.inc.php');
require_once('lib/phplibs/form.lib.php');

// Clean up url from +login and +logout chunks
function clean_up_url()
{
	$postlogin_url = rpath('/');
	$path_chunks = explode('/', $_SERVER['PATH_INFO']);
	$path_chunks =  array_filter($path_chunks, create_function('$c', 'return (($c != "+login") && ($c != "+logout"));'));
	return implode('/', $path_chunks);
}
	        	
// Logout user if there is someone logged on
Stupid::add_rule(create_function('', 'WAAS::logout(); redirect(rpath(clean_up_url()));'),
	array('type' => 'url_path', 'chunk[-1]' => '/\+logout/'));
Stupid::chain_reaction();

// Login form
if (WAAS::current_user_is_anon())
{   $layout->s('main')->s('login')->get_from_ob();
	class LoginForm extends Form
	{
		public function __construct()
		{	global $GS_site_title;
			parent::__construct(array(
				'login-user' => array('display' => 'Username'),
				'login-pass' => array('display' => 'Password', 'type' => 'password'),
				'custom-txt' => array('type' => 'custom', 'value' => ('If you dont have an account, you can always ' . a('/register.php', 'create a new one') . '.') )
				),
				array('title' => $GS_site_title . ' Login', 'css' => array('ui-form','ui-login'),
				'buttons' => array('login' => array('display' =>'Login'))
				)
			);
		}
		public function on_post()
		{
			$user = $this->get_field_value('login-user');
	        $pass = $this->get_field_value('login-pass');
	        if (WAAS::login($user, $pass))
	        {	
	        	redirect(rpath(clean_up_url()));
	        }
	        else
	        	$this->invalidate_field('login-pass', 'The username or password you entered is incorrect.');
		}
	};
	new LoginForm();
    $layout->stop_capturing_ob();
}
else
	redirect(rpath('/'));
    
?>
