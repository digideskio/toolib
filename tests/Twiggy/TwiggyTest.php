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

namespace toolib\tests\Twiggy;

use toolib\Twiggy;

require_once __DIR__ .  '/../path.inc.php';

class TwiggyTest extends \PHPUnit_Framework_TestCase
{
	
	/*
	 * Various test cases
	 */
	public function cases()
	{
		return array(
			array('00-compilation.html.twiggy', '00.out-1.html', array()),
			array('01-simple.html.twiggy', '01.out-1.html', array()),
			array('02-variables.html.twiggy', '02.out-1.html',
				array('title' => 'Very nice title', 'contents' => 'content is not that big')),
			array('03-control.html.twiggy', '03.out-1.html',
				array('title' => 'Very nice title', 
					'item' => array('Item name 1','Item name 2','Item name 3','Item name 4','Item name 5'))
			),
			array('04-blocks.html.twiggy', '04.out-1.html',
				array('item' => array('Item name 1','Item name 2','Item name 3','Item name 4','Item name 5'))
			),
			array('04-blocks.html.twiggy', '04.out-1.html',
				array('title' => 'Nice title', 'contents' => 'Nice content list',
				'item' => array('Item name 1','Item name 2','Item name 3','Item name 4','Item name 5'))
			),
			array('05-inheritance.html.twiggy', '05.out-1.html',
				array('item' => array('Item name 1','Item name 2','Item name 3','Item name 4','Item name 5'),
					'content_is' => 'besty', 'site_name' => "Name")
			),
			array('06-include.html.twiggy', '06.out-1.html',
				array('page' => 'BarFoo')
			),
			array('07-multi-include.html.twiggy', '07.out-1.html', array()),
			);
	}
	
	/**
	 * @dataProvider cases
	 */
	public function testGeneration($tpl, $expected_output_file, $enviroment)
	{
		// Startup with default settings
		Twiggy::initialize(array(__DIR__ . '/tests'));
		
		$expected_output = file_get_contents(__DIR__ . '/tests/' . $expected_output_file);
		$output = Twiggy::open($tpl)->render($enviroment);
		
		$this->assertEquals($expected_output, $output);
	}

}
