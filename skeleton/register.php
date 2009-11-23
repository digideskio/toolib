<?php
require_once('layout.inc.php');
require_once('lib/phplibs/form.lib.php');

// Create the registration form
class RegistrationForm extends Form
{
    public function __construct()
    {   parent::__construct(
            array(
                'register-username' => array('display' => 'Username', 'regcheck' => '/^[a-z0-9\._]{4,16}$/',
                    'onerror' => 'Valid characters are lower case letters (a-z), numbers (0-9), underscore (_) or dot (.) only. Valid length is at least 4 and maximum 16.'),
                'register-pass1' => array('display' => 'Password', 'type' => 'password', 'regcheck' => '/^.{6,255}$/', 
                    'onerror' => 'Password must be at least 6 letters long'),
                'register-pass2' => array('display' => 'Repeat Password', 'type' => 'password', 'hint' => 'Rewrite password'),
            ),
            array(
                'title' => 'Create Account',
                'css' => array('ui-form', 'ui-register'),
                'buttons' => array('login' => array('display' =>'Register'))
            )
        );
    }
    
    public function on_post()
    {               
        // Check password
        if ($this->is_field_valid('register-pass1') && 
            ($this->get_field_value('register-pass1') != $this->get_field_value('register-pass2')))
                $this->invalidate_field('register-pass2', 'Password must be the same to complete registration.');

        // Check if user exists
        if ($this->is_field_valid('register-username'))
           if (User::open($this->get_field_value('register-username')) != false)
                $this->invalidate_field('register-username', 'This username is already taken.');
    }
    
    public function on_valid()
    {
        $username = $this->get_field_value('register-username');
        $password = $this->get_field_value('register-pass1');

        if (!User::create($username, $password))
            $msg_username = '<span class="error-msg">Cannot create account.</span>';
        else
        {
            // Perform login and redirect at intro page
            WAAS::login($username, $password);
            redirect(rpath('/'));
        }
    }
}

// Show form
$layout->s('main')->get_from_ob();
$rf = new RegistrationForm();
?>
