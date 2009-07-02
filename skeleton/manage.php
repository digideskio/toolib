<?php
require_once('layout.inc.php');
require_once($GS_libs . 'lib/grid.lib.php');
require_once($GS_libs . 'lib/form.lib.php');
$sel_menu = 'manage';

function print_ideletable($what, $on_delete, $return_result = false)
{	static $del_id = 0;
	$myid = $del_id++;
	$ret_data = '<div class="ui-idelete" id="idelete_' . $myid . '">' .
		'<img src="images/cleardot.gif" onclick="' .
		'$(\'span.ui-idelete-button\').fadeOut(\'fast\');' .
		'if ($(\'#idelete_' . $myid . '.ui-delete img\').hasClass(\'opened\'))' .
		'	$(\'#ideletebutton_' . $myid . '\').fadeOut();	else' .
		'	$(\'#ideletebutton_' . $myid . '\').fadeIn();' .

		'$(\'#idelete_' . $myid . '.ui-idelete img\').toggleClass(\'opened\');">' .
		$what .
		'<span id="ideletebutton_' . $myid . '" class="ui-idelete-button" onclick="window.location=\'' .
		esc_js($on_delete) . '\'">Delete</span></div>';
	
	if ($return_result)
		return $ret_data;
	echo $ret_data;
	return true;
}

function print_iedit($return_result = false)
{	$ret_data = '<span class="ui-iedit" onclick="$(\'span.ui-idelete-button\').hide();' .
		'$(\'.ui-idelete img\').animate({\'opacity\' : \'toggle\'}).removeClass(\'opened\');' .
		'">(edit)</span>' ;
	if ($return_result)
		return $ret_data;
	echo $ret_data;
	return true;
}

// Authentication check
if (!Group::open('admin')->has_current_user())
	redirect('index.php');

$layout->s('main')->s('main-base')->get_from_ob();

//// CALCULATE PARAMETERS ////

// Check if there is a group selected
if ( (isset($_REQUEST['group']))  && (($group = Group::open($_REQUEST['group']) ) !== false)){
	$layout->s('main')->s('main-base')->add_attrib('class', 'groupframe');
	echo '<div class="ui-search-filter">Users of group <span class="ui-search-filter-keyword">' .$group->name . '</span></div>';
}
else
	$group = false;
	
// Check if there is a user parameter
if (!isset($_REQUEST['user']) || (($user = User::open($_REQUEST['user'])) === false))
	$user = false;

// Check action
if (isset($_REQUEST['action']))	$action = $_REQUEST['action']; else $action = false;


switch($action)
{
	case 'partgroup':
		if ((!$group) || (!$user)) break;
		$group->remove_member($user->username);
		redirect('manage.php?user=' . $user->username);
		break;
	case 'deletegroup':
		$group->delete();
		redirect('manage.php');
		break;
	default:
}

// Show groups
if (!$group)
	$users = Waas::all_users();
else
	$users = $group->members();
if (count($users) == 0)
	echo '<h4>No users found!</h4>';
else
{
	class UsersGrid extends Grid
	{
		public function __construct()
		{	
			Grid::__construct(
				array(
					'count' => array('caption' => '#', 'customdata' => true,
						'htmlattribs' => array('width' => '25px'), 
						'clickable' => true),
					'username' => array('caption' => 'Username', 'clickable' => true),
					'memberof' => array('caption' => 'Member of', 'customdata' => true, 'clickable' => true),
					'is_enabled' => array('caption' => 'Enabled', 'mangle' => true,
						'htmlattribs' => array('width' => '25px'), 'clickable' => true)
				),
				array(
					'css' => array('ui-grid', 'userlist'),
					'maxperpage' => '75'
				),
				$GLOBALS['users']
			);
		}
		
		public function on_mangle_data($col_id, $row_id, $data)
		{	
			if ($col_id == 'is_enabled')
				return '<input type="checkbox" ' . (($data == '1')?'checked':'') . ' disabled >';
		}
	
		public function on_custom_data($col_id, $row_id, $record)
		{
			if ($col_id == 'count')
				return $row_id + 1;
			else if ($col_id == 'memberof')
			{	$groups = Group::open_memberships($record->username);
				$cell = '';
				foreach($groups as $group)
				{
					if ($cell != '') $cell .= ', ';
					$cell .= $group->name;
				}
				return ($cell != '')?$cell:'&nbsp;';
			}
		}
		
		public function on_click($col_id, $row_id, $record)
		{	redirect('manage.php?user=' . $record->username .
				(($GLOBALS['group'])?'&group=' .$GLOBALS['group']->name:'')
			);	
		}
	}
	new UsersGrid();
}

