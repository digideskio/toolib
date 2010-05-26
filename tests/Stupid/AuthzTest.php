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

class Stupid_AuthzTest extends PHPUnit_Framework_TestCase
{

    public function prepareAuthz()
    {
        $roles = new Authz_Role_FeederInstance();
        $roles->add_role('@game');
        $roles->add_role('@video');
        $roles->add_role('@user', array('@game', '@video'));
        $roles->add_role('@web-user');
        $roles->add_role('@web-admin', '@web-user');
        $roles->add_role('@fs-admin');
        $roles->add_role('@logger');
        $roles->add_role('@admin', array('@user', '@web-admin', '@fs-admin'));

        $list = new Authz_ResourceList();
        Authz::set_resource_list($list);
        $dir = $list->add_resource('directory');
        $dir->get_acl()->allow(null, 'read');
        $dir->get_acl()->deny(null, 'write');
        $dir->get_acl()->allow('admin', 'write');
        $dir->get_acl()->allow('user', 'list');

        $file = $list->add_resource('file', 'directory');
        $file->get_acl()->allow('user', 'execute');
        $file->get_acl()->deny(null, 'list');
        
        $root = $list->get_resource('file', '/');
        $root->get_acl()->allow(null, 'list');
        
        Authz::set_role_feeder($roles);
        Authz::set_resource_list($list);
        

    }
    public function testEffectiveness()
    {   
        $this->prepareAuthz();
    
        $cond = Stupid_Condition::create(array('type' =>'authz', 'resource' => 'file', 'role' => 'unknown', 'action' => 'read'));
        $this->assertTrue($cond->evaluate(array()));
        
        $cond = Stupid_Condition::create(array('type' =>'authz', 'resource' => 'file', 'role' => 'unknown', 'action' => 'write'));
        $this->assertFalse($cond->evaluate(array()));
        
        $cond = Stupid_Condition::create(array('type' =>'authz', 'resource' => 'file', 'role' => 'admin', 'action' => 'write'));
        $this->assertTrue($cond->evaluate(array()));
        
        $cond = Stupid_Condition::create(array('type' =>'authz', 'resource' => 'file', 'role' => 'user', 'action' => 'execute'));
        $this->assertTrue($cond->evaluate(array()));
        
        $cond = Stupid_Condition::create(array('type' =>'authz', 'resource' => 'file', 'role' => 'user', 'action' => 'list'));
        $this->assertFalse($cond->evaluate(array()));
        
        $cond = Stupid_Condition::create(array('type' =>'authz', 'resource' => 'directory', 'role' => 'user', 'action' => 'list'));
        $this->assertTrue($cond->evaluate(array()));
        
        $cond = Stupid_Condition::create(array('type' =>'authz', 'resource' => 'file', 'instance' => '/', 'role' => 'sque', 'action' => 'list'));
        $this->assertTrue($cond->evaluate(array()));
        
        $cond = Stupid_Condition::create(array('type' =>'authz', 'resource' => 'file', 'instance' => 'unknown', 'role' => 'sque', 'action' => 'list'));
        $this->assertFalse($cond->evaluate(array()));
    }
    
    public function testBackref()
    {   
        $this->prepareAuthz();
    
        $cond = Stupid_Condition::create(array('type' =>'authz', 'resource' => 'file', 'backref_instance' => 0, 'role' => 'sque', 'action' => 'list'));
        $this->assertTrue($cond->evaluate(array('/')));
        
        $cond = Stupid_Condition::create(array('type' =>'authz', 'resource' => 'file', 'backref_instance' => 0, 'role' => 'sque', 'action' => 'list'));
        $this->assertFalse($cond->evaluate(array('unknown')));
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testMandatoryOptions1()
    {   
        $this->prepareAuthz();
    
        $cond = Stupid_Condition::create(array('type' =>'authz', 'backref_instance' => 0, 'role' => 'sque', 'action' => 'list'));
        $this->assertTrue($cond->evaluate(array('/')));
        
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testMandatoryOptions2()
    {   
        $this->prepareAuthz();
    
        $cond = Stupid_Condition::create(array('type' =>'authz', 'resource' => 'file',  'backref_instance' => 0, 'role' => 'sque'));
        $this->assertTrue($cond->evaluate(array('/')));
        
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testWrongBackreference()
    {   
        $this->prepareAuthz();
    
        $cond = Stupid_Condition::create(array('type' =>'authz', 'resource' => 'file', 'backref_instance' => 5, 'role' => 'sque', 'action' => 'list'));
        $this->assertTrue($cond->evaluate(array('/')));
        
    }
}
?>
