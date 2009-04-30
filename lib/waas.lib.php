<?php
require_once('singleton.lib.php');
require_once('mysqli.lib.php');

// Prepare the needed statements for waas
dbconn::prepare('user-validate', 'SELECT COUNT(*) FROM users WHERE is_enabled != 0 AND user=? AND password=MD5(?)');
dbconn::prepare('user-info', 'SELECT user, is_enabled FROM users WHERE user = ? LIMIT 1'); 
dbconn::prepare('user-resetpwd', 'UPDATE users SET password=MD5(?) WHERE user=? LIMIT 1');
dbconn::prepare('user-add', 'INSERT INTO users (user, password) VALUES(?, MD5(?))');
dbconn::prepare('user-all', 'SELECT user, is_enabled FROM users');
dbconn::prepare('user-countall', 'SELECT user, is_enabled FROM users');
dbconn::prepare('user-delete', 'DELETE FROM users WHERE user = ? LIMIT 1');
dbconn::prepare('user-update', 'UPDATE users SET is_enabled = ? WHERE user = ? LIMIT 1');
dbconn::prepare('user-changepwd', 'UPDATE users SET password=MD5(?) WHERE user = ? AND password=MD5(?)');

//! User handler
class User
{
	//! Username of the user (or login id)
	public $username;
	
	//! A flag if user is enabled
	public $is_enabled;

	//! A user handler object
	/**
		User objects are created ONLY by WAAS. Don't try to create yours.
	*/
	public function __construct($username, $is_enabled = NULL)
	{
		// Save data
		$this->username = $username;
		$this->is_enabled = $is_enabled;
    }
	
	//! Reset password of user
	public function reset_password($new_password)
	{	dbconn::execute('user-resetpwd', 'ss', $new_password, $username);
		return true;
	}
	
	//! Save changes to the database
	public function save_changes()
	{	// Update database
		dbconn::execute('user-update', 'ss', $this->is_enabled, $this->username);
	}
	
	//! Change the password of this user by validating the old one.
	/** It is like reset_password() but it will do it only after a successful validation
	   of the current one
	*/
	public function change_password($current, $new)
	{
	    // Update database
		if (!($stm = dbconn::execute('user-changepwd', 'sss', $new, $this->username, $current)))
		  return false;
		  
	    if ($stm->affected_rows != 1)
	       return false;
	    return true;
	}
};

//! Web Application Authentication System definition
/**
    A static singleton object that implements the authentication
    system of a web application. It supports user management,
    authentication, session handling, and autoredirection after login.
*/  
class waas extends singleton
{
	//! Action hooks
	private $hooks;
	
	//! Current logged on user
	private $m_current_user;
	
	//! Login redirect url
	private $login_redirect_url;
	
	//! Called automatically when waas object is constructed.
	public function __construct()
	{
	    $this->hooks = array('pre-login' => false,
	        'post-login' => false,
	        'pre-logout' => false,
	        'post-logout' => false
	        );
	}
	
	//! It calls a hook with its parameters
	/**
	   1st parameter is the name of the hook. 
	   Nth parameter(s) are passed to the hook function
	*/
	private function call_hook()
	{
	    if (func_get_args() < 1)
	        return false;
	    
	    $key = func_get_arg(0);
	    if (! $this->hooks[$key])
	        return false;

	    if (func_get_args() == 1)
	    {
	        // Call the user function
	        return call_user_func($this->hooks[$key]);
	    }
	    else
	    {   // Call the user function with parameters
	        $args = array_slice(func_get_args(), 1);
	        return call_user_func_array($this->hooks[$key], $args);
	    }
    }
	
	//! Define a hook on event
	/**
	   @param $event The name of the hook.
    	   Supported hooks are
	       - @b pre-login ( Called when a login request is done and before the user gets validated )
	       - @b post-login ( Called after a user was logged on succesfully )
	       - @b pre-logout ( Called before a user gets logged out )
	       - @b post-logout ( Called after the completion of logout process. )
	       .
	   @param $callback The hook callback function. The format is the same
	       of general php callback function. (http://php.net/callback#language.types.callback)
	 */
	static public function set_hook($event, $callback)
	{   // Get singleton instance
		$pthis = self::get_my_instance();
	    if (!isset($pthis->hooks[$event]))
	    {
	        dbg::log('waas: tried to add hook for event '. $event, 'error');
	        return false;           // Wrong hook name
	    }
	    
	    $pthis->hooks[$event] = $callback;
	    return false;
	}
	
	//! Returns the pointer to the singleton, and creates it if it is not created yet.
	static private function get_my_instance()
	{	return self::get_instance(__CLASS__);	}
	
