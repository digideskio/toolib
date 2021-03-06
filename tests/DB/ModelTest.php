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


require_once __DIR__ . '/../path.inc.php';
require_once __DIR__ . '/SampleSchema.class.php';
require_once __DIR__ . '/SampleModels.inc.php';

use toolib\DB\Connection;
use toolib\DB\Model;

class ModelTest extends PHPUnit_Framework_TestCase
{

    public static function setUpBeforeClass()
    {
        SampleSchema::build();

        // Initialize Class Models
        Forum::getModel();
        Thread::getModel();
        Post::getModel();
        User::getModel();
        Group::getModel();
        Group_Members::getModel();
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
        $res = Model::open('wrong');
        $this->assertNull($res);

        // Open with class name but not model
        $res = Model::open('ModelTest');
        $this->assertNull($res);

        // Open Forum
        $res = Model::open('Forum');
        $this->assertInstanceOf('toolib\DB\Model', $res);
        $this->assertEquals($res->getName(), 'Forum');

        // Open Group
        $res = Model::open('Group');
        $this->assertInstanceOf('toolib\DB\Model', $res);
        $this->assertEquals($res->getName(), 'Group');

        // Open Group_Members
        $res = Model::open('Group_Members');
        $this->assertInstanceOf('toolib\DB\Model', $res);
        $this->assertEquals($res->getName(), 'Group_Members');
    }

    public function testExistsModel()
    {
        $this->assertFalse(Model::exists('wrong'));
        $this->assertFalse(Model::exists('ModelTest'));
        $this->assertTrue(Model::exists('Forum'));
        $this->assertTrue(Model::exists('Thread'));
        $this->assertTrue(Model::exists('Post'));
        $this->assertTrue(Model::exists('User'));
        $this->assertTrue(Model::exists('Group'));
        $this->assertTrue(Model::exists('Group_Members'));
    }

    //! Check model names and tables
    /**
    * @dataProvider modelsInfo
    */
    public function testNameTable($model_name, $table)
    {
        $m = Model::open($model_name);
        $this->assertEquals($m->getName(), $model_name);
        $this->assertEquals($m->getTable(), $table);

        // Check class information
        if (property_exists($model_name, 'table'))
        	$this->assertEquals($table, $model_name::$table);
    }

