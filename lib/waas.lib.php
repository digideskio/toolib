<?php
require_once('singleton.lib.php');
require_once('events.lib.php');
require_once('mysqli.lib.php');

// Prepare the needed statements for waas
dbconn::prepare('user-validate', 'SELECT COUNT(*) FROM users WHERE is_enabled != 0 AND user=? AND password=MD5(?)');
dbconn::prepare('user-info', 'SELECT user, is_enabled FROM users WHERE user = ? LIMIT 1'); 
dbconn::prepare('user-resetpwd', 'UPDATE users SET password=MD5(?) WHERE user=? LIMIT 1');
dbconn::prepare('user-create', 'INSERT INTO users (user, password) VALUES(?, MD5(?))');
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
	private function __construct(){}

	//! Add a new user to the database
	/** 
	   It will try to create a new user in the database.
	@return A User object of the new user or false in case of error.
	*/
	public static function create($username, $password)
	{    if (!$stm = dbconn::execute('user-create', 'ss', $username, $password))
			return false;
			
		return User::open($username);
	}
    //! Get a user object of an existing user
	/** 
	    @return A User object on success. Or false on error.
	*/	   
	public static function open($username)
	{
		$user = dbconn::execute_fetch_all('user-info', 's', $username);
        if (count($user) != 1)
            return false;

        // Create user object            
	    $u =  new User();
		$u->username = $user[0]['user'];
		$u->is_enabled = $user[0]['is_enabled'];
		return $u;
	}
	
	//! Reset password of user
	public function reset_password($new_password)
	{	dbconn::execute('user-resetpwd', 'ss', $new_password, $username);
		return true;
	}
	
	//! Save changes to the database
	public function save_object()
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
    A static IntraSessionSingleton object that implements the authentication
    system of a web application. It supports user management and
    authentication.
    
    @note Waas requires that you have properly started a php session. This can
    be done by calling session_start() at the begining of each page.
*/  
class Waas extends IntraSessionSingleton
{
    //! Current logged on user
	private $m_current_user;

    //! An observation point for actions on Waas
    private $events;
    
	//! Called automatically when waas object is constructed.
	public function __construct()
	{
	    $this->events = new EventDispatcher(array(
	        'pre-login',
	        'post-login',
	        'pre-logout',
	        'post-logout'
	        ));
	}
	
	//! Get the EventDispatcher object to observe events
	/** 
	    Waas currently exports the following events
        - @b pre-login(username, password) : Called when a login request is done and before the 
	        validation of the credentials.
        - @b post-login(username, password) : Called when a login request is done and the credentials
	        were validated successfuly.
        - @b pre-logout(): Called when a user asks to logout just before the actual
            logout is done.
        - @b post-logout(): Called after a user has been logged out succesfully.
        .
    */
	static public function events()
	{    return self::get_instance()->events;    }
	
	
    //! Returns the pointer to the singleton, and creates it if it is not created yet.
	static public function get_instance()
	{	return self::get_class_instance(__CLASS__);	}
	
	//! Login a user to the current session.
	/** 
	   @note login() will try to change cookies, and it is proposed
	       to execute this before sending any real data to the user.
	       
	   It will try to validate the credentials and if they are
	   ok, the user will be logged on in the current session.

	*/
	static public function login($user, $pass)
	{	// Get singleton instance
		$pthis = self::get_instance();
		
		// Logout any previous user
		if (!waas::current_user_is_anon())
		      waas::logout();

		// Raise event pre-login
		$pthis->events->notify('pre-login');

		// Check for users with that username and that password.
		$count_records = dbconn::execute_fetch_all('user-validate', 'ss', $user, $pass);
		if ($count_records[0][0] != 1)
		      return false;

		// Retrieve and save detailed info of user
		$pthis->m_current_user = User::open($user);
		
		// Regenerate session id to prevent session fixation
		session_regenerate_id(true);
		
		// Raise event post-login
		$pthis->events->notify('post-login');		
		return true;
	}
	
	//! Logout current user
	/** 
	@note logout() will try to change cookies, and it is proposed
	   to execute this before sending any real data to the user.
    */
	static public function logout()
	{   // Get singleton instance
		$pthis = self::get_instance();

        // Skip if it is already logged out
        if (self::current_user_is_anon())
            return false;
            
        // Raise event pre-logout
		$pthis->events->notify('pre-logout');
		
		// Unsect current user
		unset($pthis->m_current_user);
		
		// Raise event post-logout
		$pthis->events->notify('post-logout');
	}
	
	//! Get current logged-in user
	/** 
	   If no user is logged on, it will return false, otherwise it will return a User object of the
	   logged-on user.
	*/
	static public function current_user()
	{	// Get singleton instance
		$pthis = self::get_instance();
		
		if (isset($pthis->m_current_user))
			return $pthis->m_current_user;
		
		return false;
	}
	
	//! Check if current user is anonymous
	/** 
	   @remarks Anonymous is a non-loggon user.
	*/
	static public function current_user_is_anon()
	{	// Get singleton instance
		$pthis = self::get_instance();
		return (!isset($pthis->m_current_user));
	}
	
	//! Get the list of all users of the system
	/** 
	   It will return an array of User objects.
	 */
	static public function all_users()
	{	// Get singleton instance
		$pthis = self::get_instance();
		
		// Get the list with all users
        if (($usernames = dbconn::execute_fetch_all('user-all')) === false)
        	return false;
        	
		$users = array();
		foreach($usernames as $user)
			$users[] = User::open($user['user']);
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
		$pthis = self::get_instance();
		
		$res = dbconn::execute_fetch_all('user-countall');
		if (count($res) == 1)
            return $res[0][0];
		return false;
	}
};

?>