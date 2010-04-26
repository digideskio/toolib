<?php

require_once(dirname(__FILE__) . '/../Condition.class.php');

//! Implementation of auth Stupid_Condition
/**
 * A condition evaluator that can perform checks on the
 * WAAS and Group based on current logged on user.\n
 * This evaluator implements the <b> type = "auth"</b> 
 *
 * @par Acceptable condition options
 * - @b op [Default = equal]: ingroup, isanon, isuser
 * - @b group: The corresponding group for operands that need to define a group.
 * - @b user: The corresponding user for operands that need to define a user.
 * .
 * 
 * @par Examples
 * @code
 * // This action is accesible only from users of group admin
 * Stupid::add_rule('create_news',
 *     array('type' => 'url_path', 'path' => '/\/news\/\+create/'),
 *     array('type' => 'auth', 'op' => 'ingroup', 'group' => 'admin'));
 * @endcode
 */
class Stupid_Condition_Authentication extends Stupid_Condition
{
	public static function type()
	{	return 'auth';	}

	public function evaluate_impl($previous_backrefs)
	{
		// Default condition values
		$defcond = array(
			'op' => 'ingroup', 'isanon', 'isuser',
			'group' => '',
			'user' => ''
		);
		$options = array_merge($defcond, $this->options);

		// Per operand
		switch($options['op']){
		case 'ingroup':
			if (($gp = Group::open($options['group'])) === false)
				return false;
			return $gp->has_current_user();
		case 'isanon';
			return WAAS::current_user_is_anon();
		case 'isuser':
			return ((!WAAS::current_user_is_anon()) && (Waas::current_user()->username == $options['user']));
		}
	}
}
AuthenticationCondition::register();

?>
