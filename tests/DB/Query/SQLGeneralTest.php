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

require_once __DIR__ .  '/../../path.inc.php';
require_once __DIR__ .  '/../SampleSchema.class.php';
require_once __DIR__ .  '/../SampleModels.inc.php';

use toolib\DB\Connection;

class Record_Query_SQLGeneralTest extends PHPUnit_Framework_TestCase
{

    public static function setUpBeforeClass()
    {
        SampleSchema::build();
    }

    public static function tearDownAfterClass()
    {   
        SampleSchema::destroy();
    }

    public function setUp()
    {   
        SampleSchema::connect();
    }
    public function tearDown()
    {
        Connection::disconnect();
    }

    public function testDefaultModelQueryInfo()
    {
        $mq = Forum::rawQuery();
        $this->assertType('toolib\DB\ModelQuery', $mq);
        $this->assertEquals($mq->type(), null);
    }
    
    /**
     * @expectedException RuntimeException
     */
    public function testEmptyType()
    {
        $mq = Forum::rawQuery();
        $mq->sql();
    }
    
   
    public function dataSameSqlAndHash()
    {   Post::model();
        return array(
            // Same query diferent literal values
            array(  // SELECT
                Thread::rawQuery()
                ->select(Thread::model()->fields())
                ->leftJoin('Post', 'id', 'thread_id')
                ->where('l.post like ?', 'not')
                ->whereIn('l.id', array(1,2,3,4,5), 'OR')
                ,
                Thread::rawQuery()
                ->select(Thread::model()->fields())
                ->leftJoin('Post', 'id', 'thread_id')
                ->where('l.post like ?', 'not')
                ->whereIn('l.id', array('mak','mok','tr','gfd','asdf'), 'OR')
            ),
            array(  // INSERT
                Thread::rawQuery()
                ->insert(Thread::model()->fields())
                ->valuesArray(array(1,2,3,4))
                ->values('a','fd','sdf','sf')
                ,
                Thread::rawQuery()
                ->insert(Thread::model()->fields())
                ->values('a','fd','sdf','sf')
                ->valuesArray(array('64356345','fd','sdf','sf'))
            ),
            array(  // UPDATE
                Post::rawQuery()
                ->update()
                ->set('post')
                ,
                Post::rawQuery()
                ->update()
                ->set('post', 'asdfasdfasdf')
            ),
            // Same query different case in operators
            array(  // SELECT
                Thread::rawQuery()
                ->select(Thread::model()->fields())
                ->leftJoin('Post', 'id', 'thread_id')
                ->where('l.post LiKe ?', 'NoT')
                ->whereIn('l.id', array(1,2,3,4,5), 'Or')
                ,
                Thread::rawQuery()
                ->select(Thread::model()->fields())
                ->leftJoin('Post', 'id', 'thread_id')
                ->where('l.post LiKe ?', 'not')
                ->whereIn('l.id', array('mak','mok','tr','gfd','asdf'), 'OR')
            ),
        );
    }
    
    /**
     * @dataProvider dataSameSqlAndHash
     */
    public function testSelectSameSqlAndHash($a, $b)
    {
        $this->assertEquals($a->sql(), $b->sql());
        $this->assertEquals($a->hash(), $b->hash());
    }
}

