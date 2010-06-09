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


require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) .  '/../../path.inc.php';
require_once dirname(__FILE__) .  '/../SampleSchema.class.php';
require_once dirname(__FILE__) .  '/../SampleModels.inc.php';

class Record_Query_SQLInsertTest extends PHPUnit_Framework_TestCase
{

    public static function setUpBeforeClass()
    {
        //SampleSchema::build();
    }

    public static function tearDownAfterClass()
    {   
        //SampleSchema::destroy();
    }

    public function setUp()
    {   
        //SampleSchema::connect();
    }
    public function tearDown()
    {
        //DB_Conn::disconnect();
    }
    
    public function testInsertValues()
    {
        $mq = Thread::raw_query();
        $mq->insert(Thread::model()->fields())
            ->values_array(array(1, 2, 'title', '2002-10-01'));
        $this->assertEquals('INSERT INTO `threads` (`id`, `forum_id`, `title`, `datetime`) ' .
            'VALUES (?, ?, ?, ?)', $mq->sql());

        $mq = Thread::raw_query();
        $mq->insert(array('id'))
            ->values_array(array(1))
            ->values(5)
            ->values(16);
        $this->assertEquals('INSERT INTO `threads` (`id`) ' .
            'VALUES (?) (?) (?)', $mq->sql());
            
        $mq = Thread::raw_query();
        $mq->insert(array('id'))
            ->values_array(array(1))
            ->values(5)
            ->values(16)
            ->order_by(20)  // Order must not take effect  
            ->limit(2);     // Limit must not take effect
        $this->assertEquals('INSERT INTO `threads` (`id`) ' .
            'VALUES (?) (?) (?)', $mq->sql());
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalid1Insert()
    {
        Thread::raw_query()
            ->insert(Thread::model()->fields())
            ->values_array(array(1,2,3));
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalid2Insert()
    {
        Thread::raw_query()
            ->insert(Thread::model()->fields())
            ->values(1,2,3);
    }
}
?>
