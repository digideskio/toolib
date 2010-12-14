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

class Record_Query_SQLSelectTest extends PHPUnit_Framework_TestCase
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
    
    public function testSelectFieldsQuery()
    {   
        // All fields
        $mq = Forum::rawQuery();
        $mq->select(Forum::model()->fields());
        $this->assertEquals('SELECT `id`, `title` FROM `forums`', $mq->sql());
        
        // All fields (with renamed ones)
        $mq = Post::rawQuery();
        $mq->select(Post::model()->fields());
        $this->assertEquals('SELECT `id`, `thread_id`, `posted_text`, `image`, `poster`, `date` FROM `posts`',
            $mq->sql());
            
        // Selected fields
        $mq = Post::rawQuery();
        $mq->select(array('id', 'image'));
        $this->assertEquals('SELECT `id`, `image` FROM `posts`',
            $mq->sql());
            
        // Selected pk fields
        $mq = Post::rawQuery();
        $mq->select(Post::model()->pkFields());
        $this->assertEquals('SELECT `id` FROM `posts`',
            $mq->sql());
    }
    
    public function testSelectLimitQuery()
    {   
        // Limit maximum 15
        $mq = Forum::rawQuery();
        $mq->select(Forum::model()->fields())
            ->limit(15);
        $this->assertEquals('SELECT `id`, `title` FROM `forums` LIMIT 15', $mq->sql());

        // Limit maximum 15, offset 3
        $mq = Forum::rawQuery();
        $mq->select(Forum::model()->fields())
            ->limit(15, 3);
        $this->assertEquals('SELECT `id`, `title` FROM `forums` LIMIT 3,15', $mq->sql());
    }
    
    public function testSelectGroupByQuery()
    {
        // Group by one column ref
        $mq = Forum::rawQuery();
        $mq->select(Forum::model()->fields())
            ->group_by('id');
        $this->assertEquals('SELECT `id`, `title` FROM `forums` GROUP BY `id` ASC', $mq->sql());
        
        // Group by one column ref by number
        $mq = Forum::rawQuery();
        $mq->select(Forum::model()->fields())
            ->group_by('2', 'deSc');
        $this->assertEquals('SELECT `id`, `title` FROM `forums` GROUP BY 2 DESC', $mq->sql());
        
        // Group by one single expression
        $mq = Forum::rawQuery();
        $mq->select(Forum::model()->fields())
            ->group_by('id = ?');
        $this->assertEquals('SELECT `id`, `title` FROM `forums` GROUP BY `id` = ? ASC', $mq->sql());
        
        // Group by one single expression
        $mq = Forum::rawQuery();
        $mq->select(Forum::model()->fields())
            ->group_by('id > ?', 'DESC');
        $this->assertEquals('SELECT `id`, `title` FROM `forums` GROUP BY `id` > ? DESC', $mq->sql());
        
        // Group by left join column
        $mq = Forum::rawQuery();
        $mq->select(Forum::model()->fields())
            ->leftJoin('Thread')
            ->group_by('l.id > ?', 'DESC');
        $this->assertEquals('SELECT p.`id`, p.`title` FROM `forums` p LEFT JOIN `threads` l' .
            ' ON l.`forum_id` = p.`id` GROUP BY l.`thread_id` > ? DESC', $mq->sql());
    }
    
    public function invalidGroupBy()
    {
        return array(
            // Wrong expressions
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->group_by("id > 55", 'DESC')),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->group_by("id > 'test'", 'DESC')),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->orderBy("id = 55", 'DESC')),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->group_by("44 > 55", 'DESC')),
            // Wrong columns
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->group_by("invalid_field", 'DESC')),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->group_by("l.id", 'DESC')),
            // Wrong numerical references
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->group_by("0", 'DESC')),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->group_by("3", 'DESC')),
        );
    }
    
    /**
     * @dataProvider invalidGroupBy
     * @expectedException InvalidArgumentException
     */
    public function testInvalidGroupBy($mq)
    {
        $mq->sql();
    }
    
    public function testSelectOrderByQuery()
    {
        // Order by one column ref
        $mq = Forum::rawQuery();
        $mq->select(Forum::model()->fields())
            ->orderBy('id');
        $this->assertEquals('SELECT `id`, `title` FROM `forums` ORDER BY `id` ASC', $mq->sql());

        // Order by one column ref with alias column
        $mq = Thread::rawQuery();
        $mq->select(Thread::model()->fields())
            ->orderBy('id');
        $this->assertEquals('SELECT `thread_id`, `forum_id`, `title`, `datetime` FROM `threads` ORDER BY `thread_id` ASC', $mq->sql());
        
        // Order by one column ref by number
        $mq = Forum::rawQuery();
        $mq->select(Forum::model()->fields())
            ->orderBy('2', 'deSc');
        $this->assertEquals('SELECT `id`, `title` FROM `forums` ORDER BY 2 DESC', $mq->sql());
        
        // Order by one single expression
        $mq = Forum::rawQuery();
        $mq->select(Forum::model()->fields())
            ->orderBy('id = ?');
        $this->assertEquals('SELECT `id`, `title` FROM `forums` ORDER BY `id` = ? ASC', $mq->sql());
        
        // Order by one single expression
        $mq = Forum::rawQuery();
        $mq->select(Forum::model()->fields())
            ->orderBy('id > ?', 'DESC');
        $this->assertEquals('SELECT `id`, `title` FROM `forums` ORDER BY `id` > ? DESC', $mq->sql());
        
        // Order by left join column
        $mq = Forum::rawQuery();
        $mq->select(Forum::model()->fields())
            ->leftJoin('Thread')
            ->orderBy('l.id > ?', 'DESC');
        $this->assertEquals('SELECT p.`id`, p.`title` FROM `forums` p LEFT JOIN `threads` l' .
            ' ON l.`forum_id` = p.`id` ORDER BY l.`thread_id` > ? DESC', $mq->sql());
    }
    
    
    public function invalidOrderBy()
    {
        return array(
            // Wrong expressions
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->orderBy("id > 55", 'DESC')),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->orderBy("id > 'test'", 'DESC')),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->orderBy("id = 55", 'DESC')),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->orderBy("44 > 55", 'DESC')),
            // Wrong columns
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->orderBy("invalid_field", 'DESC')),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->orderBy("l.id", 'DESC')),
            // Wrong numerical references
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->orderBy("0", 'DESC')),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->orderBy("3", 'DESC')),
        );
    }
    
    /**
     * @dataProvider invalidOrderBy
     * @expectedException InvalidArgumentException
     */
    public function testInvalidOrderBy($mq)
    {
        $mq->sql();
    }
    
    public function testSelectConditionalQuery()
    {
        // Where equal question mark after
        $mq = Post::rawQuery();
        $mq->select(array('id'))
            ->where("post = ?");
        $this->assertEquals("SELECT `id` FROM `posts` WHERE `posted_text` = ?", $mq->sql());
        
        // Where equal question mark before
        $mq = Post::rawQuery();
        $mq->select(array('id'))
            ->where("? >= image");
        $this->assertEquals("SELECT `id` FROM `posts` WHERE ? >= `image`", $mq->sql());

        // Where equal both question marks
        $mq = Post::rawQuery();
        $mq->select(array('id'))
            ->where("? >= ?");
        $this->assertEquals("SELECT `id` FROM `posts` WHERE ? >= ?", $mq->sql());
        
        // Use table shortcut for primary
        $mq = Post::rawQuery();
        $mq->select(array('id'))
            ->where("p.post    <>  ?");
        $this->assertEquals("SELECT `id` FROM `posts` WHERE `posted_text` <> ?", $mq->sql());

        // Using like operator
        $mq = Post::rawQuery();
        $mq->select(array('id'))
            ->where("p.post LiKe ?");
        $this->assertEquals("SELECT `id` FROM `posts` WHERE `posted_text` LIKE ?", $mq->sql());

        // Using like operator with spaces
        $mq = Post::rawQuery();
        $mq->select(array('id'))
            ->where("p.post   LiKe   ?");
        $this->assertEquals("SELECT `id` FROM `posts` WHERE `posted_text` LIKE ?", $mq->sql());

        // Using not like operator
        $mq = Post::rawQuery();
        $mq->select(array('id'))
            ->where("p.post Not LiKe ?");
        $this->assertEquals("SELECT `id` FROM `posts` WHERE `posted_text` NOT LIKE ?", $mq->sql());
        
        // Using not like operator with spaces
        $mq = Post::rawQuery();
        $mq->select(array('id'))
            ->where("p.post  Not   LiKe   ?");
        $this->assertEquals("SELECT `id` FROM `posts` WHERE `posted_text` NOT LIKE ?", $mq->sql());
        
        // Using is operator with spaces
        $mq = Post::rawQuery();
        $mq->select(array('id'))
            ->where("p.post  iS null");
        $this->assertEquals("SELECT `id` FROM `posts` WHERE `posted_text` IS NULL", $mq->sql());

        // Using is not operator with false
        $mq = Post::rawQuery();
        $mq->select(array('id'))
            ->where("p.post  iS Not FaLse");
        $this->assertEquals("SELECT `id` FROM `posts` WHERE `posted_text` IS NOT FALSE", $mq->sql());

        // Using is operator with True
        $mq = Post::rawQuery();
        $mq->select(array('id'))
            ->where("p.post  iS  truE ");
        $this->assertEquals("SELECT `id` FROM `posts` WHERE `posted_text` IS TRUE", $mq->sql());
        
        // Using is operator with True
        $mq = Post::rawQuery();
        $mq->select(array('id'))
            ->where("p.post  iS  unKnown ");
        $this->assertEquals("SELECT `id` FROM `posts` WHERE `posted_text` IS UNKNOWN", $mq->sql());
        
        // Testing boolean operators
        $mq = Post::rawQuery();
        $mq->select(array('id'))
            ->where("post = ?", 'and not')
            ->where("post = ?", 'oR not')
            ->where("post = ?", 'not')
            ->where("post = ?", 'XoR')
            ->where("post = ?", 'aNd')
            ->where("post = ?", 'XoR not');
        $this->assertEquals(
            "SELECT `id` FROM `posts` WHERE NOT `posted_text` = ? OR NOT `posted_text` = ? ".
            "AND NOT `posted_text` = ? XOR `posted_text` = ? AND `posted_text` = ? XOR NOT `posted_text` = ?",
            $mq->sql());
    }
    
    public function invalidConditions()
    {
        return array(
            // Literal values are not permitted
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->where("title = \'?")),                
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->where("title = 'test!@\'#%\"'")),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->where("title = 1")),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->where("1 = 1")),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->where("1 = title")),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->where("'test' = title")),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->where("title = \"1\"")),
            // Left table on non-joined query
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->where("l.title = ?")),
            // Invalid r-values
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->where("l.title = null")),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->where("l.title = false")),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->where("l.title = true")),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->where("l.title like true")),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->where("l.title is ?")),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->where("l.title is not ?")),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->where("l.title is falsea")),
            // Invalid operators
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->where("title >< ?")),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->where("title =< ?")),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->where("title not = ?")),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->where("title in ?")),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->where("title in ?")),
            // Invalid operators
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->where("title = ?", 'invalid')),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->where("title = ?", 'andnot')),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->where("title = ?", 'ornot')),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->where("title = ?", 'andor')),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->where("title = ?", 'xornot')),
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->where("title = ?", 'notxor')),
        );
    }
    
    /**
     * @dataProvider invalidConditions()
     * @expectedException InvalidArgumentException
     */
    public function testSelectInvalidConditionalQuery($mq)
    {
        $mq->sql();
    }
    
    public function testSelectConditionalWhereInQuery()
    {
        // Where_in with numeric value
        $mq = Post::rawQuery();
        $mq->select(array('id'))
            ->whereIn('post', 3);
        $this->assertEquals("SELECT `id` FROM `posts` WHERE `posted_text` IN (?, ?, ?)", $mq->sql());

        // Where_in with literal values
        $mq = Post::rawQuery();
        $mq->select(array('id'))
            ->whereIn('id', array(1,2))
            ->where('post like ?');
        $this->assertEquals("SELECT `id` FROM `posts` WHERE `id` IN (?, ?) AND `posted_text` LIKE ?", $mq->sql());
        
        // Where in with left join and literal values
        $mq = Thread::rawQuery();
        $mq->select(array('id', 'title'))
            ->leftJoin('Post', 'id', 'thread_id')
            ->whereIn('l.post', array('', 'nothing', 'draft'));
        $this->assertEquals('SELECT p.`thread_id`, p.`title` FROM `threads` p LEFT JOIN `posts` l ON ' .
            'l.`thread_id` = p.`thread_id` WHERE l.`posted_text` IN (?, ?, ?)', $mq->sql());
            
        // Boolean operators with where_in
        $mq = Thread::rawQuery();
        $mq->select(array('id'))
            ->whereIn('title', 1)
            ->whereIn('id', 1)
            ->whereIn('forum_id', 1, 'oR')
            ->whereIn('datetime', 1, 'AnD');
        $this->assertEquals('SELECT `thread_id` FROM `threads` WHERE `title` IN (?) AND '.
            '`thread_id` IN (?) OR `forum_id` IN (?) AND `datetime` IN (?)', $mq->sql());
    }
    
    public function testSelectLeftJoinQuery()
    {
        // Perform a join with explicit defined bond
        $mq = Thread::rawQuery();
        $mq->select(Thread::model()->fields())
            ->leftJoin('Post', 'id', 'thread_id')
            ->where("p.title  not LiKe   ?")
            ->where("l.post  LiKe   ?");
        $this->assertEquals('SELECT p.`thread_id`, p.`forum_id`, p.`title`, p.`datetime` FROM `threads` ' .
            'p LEFT JOIN `posts` l ON l.`thread_id` = p.`thread_id` WHERE p.`title` NOT LIKE ? AND l.`posted_text` LIKE ?', $mq->sql());

        // Perform a join with implicit defined bond 1-M
        $mq = Thread::rawQuery();
        $mq->select(Thread::model()->fields())
            ->leftJoin('Post')
            ->where("p.title  not LiKe   ?")
            ->where("l.post  LiKe   ?");
        $this->assertEquals('SELECT p.`thread_id`, p.`forum_id`, p.`title`, p.`datetime` FROM `threads` ' .
            'p LEFT JOIN `posts` l ON l.`thread_id` = p.`thread_id` WHERE p.`title` NOT LIKE ? AND l.`posted_text` LIKE ?', $mq->sql());

        // Perform a join with implicit defined bond M-1
        $mq = Post::rawQuery();
        $mq->select(array('id'))
            ->leftJoin('Thread')
            ->where("l.title  not LiKe   ?");
        $this->assertEquals('SELECT p.`id` FROM `posts` p LEFT JOIN `threads` l ' .
            'ON l.`thread_id` = p.`thread_id` WHERE l.`title` NOT LIKE ?', $mq->sql());

        // Perform a query with group_by
        $mq = Thread::rawQuery();
        $mq->select(Thread::model()->fields())
            ->leftJoin('Post', 'id', 'thread_id')
            ->where("p.title  not LiKe   ?")
            ->group_by("p.id");
        $this->assertEquals('SELECT p.`thread_id`, p.`forum_id`, p.`title`, p.`datetime` FROM `threads` p ' .
            'LEFT JOIN `posts` l ON l.`thread_id` = p.`thread_id` WHERE p.`title` NOT LIKE ? GROUP BY p.`thread_id` ASC', $mq->sql());
    }
    
    
    public function invalidLeftJoins()
    {
        return array(
            // Wrong join keys,
            array(Thread::rawQuery()
                ->select(Thread::model()->fields())
                ->leftJoin('Post', 'id', 'invalid_thread_id')),
            array(Thread::rawQuery()
                ->select(Thread::model()->fields())
                ->leftJoin('Post', 'invalid_id', 'invalid_thread_id')),
            array(Thread::rawQuery()
                ->select(Thread::model()->fields())
                ->leftJoin('Post', 'invalid_id', 'thread_id')),
            array(Thread::rawQuery()
                ->select(Thread::model()->fields())
                ->leftJoin('InvalidModel', 'invalid_id', 'thread_id')),
            // Left join without explicit join keys on non-related models
            array(Forum::rawQuery()
                ->select(Forum::model()->fields())
                ->leftJoin('Post')),
        );
    }
    
    /**
     * @dataProvider invalidLeftJoins()
     * @expectedException InvalidArgumentException
     */
    public function testInvalidLeftJoins($mq)
    {
        $mq->sql();
    }

    public function testSelectMixedGrill()
    {
        // Everything!
        $mq = Thread::rawQuery();
        $mq->select(Thread::model()->fields())
            ->leftJoin('Post', 'id', 'thread_id')
            ->where('l.post like ?', 'not')
            ->whereIn('l.id', array(1,2,3,4,5), 'OR')
            ->whereIn('p.title like ?', 5, 'AND not')
            ->where("p.title  not LiKe   ?", 'or')
            ->orderBy(1, 'ASC')
            ->orderBy('p.title = ?', 'DESC')
            ->group_by("p.id")
            ->group_by(3, 'DESC');
        $this->assertEquals('SELECT p.`thread_id`, p.`forum_id`, p.`title`, p.`datetime` FROM `threads` p ' .
            'LEFT JOIN `posts` l ON l.`thread_id` = p.`thread_id` ' .
            'WHERE NOT l.`posted_text` LIKE ? OR l.`id` IN (?, ?, ?, ?, ?) ' .
            'AND NOT p.`title` IN (?, ?, ?, ?, ?) OR p.`title` NOT LIKE ? ' .
            'GROUP BY p.`thread_id` ASC, 3 DESC ORDER BY 1 ASC, p.`title` = ? DESC', $mq->sql());
    }
    
}