    //! Check model field information
    /**
    * @dataProvider modelsInfo
    */
    public function testFields($model_name, $table, $fields, $pks, $ais, $fks)
    {   
        $m = Model::open($model_name);

        // Fields names
        $fs = $m->getFields();
        $this->assertEquals($fs, $fields);

        // Fields associative array
        $fs = $m->getFields(true);
        $this->assertEquals(array_keys($fs), $fields);

        // Field_info (wrong-name)
        $this->assertNull($m->getFieldInfo('wrong'));
        $this->assertNull($m->getFieldInfo('wrong', 'wrong'));

        // Has-field
        $this->assertFalse($m->hasField('wrong'));

        // Field_info (correct)
        foreach($fields as $field)
        {
            $this->assertTrue($m->hasField($field));

            $info = $m->getFieldInfo($field);
            $this->assertInternalType('array', $info);
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
        $m = Model::open($model_name);

        // Pk field names
        $this->assertEquals($m->getPkFields(), $pks);

        // PK Fields info
        $this->assertEquals(array_keys($m->getPkFields(true)), $pks);

        foreach($m->getPkFields(true) as $name => $info)
        {
            $this->assertEquals($name, $info['name']);
            $this->assertTrue($info['pk']);
            $this->assertEquals($m->getFieldInfo($name), $info);
        }
    }

    //! Check AI field information
    /**
    * @dataProvider modelsInfo
    */
    public function testAIFields($model_name, $table, $fields, $pks, $ais, $fks)
    {   
        $m = Model::open($model_name);

        // AI field names
        $this->assertEquals($m->getAiFields(), $ais);

        // AI Fields info
        $this->assertEquals(array_keys($m->getAiFields(true)), $ais);

        foreach($m->getAiFields(true) as $name => $info)
        {
            $this->assertEquals($name, $info['name']);
            $this->assertTrue($info['ai']);
            $this->assertEquals($m->getFieldInfo($name), $info);
        }
    }

    //! Check FK field information
    /**
    * @dataProvider modelsInfo
    */
    public function testFKFields($model_name, $table, $fields, $pks, $ais, $fks)
    {   
        $m = Model::open($model_name);

        // FK field names
        $this->assertEquals($m->getFkFields(), array_keys($fks));

        // FK Fields info
        $this->assertEquals(array_keys($m->getFkFields(true)), array_keys($fks));

        foreach($m->getFkFields(true) as $name => $info)
        {
            $this->assertEquals($name, $info['name']);
            $this->assertEquals($info['fk'], $fks[$name]);
            $this->assertEquals($m->getFieldInfo($name), $info);
        }
    }

    //! Check FK Field for
    /**
    * @dataProvider modelsInfo
    */
    public function testFKFieldFor($model_name, $table, $fields, $pks, $ais, $fks)
    {   
        $m = Model::open($model_name);

        // Check corect reference
        foreach($fks as $fk => $model)
        {
            $this->assertEquals($m->getFkFieldFor($model), $fk);
            $this->assertEquals($m->getFkFieldFor($model, true), $m->getFieldInfo($fk));
        }

        // Check for unknown model
        $this->assertNull($m->getFkFieldFor('unknown'));
        $this->assertNull($m->getFkFieldFor('unknown', true));
    }

    //! Unpack data from database format
    /**
    * @expectedException InvalidArgumentException
    */
    public function testUnpackDataWrong()
    {   
        $m = Model::open('Forum');
        $m->unpackFieldData('no field', 'test');
    }

    //! Pack data to database format
    /**
    * @expectedException InvalidArgumentException
    */
    public function testPackDataWrongField()
    {   
        $m = Model::open('Forum');
        $m->packFieldData('no field', 'test');
    }

    //! Unpack data to wrong format
    /**
    * @expectedException Exception
    */
    public function testUnpackDataWrongData()
    {   
        $m = Model::open('Post');
        $m->unpackFieldData('date', 'wrong date');
    }

    public function testDataPacking()
    {   
        $m = Model::open('Post');

        // Generic db -> external
        $this->assertEquals('same text', $m->unpackFieldData('id', 'same text'));
        $this->assertEquals('123', $m->unpackFieldData('id', 123));
        $this->assertEquals(123, $m->unpackFieldData('id', '123'));
        $this->assertSame(array('test'), $m->unpackFieldData('id', array('test')));
        $this->assertEquals(true, $m->unpackFieldData('id', true));
        $this->assertEquals(false, $m->unpackFieldData('id', false));
        $this->assertSame(null, $m->unpackFieldData('id', null));

        // Datetime db -> external
        $this->assertNull($m->unpackFieldData('date', null));  // Null is always null
        $expected_tm = date_create('2002-10-10', new DateTimeZone('UTC'));
        $expected_tm->setTimeZone(new DateTimeZone(date_default_timezone_get()));
        $this->assertEquals($expected_tm,
            $m->unpackFieldData('date', '2002-10-10')
        );
        $expected_tm = date_create('@123', new DateTimeZone('UTC'));
        $expected_tm->setTimeZone(new DateTimeZone(date_default_timezone_get()));
        $this->assertEquals($expected_tm, $m->unpackFieldData('date', '@123'));

        // Serializable db -> external
        $this->assertNull($m->unpackFieldData('image', null));  // Null is always null
        $this->assertEquals('text sample', $m->unpackFieldData('image', 's:11:"text sample";'));
        $this->assertEquals(array('item1', 'slot2' => 'item2'),
        $m->unpackFieldData('image', 'a:2:{i:0;s:5:"item1";s:5:"slot2";s:5:"item2";}'));

        // Generic external -> db
        $this->assertEquals('same text', $m->packFieldData('id', 'same text'));
        $this->assertEquals('123', $m->packFieldData('id', 123));
        $this->assertEquals('123', $m->packFieldData('id', '123'));
        $this->assertEquals('Array', $m->packFieldData('id', array('test')));
        $this->assertEquals(1, $m->packFieldData('id', true));
        $this->assertEquals('', $m->packFieldData('id', false));
        $this->assertEquals('', $m->packFieldData('id', null));
        $this->assertSame(null, $m->packFieldData('id', null));

        // Datetime external -> db
        $this->assertEquals(null, $m->packFieldData('date', null)); // Null is always null
        $formated_date = date_create('2002-10-10 00:00:00');
        $formated_date->setTimeZone(new DateTimeZone('UTC'));
        $this->assertEquals($formated_date->format(DATE_ISO8601),
            $m->packFieldData('date', date_create('2002-10-10 00:00:00')));
        $this->assertEquals('1970-01-01T00:02:03+0000', $m->packFieldData('date', date_create('@123')));
        
        // Serializable external -> db
        $this->assertEquals(null, $m->packFieldData('image', null)); // Null is always null
        $this->assertEquals('s:11:"text sample";', $m->packFieldData('image', 'text sample'));
        $this->assertEquals('a:2:{i:0;s:5:"item1";s:5:"slot2";s:5:"item2";}',
        $m->packFieldData('image', array('item1', 'slot2' => 'item2')));
    }
}
