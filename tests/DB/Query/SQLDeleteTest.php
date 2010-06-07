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

class Record_Query_SQLDeleteTest extends PHPUnit_Framework_TestCase
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
    
    public function testDelete()
    {
        $mq = Thread::raw_query();
        $mq->delete()
            ->where('id = ?');
        $this->assertEquals('DELETE FROM `threads` WHERE `id` = ?', $mq->sql());
        
        $mq = Thread::raw_query();
        $mq->delete()
            ->where('id = ?');
        $this->assertEquals('DELETE FROM `threads` WHERE `id` = ?', $mq->sql());
        
        $mq = Post::raw_query();
        $mq->delete()
            ->where('post = ?')
            ->limit(1, 14); // Drop offset in delete as it is not valid
        $this->assertEquals('DELETE FROM `posts` WHERE `posted_text` = ? LIMIT 1', $mq->sql());
        
        $mq = Post::raw_query();
        $mq->delete()
            ->where('post = ?')
            ->group_by('post')  // Drop group by post in delete
            ->order_by('id');
        $this->assertEquals('DELETE FROM `posts` WHERE `posted_text` = ? ORDER BY `id` ASC', $mq->sql());
    }
    
}
?>
