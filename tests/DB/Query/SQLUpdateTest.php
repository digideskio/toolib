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

class Record_Query_SQLUpdateTest extends PHPUnit_Framework_TestCase
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
        //Connection::disconnect();
    }
    
    public function testUpdate()
    {
        $mq = Thread::rawQuery();
        $mq->update()
            ->set('id');
        $this->assertEquals('UPDATE `threads` SET `thread_id` = ?', $mq->sql());

        $mq = Thread::rawQuery();
        $mq->update()
            ->set('id')
            ->set('title', 'new title');
        $this->assertEquals('UPDATE `threads` SET `thread_id` = ?, `title` = ?', $mq->sql());
        
        $mq = Post::rawQuery();
        $mq->update()
            ->set('post');
        $this->assertEquals('UPDATE `posts` SET `posted_text` = ?', $mq->sql());
        
        $mq = Post::rawQuery();
        $mq->update()
            ->set('post')
            ->limit(1);
        $this->assertEquals('UPDATE `posts` SET `posted_text` = ? LIMIT 1', $mq->sql());

        $mq = Post::rawQuery();
        $mq->update()
            ->set('post')
            ->limit(1, 14); // Drop offset in update as it is not valid
        $this->assertEquals('UPDATE `posts` SET `posted_text` = ? LIMIT 1', $mq->sql());
        
        $mq = Post::rawQuery();
        $mq->update()
            ->set('post')
            ->group_by('post')  // Drop group by post in update
            ->orderBy('id');
        $this->assertEquals('UPDATE `posts` SET `posted_text` = ? ORDER BY `id` ASC', $mq->sql());

    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalid1Insert()
    {
        $mq = Post::rawQuery();
        $mq->update()
            ->set('invalid_field');
        $this->assertEquals('UPDATE `posts` SET `posted_text` = ?', $mq->sql());
    }
}
