<?php
require_once('layout.inc.php');
require_once($GS_libs . 'lib/form.lib.php');

// Logout user if there is someone logged on
if (get_is_equal('logout', 'yes'))
	WAAS::logout();
	
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
				array('title' => $GS_site_title . ' Login', 'css' => array('ui-login'),
					'buttons' => array('login' => array('display' =>'Login'))
				)
			);
		}
		public function on_post()
		{
			$user = $this->get_field_value('login-user');
	        $pass = $this->get_field_value('login-pass');
	        if (WAAS::login($user, $pass))
	        	redirect(rpath('/index.php'));
	        else
	        {
	        	$this->invalidate_field('login-pass', 'The username or password you entered is incorrect.');
	        }
		}
	};
	new LoginForm();
    $layout->stop_capturing_ob();
}
else
	redirect(rpath('/index.php'));
    
?>
