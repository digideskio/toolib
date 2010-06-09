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
require_once dirname(__FILE__) .  '/../path.inc.php';
require_once dirname(__FILE__) .  '/SampleSchema.class.php';
require_once dirname(__FILE__) .  '/SampleModels.inc.php';

class ModelTest extends PHPUnit_Framework_TestCase
{

    public static function setUpBeforeClass()
    {
        SampleSchema::build();

        // Initialize Class Models
        Forum::model();
        Thread::model();
        Post::model();
        User::model();
        Group::model();
        Group_Members::model();
    }

    public static function tearDownAfterClass()
    {   SampleSchema::destroy();
    }

    public function setUp()
    {   SampleSchema::connect();
    }
    public function tearDown()
    {
        DB_Conn::disconnect();
    }

    //! Provider for model information
    public function modelsInfo()
    {
        return array(
            array('Forum', 'forums',
                array('id', 'title'),   // Fields
                array('id'),            // PK
                array('id'),            // AI
                array()                 // FK
            ),
            array('Thread', 'threads',
                array('id', 'forum_id','title', 'datetime'),    // Fields
                array('id'),    // PK
                array('id'),    // AI
                array('forum_id' => 'Forum')         // FK
            ),
            array('Post', 'posts',
                array('id', 'thread_id', 'post', 'image', 'poster', 'date'),
                array('id'),                    // PK
                array('id'),                    // AI
                array('thread_id' => 'Thread')  // FK
            ),
            array('User', 'users',
                array('username', 'password', 'enabled'),
                array('username'),  // PK
                array(),            // AI
                array()             // FK
            ),
            array('Group', 'groups',
                array('groupname','enabled'),
                array('groupname'),  // PK
                array(),    // AI
                array()     // FK
            ),
            array('Group_Members', 'group_members',
                array('username', 'groupname'), // ALL
                array('username', 'groupname'), // PK
                array(),    // AI
                array('username' => 'User', 'groupname' => 'Group')
            ),
        );
    }

    public function testOpenModel()
    {   // Open wrong
        $res = DB_Model::open('wrong');
        $this->assertNull($res);

        // Open with class name but not model
        $res = DB_Model::open('ModelTest');
        $this->assertNull($res);

        // Open Forum
        $res = DB_Model::open('Forum');
        $this->assertType('DB_Model', $res);
        $this->assertEquals($res->name(), 'Forum');

        // Open Group
        $res = DB_Model::open('Group');
        $this->assertType('DB_Model', $res);
        $this->assertEquals($res->name(), 'Group');

        // Open Group_Members
        $res = DB_Model::open('Group_Members');
        $this->assertType('DB_Model', $res);
        $this->assertEquals($res->name(), 'Group_Members');
    }

    public function testExistsModel()
    {
        $this->assertFalse(DB_Model::exists('wrong'));
        $this->assertFalse(DB_Model::exists('ModelTest'));
        $this->assertTrue(DB_Model::exists('Forum'));
        $this->assertTrue(DB_Model::exists('Thread'));
        $this->assertTrue(DB_Model::exists('Post'));
        $this->assertTrue(DB_Model::exists('User'));
        $this->assertTrue(DB_Model::exists('Group'));
        $this->assertTrue(DB_Model::exists('Group_Members'));
    }

    //! Check model names and tables
    /**
    * @dataProvider modelsInfo
    */
    public function testNameTable($model_name, $table)
    {
        $m = DB_Model::open($model_name);
        $this->assertEquals($m->name(), $model_name);
        $this->assertEquals($m->table(), $table);

        // Check class information
        $this->assertEquals(get_static_var($model_name, 'table'), $table);
    }

    //! Check model field information
    /**
    * @dataProvider modelsInfo
    */
    public function testFields($model_name, $table, $fields, $pks, $ais, $fks)
    {   
        $m = DB_Model::open($model_name);

        // Fields names
        $fs = $m->fields();
        $this->assertEquals($fs, $fields);

        // Fields associative array
        $fs = $m->fields(true);
        $this->assertEquals(array_keys($fs), $fields);

        // Field_info (wrong-name)
        $this->assertNull($m->field_info('wrong'));
        $this->assertNull($m->field_info('wrong', 'wrong'));

        // Has-field
        $this->assertFalse($m->has_field('wrong'));

        // Field_info (correct)
        foreach($fields as $field)
        {
            $this->assertTrue($m->has_field($field));


            $info = $m->field_info($field);
            $this->assertType('array', $info);
            $this->assertEquals($field, $info['name']);

            // PK
            $this->assertEquals(in_array($field, $pks), $info['pk']);
            // AI
            $this->assertEquals(in_array($field, $ais), $info['ai']);
            // FK
            if (array_key_exists($field, $fks))
                $this->assertEquals($fks[$field], $info['fk']);
            else
                $this->assertFalse($info['fk']);
        }
    }

    //! Check PK field information
    /**
    * @dataProvider modelsInfo
    */
    public function testPkFields($model_name, $table, $fields, $pks, $ais, $fks)
    {   
        $m = DB_Model::open($model_name);

        // Pk field names
        $this->assertEquals($m->pk_fields(), $pks);

        // PK Fields info
        $this->assertEquals(array_keys($m->pk_fields(true)), $pks);

        foreach($m->pk_fields(true) as $name => $info)
        {
            $this->assertEquals($name, $info['name']);
            $this->assertTrue($info['pk']);
            $this->assertEquals($m->field_info($name), $info);
        }
    }

