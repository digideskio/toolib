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
require_once __DIR__ .  '/../SampleSchema.class.php';
require_once __DIR__ .  '/../SampleModels.inc.php';

class Record_Query_SQLExecuteTest extends PHPUnit_Framework_TestCase
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
    
    public function testExecuteUpdate()
    {
        $mq = Thread::raw_query();
        $mq->update()
            ->set('title', 'new title');
        $res = $mq->execute();
        $rec = Thread::open(1);
        $this->assertType('Thread', $rec);
        $this->assertEquals('new title', $rec->title);


        $mq = Post::raw_query();
        $mq->update()
            ->set('post', 'test post updated')
            ->set('image');
        $res = $mq->execute(serialize('dokimi image'));
        $rec = Post::open(1);
        $this->assertType('Post', $rec);
        $this->assertEquals('test post updated', $rec->post);
        $this->assertEquals('dokimi image', $rec->image);

    }
    
    public function testExecuteInsert()
    {
        $mq = Post::raw_query();
        $mq->insert(array('image', 'post'))
            ->values(serialize('image1'), 'post1');
        $this->assertTrue($mq->execute() !== false);
        
        $rec = Post::open(DB_conn::last_insert_id());
        $this->assertType('Post', $rec);
        $this->assertEquals('image1', $rec->image);
        $this->assertEquals('post1', $rec->post);
        
        
        $mq = Post::raw_query();
        $mq->insert(array('image', 'post'))
            ->values_array(array(serialize('image2'), 'post2'));
        $this->assertTrue($mq->execute() !== false);
        
        $rec = Post::open(DB_conn::last_insert_id());
        $this->assertType('Post', $rec);
        $this->assertEquals('image2', $rec->image);
        $this->assertEquals('post2', $rec->post);

    }
    
    public function testExecuteSelectWhereInLiteral()
    {
       // Where_in with literal values
        $mq = Post::raw_query();
        $mq->select(array('id'))
            ->where_in('id', array(1,2))
            ->where('post like ?');
        $this->assertEquals("SELECT `id` FROM `posts` WHERE `id` IN (?, ?) AND `posted_text` LIKE ?", $mq->sql());
        $records = $mq->execute('%');
        $this->assertEquals(count($records), 2);
        $this->assertEquals($records[0]['id'], 1);
        $this->assertEquals($records[1]['id'], 2);
        
        // Where_in with literal values (oposite order of parameters) (TODO)
        /*
        $mq = Post::raw_query();
        $mq->select(array('id'))
            ->where('post like ?')
            ->where_in('id', array(1,2));
        $this->assertEquals("SELECT `id` FROM `posts` WHERE `posted_text` LIKE ? AND `id` IN (?, ?)", $mq->sql());
        $records = $mq->execute('%');
        $this->assertEquals(count($records), 2);
        $this->assertEquals($records[0]['id'], 1);
        $this->assertEquals($records[1]['id'], 2);
        */
    }
    

}
?>
