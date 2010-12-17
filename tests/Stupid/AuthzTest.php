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


use toolib\Authz;
use toolib\Authz\ResourceList;
use toolib\Authz\Role\FeederInstance;
use toolib\Stupid\Condition;

require_once __DIR__ .  '/../path.inc.php';

class Stupid_AuthzTest extends PHPUnit_Framework_TestCase
{

    public function prepareAuthz()
    {
        $roles = new FeederInstance();
        $roles->addRole('@game');
        $roles->addRole('@video');
        $roles->addRole('@user', array('@game', '@video'));
        $roles->addRole('@web-user');
        $roles->addRole('@web-admin', '@web-user');
        $roles->addRole('@fs-admin');
        $roles->addRole('@logger');
        $roles->addRole('@admin', array('@user', '@web-admin', '@fs-admin'));
        Authz::setRoleFeeder($roles);
        
        $list = new ResourceList();
        Authz::setResourceList($list);
        $dir = $list->addResource('directory');
        $dir->getAcl()->allow(null, 'read');
        $dir->getAcl()->deny(null, 'write');
        $dir->getAcl()->allow('admin', 'write');
        $dir->getAcl()->allow('user', 'list');

        $file = $list->addResource('file', 'directory');
        $file->getAcl()->allow('user', 'execute');
        $file->getAcl()->deny(null, 'list');
        
        $root = $list->getResource('file', '/');
        $root->getAcl()->allow(null, 'list');
        

        Authz::setResourceList($list);
        

    }
    public function testEffectiveness()
    {   
        $this->prepareAuthz();
    
        Authz::setCurrentRoleFunc(create_function('', 'return "unknown";'));
        $cond = Condition::create(array('type' =>'authz', 'resource' => 'file', 'action' => 'read'));
        $this->assertTrue($cond->evaluate(array()));
        
        $cond = Condition::create(array('type' =>'authz', 'resource' => 'file', 'action' => 'write'));
        $this->assertFalse($cond->evaluate(array()));
        
        Authz::setCurrentRoleFunc(create_function('', 'return "admin";'));
        $cond = Condition::create(array('type' =>'authz', 'resource' => 'file', 'action' => 'write'));
        $this->assertTrue($cond->evaluate(array()));
        
        Authz::setCurrentRoleFunc(create_function('', 'return "user";'));
        $cond = Condition::create(array('type' =>'authz', 'resource' => 'file', 'action' => 'execute'));
        $this->assertTrue($cond->evaluate(array()));
        
        $cond = Condition::create(array('type' =>'authz', 'resource' => 'file', 'action' => 'list'));
        $this->assertFalse($cond->evaluate(array()));
        
        $cond = Condition::create(array('type' =>'authz', 'resource' => 'directory', 'action' => 'list'));
        $this->assertTrue($cond->evaluate(array()));
        
        Authz::setCurrentRoleFunc(create_function('', 'return "user-x";'));
        $cond = Condition::create(array('type' =>'authz', 'resource' => 'file', 'instance' => '/', 'action' => 'list'));
        $this->assertTrue($cond->evaluate(array()));
        
        $cond = Condition::create(array('type' =>'authz', 'resource' => 'file', 'instance' => 'unknown', 'action' => 'list'));
        $this->assertFalse($cond->evaluate(array()));
    }
    
    public function testBackref()
    {   
        $this->prepareAuthz();
    
        Authz::setCurrentRoleFunc(create_function('', 'return "user-x";'));
        $cond = Condition::create(array('type' =>'authz', 'resource' => 'file', 'backref_instance' => 0, 'action' => 'list'));
        $this->assertTrue($cond->evaluate(array('/')));
        
        $cond = Condition::create(array('type' =>'authz', 'resource' => 'file', 'backref_instance' => 0, 'action' => 'list'));
        $this->assertFalse($cond->evaluate(array('unknown')));
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testMandatoryOptions1()
    {   
        $this->prepareAuthz();
    
        Authz::setCurrentRoleFunc(create_function('', 'return "user-x";'));
        $cond = Condition::create(array('type' =>'authz', 'backref_instance' => 0, 'action' => 'list'));
        $this->assertTrue($cond->evaluate(array('/')));
        
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testMandatoryOptions2()
    {   
        $this->prepareAuthz();
    
        Authz::setCurrentRoleFunc(create_function('', 'return "user-x";'));
        $cond = Condition::create(array('type' =>'authz', 'resource' => 'file',  'backref_instance' => 0,));
        $this->assertTrue($cond->evaluate(array('/')));
        
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testWrongBackreference()
    {   
        $this->prepareAuthz();
    
        $cond = Condition::create(array('type' =>'authz', 'resource' => 'file', 'backref_instance' => 5, 'role' => 'sque', 'action' => 'list'));
        $this->assertTrue($cond->evaluate(array('/')));
        
    }
}