// User Information
if ($user)
{
$layout->s('main')->s('user-info')->add_attrib('class', 'ui-user-info')->get_from_ob();
	echo '<h1> User: ' .esc_html($user->username) . '</h1>';
	class UserEditForm extends Form
	{
		public function __construct()
		{
			Form::__construct(
				array(
					'username' => array('display' => 'Username: ', 
						'value' => $GLOBALS['user']->username, 'htmlattribs' => array('disabled' => 'disabled')),
					'enabled' => array('display' => 'Enabled: ', 'type' => 'checkbox', 
						'value' => $GLOBALS['user']->is_enabled),
				),
				array(
					'buttons' => array('save' => array('display' => 'Update')),
					'css' => array('ui-user-edit-form'))
			);
		}
		
		public function on_valid()
		{	$GLOBALS['user']->is_enabled = $this->get_field_value('enabled');
			$GLOBALS['user']->save_object();
			redirect('manage.php?user=' . $GLOBALS['user']->username );
		}
	}
	new UserEditForm;
	
	// Add groups of user
	echo '<h2>Group membership</h2>';
	class JoinGroupForm extends Form
	{	
		public function __construct()
		{	$group_list = array();
			$all_groups = Group::open_all();
			foreach($all_groups as $group)
				if (!$group->has_user($GLOBALS['user']->username))
					$group_list[$group->name] = $group->name;
			Form::__construct(
				array(
					'other' => array('display' => 'join other group: ', 'type' => 'dropbox', 
						'optionlist' => $group_list, 'mustselect' => true),
				),
				array(
					'buttons' => array('join' => array('display' => 'Join')),
					'renderonconstruct' => false,
					'css' => array('ui-user-form')
				)
			);

			// Hide form			
			if (count($group_list) == 0)
				$this->hide();
		}
		
		public function on_valid()
		{	$group = Group::open($this->get_field_value('other'));
			$group->add_member($GLOBALS['user']->username);
			redirect('manage.php?user=' .$GLOBALS['user']->username );
		}
			
	}
	$jgf = new JoinGroupForm;
	$user_groups =  Group::open_memberships($user->username);
	if (count($user_groups) > 0)
		echo 'Member of: ';
	foreach($user_groups as $group)
		echo '<span class="ui-search-filter-keyword">' . esc_html($group->name) . '<sup>' .
			'<a onclick="return confirm(\'Are you sure you want to part this group?\')" ' .
			'href="manage.php?user='  .$user->username . '&action=partgroup&group=' .$group->name . '">(x)</a></sup></span> ';
			
	$jgf->render();
}
$layout->s('main')->s('main-widgets')->get_from_ob();

// Groups box
$groups = Group::open_all();
echo '<div class="ui-widget"><h1>Groups  ' . print_iedit(true) . '</h1><ul class="ui-menu-list">';
foreach($groups as $group)
	echo '<li>' .
		print_ideletable( '<a href="manage.php?group=' . esc_html($group->name) .'">' . 
			esc_html($group->name) . '</a> (' .	$group->count_members() . ')' ,
			'manage.php?action=deletegroup&group=' . esc_html($group->name),
			true			
		) .
		'</li>';
echo '<li><a href="manage.php">(all)</a></li>';
echo '</ul>';
class AddGroupForm extends Form
{
	public function __construct()
	{
		Form::__construct(
			array(
				'group' => array('display' => '', 'regcheck' => '/^[0-9a-zA-Z\-_]+$/', 'onerror' => 'Write a valid groupname')
			),
			array(
				'buttons' => array('Add' => array()	),
				'css' => array()
			)
		);
	}
		
	public function on_valid()
	{	if (Group::open($this->get_field_value('group')) !== false)
		{	$this->invalidate_field('group', 'This group already exists.');
			return;
		}
		
		if (($group = Group::create($this->get_field_value('group'))) !== false)
			redirect('manage.php');
		var_dump($group);
		
		$this->invalidate_field('group', 'Cannot create group');
		
	}

}
new AddGroupForm();
echo '</div>';

?>