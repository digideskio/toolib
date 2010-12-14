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
        Connection::disconnect();
    }
    
    public function testExecuteUpdate()
    {
        $mq = Thread::rawQuery();
        $mq->update()
            ->set('title', 'new title');
        $res = $mq->execute();
        $rec = Thread::open(1);
        $this->assertType('Thread', $rec);
        $this->assertEquals('new title', $rec->title);

        $mq = Post::rawQuery();
        $mq->update()
            ->set('post', 'test post updated')
            ->set('image');
        $res = $mq->execute(serialize('dokimi image'));
        $rec = Post::open(1);
        $this->assertType('Post', $rec);
        $this->assertEquals('test post updated', $rec->post);
        $this->assertEquals('dokimi image', $rec->image);

         // Update to null
        $mq = Post::rawQuery();
        $mq->update()
            ->set('image', null);
        $res = $mq->execute();
        $rec = Post::open(1);
        $this->assertType('Post', $rec);
        $this->assertEquals('test post updated', $rec->post);
        $this->assertEquals(null, $rec->image);
    }
    
    public function testExecuteInsert()
    {
        $mq = Post::rawQuery();
        $mq->insert(array('image', 'post'))
            ->values(serialize('image1'), 'post1');
        $this->assertTrue($mq->execute() !== false);
        
        $rec = Post::open(Connection::getLastInsertId());
        $this->assertType('Post', $rec);
        $this->assertEquals('image1', $rec->image);
        $this->assertEquals('post1', $rec->post);
        
        
        $mq = Post::rawQuery();
        $mq->insert(array('image', 'post'))
            ->valuesArray(array(serialize('image2'), 'post2'));
        $this->assertTrue($mq->execute() !== false);
        
        $rec = Post::open(Connection::getLastInsertId());
        $this->assertType('Post', $rec);
        $this->assertEquals('image2', $rec->image);
        $this->assertEquals('post2', $rec->post);
        
        
        // Insert null values
        $mq = Post::rawQuery();
        $mq->insert(Post::model()->fields())
            ->valuesArray(array(null, 1, null, null, null, null));
        $res = $mq->execute();
        $p = Post::open($res->insert_id);
        $this->assertEquals($p->thread_id, 1);
        $this->assertNull($p->post);
        $this->assertNull($p->image);
        $this->assertNull($p->poster);
        $this->assertNull($p->date);
    }

    public function testExecuteSelectWhereNull()
    {
        SampleSchema::destroy();
        SampleSchema::build();
        // Select = empty for null values must return empty
        $mq = Post::rawQuery();
        $mq->select(array('id'))
            ->where('image = ?');
        $this->assertEquals(count($mq->execute('')), 0);

        // Select = null for null values must return empty
        $mq = Post::rawQuery();
        $mq->select(array('id'))
            ->where('image = ?');
        $this->assertEquals(count($mq->execute(null)), 0);

        // Select is null for null values must return all
        $mq = Post::rawQuery();
        $mq->select(array('id'))
            ->where('image is null');
        $this->assertEquals(7, count($mq->execute()));
        
        // Select is not null for null values must return all
        $mq = Post::rawQuery();
        $mq->select(array('id'))
            ->where('image is not null');
        $this->assertEquals(count($mq->execute()), 0);
        
        // Select is not null for null values must return all
        $mq = Post::rawQuery();
        $mq->select(array('id'))
            ->where('post is not null');
        $this->assertEquals(count($mq->execute()), 7);
    }
    
    public function testExecuteSelectWhereInLiteral()
    {
       // Where_in with literal values
        $mq = Post::rawQuery();
        $mq->select(array('id'))
            ->whereIn('id', array(1,2))
            ->where('post like ?');
        $this->assertEquals("SELECT `id` FROM `posts` WHERE `id` IN (?, ?) AND `posted_text` LIKE ?", $mq->sql());
        $records = $mq->execute('%');
        $this->assertEquals(count($records), 2);
        $this->assertEquals($records[0]['id'], 1);
        $this->assertEquals($records[1]['id'], 2);
        
        // Where_in with literal values (oposite order of parameters) (TODO)
        /*
        $mq = Post::rawQuery();
        $mq->select(array('id'))
            ->where('post like ?')
            ->whereIn('id', array(1,2));
        $this->assertEquals("SELECT `id` FROM `posts` WHERE `posted_text` LIKE ? AND `id` IN (?, ?)", $mq->sql());
        $records = $mq->execute('%');
        $this->assertEquals(count($records), 2);
        $this->assertEquals($records[0]['id'], 1);
        $this->assertEquals($records[1]['id'], 2);
        */
    }
    

}
