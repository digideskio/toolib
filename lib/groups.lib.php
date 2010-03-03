<?php
require_once('db/dbrecord.lib.php');

dbconn::prepare('groups-hasuser', 'SELECT * FROM group_members WHERE groupname=? AND username = ?');
dbconn::prepare('groups-members', 'SELECT username FROM group_members WHERE groupname = ?');
dbconn::prepare('groups-countmembers', 'SELECT count(*) FROM group_members WHERE groupname = ?');
dbconn::prepare('groups-memberships', 'SELECT groupname FROM group_members WHERE username = ?');
dbconn::prepare('groups-addmember', 'INSERT INTO group_members (groupname, username) VALUES(?, ?)');
dbconn::prepare('groups-removemember', 'DELETE FROM group_members WHERE groupname=? AND username=?');


//! Users grouping
/**
	Complete group support for users. You can create
	groups, add / remove members and query them. An
	easy and fast way from user organization to group
	based authorization.
	
	@todo Rename it to something that reminds it is a group of USERS!
*/
class Group extends DBRecord
{
	// Table is news
	public static $table = 'groups';
	
	// Fields
	public static $fields = array(
		'name' => array('pk' => true, 'sqlfield'=>'groupname'),
	);

	//! Checks if a specific user is member of a group
	/**
		@param $username The username of the user
		@return
			- @b true If the user is in this group
			- @b false In case of error or if the user is not in this group.
	*/
	public function has_user($username)
	{	if (($res_array = 
			dbconn::execute_fetch_all('groups-hasuser',
				'ss', $this->name, $username)) === false)
			return false;
			
		if (count($res_array) != 1)
			return false;
			
		return true;
	}
	
	//! Checks if the current logged on user is member of this group
	/**
		It is the same as has_user() but it checks for the current
		logged on uesr.
		
		@return
			- @b true If the current logged on user is member of this group
			- @b false If there is noone logged on or if the user is
				not member of this group.
			.
	*/
	public function has_current_user()
	{	if (WAAS::current_user_is_anon())
			return false;
			
		//
		return $this->has_user(WAAS::current_user()->username);
	}

	//! Add a new member in the group
	/**
		@param $username The username of the member to add in the group
		@return
			- @b false If there was an error, or the user already exists in the group.
			- @b true If the user became member of this group successfuly.
			.
	*/
	public function add_member($username)
	{	if ($this->has_user($username))
			return false;
			
		if (dbconn::execute('groups-addmember', 'ss', $this->name, $username) === false)
			return false;
			
		return true;
	}
	
	//! Remove member from group
	/**
		@param $username The username to remove from this group.
		@return
			- @b true If there was no error in the process
			- @b false if there was an error.
			.
		
		@remarks This function will return true even if the user does
		not exists in the group. 
	*/
	public function remove_member($username)
	{	if (!$this->has_user($username))
			return false;
			
		if (dbconn::execute('groups-removemember', 'ss', $this->name, $username) === false)
			return false;
			
		return true;
	}

	//! Count all members of this group
	/**
		@return The number of members that this group holds.
	*/
	public function count_members()
	{	if (($res_array = 
			dbconn::execute_fetch_all('groups-countmembers',
				's', $this->name)) === false)
			return false;
			
		if (count($res_array) != 1)
			return false;
			
		return $res_array[0][0];
	}
	
	//! Get all members of this group
	/**
		@return @b Array of User objects with all members of this group
	*/
	public function members()
	{	if (($res_array = 
			dbconn::execute_fetch_all('groups-members',
				's', $this->name)) === false)
			return false;
			
		$users = array();
		foreach($res_array as $res)
			$users[] = User::open($res['username']);
		return $users;
	}
		
	//! Get all groups that a user is belonging to
	/**
		It will return all groups that a user is member to.
		@param $username The username that you want to search for memberships.
		@return @b Array of all Group objects that the
			user belongs to.
	*/
	public static function open_memberships($username)
	{	if (($res_array = 
			dbconn::execute_fetch_all('groups-memberships',
				's', $username)) === false)
			return false;
			
		$groups = array();
		foreach($res_array as $res)
			$groups[] = Group::open($res['groupname']);
		return $groups;
	}
	
	
	// Used be DBRecord::delete() to perform extra actions.
	public function on_delete()
	{	$members = $this->members();
	
		foreach($members as $member)
			$this->remove_member($member->username);
		return true;
	}

}

?>