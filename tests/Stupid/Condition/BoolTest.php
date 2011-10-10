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


use toolib\Http;
use toolib\Stupid\Knowledge;
use toolib\Stupid\Condition;
require_once __DIR__ .  '/../../path.inc.php';

class Stupid_BoolTest extends PHPUnit_Framework_TestCase
{

	public function testOperands()
	{
		$k = new Knowledge();
		$valid = function() { return true; };
		$invalid = function() { return false; };
		
		// AND
		$c = Condition\Bool::opAnd($invalid);
		$this->assertFalse($c($k));
		$c = Condition\Bool::opAnd($valid);
		$this->assertTrue($c($k));
		$c = Condition\Bool::opAnd($valid, $valid);
		$this->assertTrue($c($k));
		$c = Condition\Bool::opAnd($valid, $invalid);
		$this->assertFalse($c($k));
		$c = Condition\Bool::opAnd($valid, $invalid, $valid);
		$this->assertFalse($c($k));
		
		// Or
		$c = Condition\Bool::opOr($invalid);
		$this->assertFalse($c($k));
		$c = Condition\Bool::opOr($valid);
		$this->assertTrue($c($k));
		$c = Condition\Bool::opOr($valid, $valid);
		$this->assertTrue($c($k));
		$c = Condition\Bool::opOr($valid, $invalid);
		$this->assertTrue($c($k));
		$c = Condition\Bool::opOr($valid, $invalid, $valid);
		$this->assertTrue($c($k));
		$c = Condition\Bool::opOr($invalid, $invalid, $invalid);
		$this->assertFalse($c($k));
		
		// Not
		$c = Condition\Bool::opNot($invalid);
		$this->assertTrue($c($k));
		$c = Condition\Bool::opNot($valid);
		$this->assertFalse($c($k));		
	}
	
}
