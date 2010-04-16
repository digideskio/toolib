<?php
require_once('waas.lib.php');

// Prepare needed statements
dbconn::prepare('seslog-insert', 'INSERT INTO session_log (user, ip, user_agent, ts_start, ts_lastseen) VALUES(?, ?, ?, NOW(), NOW())');
dbconn::prepare('seslog-expire', 'UPDATE session_log SET expired = TRUE WHERE log_id = ?');
dbconn::prepare('seslog-touch', 'UPDATE session_log SET ts_lastseen = NOW() WHERE log_id =?');
dbconn::prepare('seslog-is_expired', 'SELECT expired FROM session_log WHERE log_id = ?');
dbconn::prepare('seslog-get_others', 'SELECT log_id, user, ip, user_agent, ts_start, ts_lastseen FROM session_log WHERE expired = false AND user=(select user from session_log where log_id = ?) AND log_id != ?');
dbconn::prepare('seslog-signout_others', 'UPDATE session_log SET expired = true WHERE expired = false AND user = ? AND log_id != ?');

//! Session loging and management system
/** 
    @note SessionLog is a IntraSessionSingleton and there is no need to
    create an object of it, all the API is exported in static functions.
    
    This module provides is an extension on waas and provides user session management. When a user
    is logged on the session is saved in database and can be monitored for activity.
    
    To use this module you need to make sure that this file is always included in your application and execute 
    @b sessionlog::touch_current() at the begining of the application (and after sesison_start).
    
    You can easily imlpement single singon by adding sessionlog::signout_myother_sessions() at the begining
    of application.
*/
class SessionLog extends IntraSessionSingleton 
{
    //! The id of the current session
    private $cur_session_id;
    
    //! Constructor (this is used automaticaly)
    public function __construct()
    {   // Unset the default session id
        $this->cur_session_id = false;
        
        // Observe events on the Waas object
        waas::events()->connect('post-login', array('SessionLog', 'create'));
        waas::events()->connect('post-logout', array('SessionLog', 'expire'));
    }
    
    //! Get the current instance of the object
	static private function get_instance()
	{	return self::get_class_instance(__CLASS__);    }
	
    //! Creates and switchs to a new user session.
    /** 
        This function is called automaticaly when a user
        logs on the system. There is no need to do it
        automatically.        
    */
    static public function create()
    {   $pthis = SessionLog::get_instance();
    
        // Check if there is a user logged on
        if (!($user = waas::current_user()))
            return false;
        
        // Add a new session in the log
        dbconn::execute('seslog-insert', 'sss', $user->username, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
        $pthis->cur_session_id = dbconn::last_insert_id();
    }
    
    //! Expire current session
    /** 
        Expire/destroy the current session of this user. The session is marked as expred in the database too.
        @note This function is called automatically when a user logs out through waas.
    */        
    static public function expire()
    {   $pthis = SessionLog::get_instance();
    
        if (SessionLog::is_session_open())
        {   // Mark session as expired
            dbconn::execute('seslog-expire', 's', $pthis->cur_session_id);
            
            // Remove variable
            $pthis->cur_session_id = false;
        }
    }
    
    //! Check if there is any session open in this connection
    static public function is_session_open()
    {   $pthis = SessionLog::get_instance();
    
        return ($pthis->cur_session_id != false) ;
    }
    
    //! Touch the session in this conneciton
    /** 
        If there is a user session opened its last_activity timestamp is touched in database
    */
    static public function touch_current()
    {   $pthis = SessionLog::get_instance();
        // Check if there is an open session and touch the last seen timestamp
        if (SessionLog::is_session_open())
        {
            // Check if it has been expired            
            $res = dbconn::execute_fetch_all('seslog-is_expired', 's', $pthis->cur_session_id);
            if ((count($res) == 1) && ($res[0][0]))
                return waas::logout();  // Must logout;
        
            // Touch timestamp
            dbconn::execute('seslog-touch', 's', $pthis->cur_session_id);
        }
    }
    
    //! Get all other active sessions of this user
    /** 
        It will query the database for active sessions of this users and will return all except the current one.
    */
    static public function myother_sessions()
    {   $pthis = SessionLog::get_instance();
    
        // Check if there is an open session
        if (!SessionLog::is_session_open())
            return false;
        
        return dbconn::execute_fetch_all('seslog-get_others', 'ss', $pthis->cur_session_id, $pthis->cur_session_id);
    }
    
    //! Sign out all other active sessions of this user
    /*
        It will signout all other sessions of this user. At the next action of the other sessions they will be logged
        out.
    */     
    static public function signout_myother_sessions()
    {   $pthis = SessionLog::get_instance();
    
        // Check if there is an open session
        if (!SessionLog::is_session_open())
            return false;
        
        dbconn::execute_fetch_all('seslog-signout_others', 'ss',  waas::current_user()->username, $pthis->cur_session_id);
    }
}
?>