	//! Login a user to the current session.
	/**
	   @note login() will try to change cookies, and it is proposed
	       to execute this before sending any real data to the user.
	       
	   It will try to validate the credentials and if they are
	   ok, the user will be logged on in the current session.
	   
	   If an autoredirect is setup, this function will try to add
	   a "location" header with the redirection header and it will
	   stop the execution of the script.
	   @see set_login_autoredirect
	*/
	static public function login($user, $pass)
	{	// Get singleton instance
		$pthis = self::get_my_instance();
		
		// Logout any previous user
		if (!waas::current_user_is_anon())
		      waas::logout();

		// Call pre-login hook
		$pthis->call_hook('pre-login');

		// Check for users with that username and that password.
		$count_records = dbconn::execute_fetch_all('user-validate', 'ss', $user, $pass);
		if ($count_records[0][0] != 1)
		{     dbg::log('Failed to login');
		      return false;
	    }

		// Retrieve and save detailed info of user
		$pthis->m_current_user = $pthis->get_user($user);
		
		// Regenerate session id to prevent session fixation
		session_regenerate_id();
		
		// Call post-login hook
		$pthis->call_hook('post-login');
		
		// Visit saved url if any
		if (!empty($pthis->login_redirect_url))
		{
    	  header('Location: ' . $pthis->login_redirect_url);
	      $pthis->login_redirect_url = "";
	      exit;
		}
		
		return true;
	}
	
	//! Logout current user
	/**
	@note logout() will try to change cookies, and it is proposed
	       to execute this before sending any real data to the user.
    */
	static public function logout()
	{   // Get singleton instance
		$pthis = self::get_my_instance();

        // Call pre-logout hook
		$pthis->call_hook('pre-logout');
		
		// Unsect current user
		unset($pthis->m_current_user);
		
		// Call post-logout hook
		$pthis->call_hook('post-logout');
	}
	
	//! Change the autoredirect url to follow after login
	/**
	   Setting a valid url, will provoke waas to redirect to this url after the next login. This will happen
	   only one time and the url will be deleted. If you want to disable any previous url you can execute
	   this function with an empty string.
    */
	static public function set_login_autoredirect($url)
	{	// Get singleton instance
		$pthis = self::get_my_instance();
        $pthis->login_redirect_url = $url;
	}
	
	//! Get the current redirection url
	/**
	   @see set_login_autoredirect()
    */
	static public function get_login_autoredirect()
	{  $pthis = self::get_my_instance();
	   return$pthis->login_redirect_url;
	}
	
	//! Get current logged-in user
	/**
	   If no user is logged on, it will return false, otherwise it will return a User object of the
	   logged-on user.
	*/
	static public function current_user()
	{	// Get singleton instance
		$pthis = self::get_my_instance();
		
		if (isset($pthis->m_current_user))
			return $pthis->m_current_user;
		
		return false;
	}
	
	//! Check if current user is anonymous
	/**
	   Anonymous is a non-loggon user.
	*/
	static public function current_user_is_anon()
	{	// Get singleton instance
		$pthis = self::get_my_instance();
		return (!isset($pthis->m_current_user));
	}
	
	//! Get a handler of registered user
	/**
	   Itt will return a User object of the user with the supplied username.
	   If there is no user with that username the function will return false.
	*/	   
	static public function get_user($username)
	{	
	   $user = dbconn::execute_fetch_all('user-info', 's', $username);
	   if (count($user) == 1)
	       return new User($user[0]['user'], $user[0]['is_enabled']);
	   return false;
	}
	
	//! Add a new user to the database
	/**
	   It will try to register the new user in the database.
	@return A User object of the new user or false in case of error.
	*/
	static public function add_user($username, $password)
	{  
		if (!$stm = dbconn::execute('user-add', 'ss', $username, $password))
		{   dbg::log('error on creating user');
		    dbg::log($stm->error);
			return false;
	    }
			
		return waas::get_user($username);
	}
	
	//! Get the list of all users of the system
	/**
	   It will return an array of User objects.
	 */
	static public function all_users()
	{	// Get singleton instance
		$pthis = self::get_my_instance();
		
		// Get the list with all users
        $stm = dbconn::execute('user-all');
		$users = array();
		$stm->bind_result($username, $is_enabled);
		while($stm->fetch())
			$users[] = new User($username, $is_enabled);
			
		return $users;
	}
	
	//! Delete a user from the system
	/**
	   @return true if the user was removed or false
	   in any case of error.
    */
	static public function delete_user($username)
	{	if (!dbconn::execute('user-delete','s', $username))
			return false;
		return true;
	}
	
	//! Count all users
	static public function count_users()
	{	// Get singleton instance
		$pthis = self::get_my_instance();
		
		$res = dbconn::execute_fetch_all('user-countall');
		if (count($res) == 1)
            return $res[0][0];
		return false;
	}
};

?>