    //! Check AI field information
    /**
    * @dataProvider modelsInfo
    */
    public function testAIFields($model_name, $table, $fields, $pks, $ais, $fks)
    {   
        $m = DB_Model::open($model_name);

        // AI field names
        $this->assertEquals($m->ai_fields(), $ais);

        // AI Fields info
        $this->assertEquals(array_keys($m->ai_fields(true)), $ais);

        foreach($m->ai_fields(true) as $name => $info)
        {
            $this->assertEquals($name, $info['name']);
            $this->assertTrue($info['ai']);
            $this->assertEquals($m->field_info($name), $info);
        }
    }

    //! Check FK field information
    /**
    * @dataProvider modelsInfo
    */
    public function testFKFields($model_name, $table, $fields, $pks, $ais, $fks)
    {   
        $m = DB_Model::open($model_name);

        // FK field names
        $this->assertEquals($m->fk_fields(), array_keys($fks));

        // FK Fields info
        $this->assertEquals(array_keys($m->fk_fields(true)), array_keys($fks));

        foreach($m->fk_fields(true) as $name => $info)
        {
            $this->assertEquals($name, $info['name']);
            $this->assertEquals($info['fk'], $fks[$name]);
            $this->assertEquals($m->field_info($name), $info);
        }
    }

    //! Check FK Field for
    /**
    * @dataProvider modelsInfo
    */
    public function testFKFieldFor($model_name, $table, $fields, $pks, $ais, $fks)
    {   
        $m = DB_Model::open($model_name);

        // Check corect reference
        foreach($fks as $fk => $model)
        {
            $this->assertEquals($m->fk_field_for($model), $fk);
            $this->assertEquals($m->fk_field_for($model, true), $m->field_info($fk));
        }

        // Check for unknown model
        $this->assertNull($m->fk_field_for('unknown'));
        $this->assertNull($m->fk_field_for('unknown', true));
    }

    //! Cast data to external format
    /**
    * @expectedException InvalidArgumentException
    */
    public function testCastDataToUserWrong()
    {   
        $m = DB_Model::open('Forum');
        $m->user_field_data('no field', 'test');
    }

    //! Cast data to external format
    /**
    * @expectedException InvalidArgumentException
    */
    public function testCastDataToDBWrongField()
    {   
        $m = DB_Model::open('Forum');
        $m->db_field_data('no field', 'test');
    }

    //! Cast data to external format
    /**
    * @expectedException Exception
    */
    public function testCastDataToDBWrongData()
    {   
        $m = DB_Model::open('Post');
        $m->user_field_data('date', 'wrong date');
    }

    public function testDataCast()
    {   
        $m = DB_Model::open('Post');

        // Generic db -> external
        $this->assertEquals('same text', $m->user_field_data('id', 'same text'));
        $this->assertEquals('123', $m->user_field_data('id', 123));
        $this->assertEquals(123, $m->user_field_data('id', '123'));
        $this->assertEquals(array('test'), $m->user_field_data('id', array('test')));
        $this->assertEquals(true, $m->user_field_data('id', true));
        $this->assertEquals(false, $m->user_field_data('id', false));
        $this->assertEquals(null, $m->user_field_data('id', null));

        // Datetime db -> external
        
        $this->assertEquals(date_create('2002-10-10', new DateTimeZone('UTC'))
            ->setTimeZone(new DateTimeZone(date_default_timezone_get())),
            $m->user_field_data('date', '2002-10-10')
        );
        $this->assertEquals(date_create('@123', new DateTimeZone('UTC'))
            ->setTimeZone(new DateTimeZone(date_default_timezone_get())), $m->user_field_data('date', '@123'));

        // Serializable db -> external
        $this->assertEquals('text sample', $m->user_field_data('image', 's:11:"text sample";'));
        $this->assertEquals(array('item1', 'slot2' => 'item2'),
        $m->user_field_data('image', 'a:2:{i:0;s:5:"item1";s:5:"slot2";s:5:"item2";}'));

        // Generic external -> db
        $this->assertEquals('same text', $m->db_field_data('id', 'same text'));
        $this->assertEquals('123', $m->db_field_data('id', 123));
        $this->assertEquals('123', $m->db_field_data('id', '123'));
        $this->assertEquals('Array', $m->db_field_data('id', array('test')));
        $this->assertEquals(1, $m->db_field_data('id', true));
        $this->assertEquals('', $m->db_field_data('id', false));
        $this->assertEquals('', $m->db_field_data('id', null));

        // Datetime external -> db
        $formated_date = date_create('2002-10-10 00:00:00');
        $formated_date->setTimeZone(new DateTimeZone('UTC'));
        $this->assertEquals($formated_date->format(DATE_ISO8601),
            $m->db_field_data('date', date_create('2002-10-10 00:00:00')));
        $this->assertEquals('1970-01-01T00:02:03+0000', $m->db_field_data('date', date_create('@123')));

        // Serializable external -> db
        $this->assertEquals('s:11:"text sample";', $m->db_field_data('image', 'text sample'));
        $this->assertEquals('a:2:{i:0;s:5:"item1";s:5:"slot2";s:5:"item2";}',
        $m->db_field_data('image', array('item1', 'slot2' => 'item2')));
    }
}
?>
