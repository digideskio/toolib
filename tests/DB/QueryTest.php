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
require_once __DIR__ .  '/../path.inc.php';
require_once __DIR__ .  '/SampleSchema.class.php';
require_once __DIR__ .  '/SampleModels.inc.php';

class Record_QueryTest extends PHPUnit_Framework_TestCase
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
    
    public function testSelectFieldsQuery()
    {   
        // All fields
        $mq = Forum::raw_query();
        $mq->select(Forum::model()->fields());
        $this->assertEquals('SELECT `id`, `title` FROM `forums`', $mq->sql());
        
        // All fields (with renamed ones)
        $mq = Post::raw_query();
        $mq->select(Post::model()->fields());
        $this->assertEquals('SELECT `id`, `thread_id`, `posted_text`, `image`, `poster`, `date` FROM `posts`',
            $mq->sql());
            
        // Selected fields
        $mq = Post::raw_query();
        $mq->select(array('id', 'image'));
        $this->assertEquals('SELECT `id`, `image` FROM `posts`',
            $mq->sql());
            
        // Selected pk fields
        $mq = Post::raw_query();
        $mq->select(Post::model()->pk_fields());
        $this->assertEquals('SELECT `id` FROM `posts`',
            $mq->sql());
    }
    
    public function testSelectLimitQuery()
    {   
        // Limit maximum 15
        $mq = Forum::raw_query();
        $mq->select(Forum::model()->fields())
            ->limit(15);
        $this->assertEquals('SELECT `id`, `title` FROM `forums` LIMIT 15', $mq->sql());

        // Limit maximum 15, offset 3
        $mq = Forum::raw_query();
        $mq->select(Forum::model()->fields())
            ->limit(15,3);
        $this->assertEquals('SELECT `id`, `title` FROM `forums` LIMIT 3,15', $mq->sql());

    }
    
    public function testSelectConditionalQuery()
    {
        // Where equal question mark after
        $mq = Post::raw_query();
        $mq->select(array('id'))
            ->where("post = ?");
        $this->assertEquals("SELECT `id` FROM `posts` WHERE `posted_text` = ?", $mq->sql());
        
        // Where equal question mark before
        $mq = Post::raw_query();
        $mq->select(array('id'))
            ->where("? >= image");
        $this->assertEquals("SELECT `id` FROM `posts` WHERE ? >= `image`", $mq->sql());

        // Where equal both question marks
        $mq = Post::raw_query();
        $mq->select(array('id'))
            ->where("? >= ?");
        $this->assertEquals("SELECT `id` FROM `posts` WHERE ? >= ?", $mq->sql());
        
        // Use table shortcut for primary
        $mq = Post::raw_query();
        $mq->select(array('id'))
            ->where("p.post    <>  ?");
        $this->assertEquals("SELECT `id` FROM `posts` WHERE `posted_text` <> ?", $mq->sql());

        // Using like operator
        $mq = Post::raw_query();
        $mq->select(array('id'))
            ->where("p.post LiKe ?");
        $this->assertEquals("SELECT `id` FROM `posts` WHERE `posted_text` LIKE ?", $mq->sql());

        // Using like operator with spaces
        $mq = Post::raw_query();
        $mq->select(array('id'))
            ->where("p.post   LiKe   ?");
        $this->assertEquals("SELECT `id` FROM `posts` WHERE `posted_text` LIKE ?", $mq->sql());

        // Using not like operator
        $mq = Post::raw_query();
        $mq->select(array('id'))
            ->where("p.post Not LiKe ?");
        $this->assertEquals("SELECT `id` FROM `posts` WHERE `posted_text` NOT LIKE ?", $mq->sql());
        
        // Using not like operator with spaces
        $mq = Post::raw_query();
        $mq->select(array('id'))
            ->where("p.post  Not   LiKe   ?");
        $this->assertEquals("SELECT `id` FROM `posts` WHERE `posted_text` NOT LIKE ?", $mq->sql());

    }

    public function invalidConditions()
    {
        return array(
            // Literal values are not permitted
            array(Forum::raw_query()
                ->select(Forum::model()->fields())
                ->where("title = \'?")),                
            array(Forum::raw_query()
                ->select(Forum::model()->fields())
                ->where("title = 'test!@\'#%\"'")),
            array(Forum::raw_query()
                ->select(Forum::model()->fields())
                ->where("title = 1")),
            array(Forum::raw_query()
                ->select(Forum::model()->fields())
                ->where("1 = 1")),
            array(Forum::raw_query()
                ->select(Forum::model()->fields())
                ->where("1 = title")),
            array(Forum::raw_query()
                ->select(Forum::model()->fields())
                ->where("'test' = title")),
            array(Forum::raw_query()
                ->select(Forum::model()->fields())
                ->where("title = \"1\"")),
            // Left table on non-joined query
            array(Forum::raw_query()
                ->select(Forum::model()->fields())
                ->where("l.title = ?")),
            // Invalid operators
            array(Forum::raw_query()
                ->select(Forum::model()->fields())
                ->where("title >< ?")),
            array(Forum::raw_query()
                ->select(Forum::model()->fields())
                ->where("title =< ?")),
            array(Forum::raw_query()
                ->select(Forum::model()->fields())
                ->where("title not = ?")),
            array(Forum::raw_query()
                ->select(Forum::model()->fields())
                ->where("title in ?")),
            array(Forum::raw_query()
                ->select(Forum::model()->fields())
                ->where("title in ?")),
            // Unimplemented operators
            array(Forum::raw_query()
                ->select(Forum::model()->fields())
                ->where("title is ?")),
            array(Forum::raw_query()
                ->select(Forum::model()->fields())
                ->where("title is not ?")),
        );
    }
    /**
     * @dataProvider invalidConditions()
     * @expectedException InvalidArgumentException
     */
    public function testSelectInvalidCondiationQuery($mq)
    {
        $mq->sql();
    }
}
?>
