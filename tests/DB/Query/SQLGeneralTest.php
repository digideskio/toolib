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
require_once __DIR__ .  '/../../path.inc.php';
require_once __DIR__ .  '/../SampleModels.inc.php';
require_once __DIR__ .  '/../SampleSchema.class.php';

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
        DB_Conn::disconnect();
    }

    public function testDefaultModelQueryInfo()
    {
        $mq = Forum::raw_query();
        $this->assertType('DB_ModelQuery', $mq);
        $this->assertEquals($mq->type(), null);
    }
    
    /**
     * @expectedException RuntimeException
     */
    public function testEmptyType()
    {
        $mq = Forum::raw_query();
        $mq->sql();
    }
    
   
    public function dataSameSqlAndHash()
    {   Post::model();
        return array(
            // Same query diferent literal values
            array(  // SELECT
                Thread::raw_query()
                ->select(Thread::model()->fields())
                ->left_join('Post', 'id', 'thread_id')
                ->where('l.post like ?', 'not')
                ->where_in('l.id', array(1,2,3,4,5), 'OR')
                ,
                Thread::raw_query()
                ->select(Thread::model()->fields())
                ->left_join('Post', 'id', 'thread_id')
                ->where('l.post like ?', 'not')
                ->where_in('l.id', array('mak','mok','tr','gfd','asdf'), 'OR')
            ),
            array(  // INSERT
                Thread::raw_query()
                ->insert(Thread::model()->fields())
                ->values_array(array(1,2,3,4))
                ->values('a','fd','sdf','sf')
                ,
                Thread::raw_query()
                ->insert(Thread::model()->fields())
                ->values('a','fd','sdf','sf')
                ->values_array(array('64356345','fd','sdf','sf'))
            ),
            array(  // UPDATE
                Post::raw_query()
                ->update()
                ->set('post')
                ,
                Post::raw_query()
                ->update()
                ->set('post', 'asdfasdfasdf')
            ),
            // Same query different case in operators
            array(  // SELECT
                Thread::raw_query()
                ->select(Thread::model()->fields())
                ->left_join('Post', 'id', 'thread_id')
                ->where('l.post LiKe ?', 'NoT')
                ->where_in('l.id', array(1,2,3,4,5), 'Or')
                ,
                Thread::raw_query()
                ->select(Thread::model()->fields())
                ->left_join('Post', 'id', 'thread_id')
                ->where('l.post LiKe ?', 'not')
                ->where_in('l.id', array('mak','mok','tr','gfd','asdf'), 'OR')
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
?>
