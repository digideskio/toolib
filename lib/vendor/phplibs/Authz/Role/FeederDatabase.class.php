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


require_once dirname(__FILE__) . '/Feeder.class.php';
require_once dirname(__FILE__) . '/Database.class.php';

class Authz_Role_FeederDatabase implements Authz_Role_Feeder
{
    protected $role_query;
    
    protected $role_name_field;
    
    protected $parents_query;
    
    protected $parent_name_field;
    
    protected $role_class;
    
    public function __construct(DB_ModelQuery $role_query, $role_name_field, $parents_query = null, $parent_name_field = null, $role_class = null)
    {
        $this->role_query = $role_query;
        $this->role_name = $role_name_field;
        $this->parents_query = $parents_query;
        $this->parent_name_field = $parent_name_field;
        $this->role_class = ($role_class == null?'Authz_Role_Database':$role_class);
    }
    
    public function has_role($name)
    {
        $result = $this->role_query->execute($name);
        if (count($result) !== 1)
            return false;
        return true;
    }
    
    public function get_role($name)
    {
        if (!$this->has_role($name))
            return false;
        
        return new $this->role_class($name, $this->parents_query, $this->parent_name_field);
    }
}
?>
