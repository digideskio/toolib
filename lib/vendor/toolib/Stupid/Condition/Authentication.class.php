<?php
/*
 *  This file is part of PHPLibs <http://phplibs.kmfa.net/>.
 *  
 *  Copyright (c) 2010 < squarious at gmail dot com > .
 *  
 *  PHPLibs is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *  
 *  PHPLibs is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License
 *  along with PHPLibs.  If not, see <http://www.gnu.org/licenses/>.
 *  
 */

namespace toolib\Stupid\Condition;

require_once __DIR__ . '/../Condition.class.php';

//! Implementation of auth Stupid_Condition
/**
 * A condition evaluator that can perform checks on the
 * Authn_Realm
 * This evaluator implements the <b> type = "authn"</b> 
 *
 * @par Acceptable condition options
 * - @b op [Default = isuser]: isanon, isuser
 * - @b user: The corresponding user for operands that need to define a user.
 * .
 * 
 * @par Examples
 * @code
 * // This action is accesible only from user root
 * Stupid::addRule('create_news',
 *     array('type' => 'url_path', 'path' => '/\/news\/\+create/'),
 *     array('type' => 'authn', 'op' => 'isuser', 'user' => 'root'));
 * @endcode
 */
class Authentication extends \toolib\Stupid\Condition
{
	public static function type()
	{
		return 'authn';
	}

	public function evaluateImpl($previous_backrefs)
	{
		// Default condition values
		$defcond = array(
			'op' => 'isuser',
			'user' => ''
		);
		$options = array_merge($defcond, $this->options);

		// Per operand
		switch($options['op']){
		case 'isanon';
			return ! Authn_Realm::hasIdentity();
		case 'isuser':
			return ((Authn_Realm::hasIdentity()) && (Authn_Realm::getIdentity() == $options['user']));
		}
	}
}
Authentication::register();
