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

namespace toolib\Authn\Ldap;
require_once( dirname(__FILE__) . '/../Identity.class.php');

/**
  * @brief Implementation of identity for \\toolib\\Authn\\Ldap\\Backend.
 */
class Identity implements \toolib\Authn\Identity
{
    /**
     * @brief An associative array with all user attributes
     * @var array
     */	
	private $user_attribs;

    /**
     * @brief The name of the attribute that will be used as idenity's id.
     * @var string
     */
    private $id_attribute;
    
    /**
     * @brief The object is constructed by \toolib\Authn\Ldap\Backend
     * @param $user_attribs Associative array with all attributes of user in LDAP catalog.
     * @param $id_attribute The name of the attribute that will be used as idenity's id.
     */
    public function __construct($user_attribs, $id_attribute)
    {
        $this->user_attribs = $user_attribs;
        $this->id_attribute = $id_attribute;
        
        // Check that there is an id attribute
        if (! $this->getAttribute($this->id_attribute))
            throw new RuntimeException("There is no attirubute with name \"{$this->id_attribute}\"!");
    }

    /**
     * @brief Get the Distinguished Name of this identity
     */
    public function dn()
    {   
        return $this->getAttribute('distinguishedname');
    }

    /**
     * @brief Get the principalName of this identity
     */
    public function getPrincipalName()
    {   
        return $this->getAttribute('userprincipalname');
    }
    
    /**
     * @brief Get the SAM Account Name of this identity
     */
    public function getSamAccountName()
    {
        return $this->getAttribute('samaccountname');
    }
    
    /**
     * @brief Get an attribute from users attributes
     * @param $name The name of the attribute
     * @return
     *  - The value of attribute.
     *  - @b false on any kind of error.
     */
    public function getAttribute($name)
    {
        if (!isset($this->user_attribs[$name]))
            return false;
        if ($this->user_attribs[$name]['count'] == 0)
            return false;
        return $this->user_attribs[$name][0];
    }
    
    public function id()
    {
        return $this->getAttribute($this->id_attribute);
    }
}
