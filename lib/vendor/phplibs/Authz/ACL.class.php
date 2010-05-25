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


require_once dirname(__FILE__) . '/ACE.class.php';

class Authz_ACL
{
    private $aces = array();

    public function allow($role, $action)
    {
        $ace = new Authz_ACE($role, $action, true);
        $this->aces[$ace->get_dn_hash()] = $ace;
    }
    
    public function deny($role, $action)
    {
        $ace = new Authz_ACE($role, $action, false);
        $this->aces[$ace->get_dn_hash()] = $ace;
    }
    
    //! Remove an ace
    public function remove_ace($role, $action)
    {
        $ace = new Authz_ACE($role, $action, false);
        if (!isset($this->aces[$ace->get_dn_hash()]))
            return false;
            
        unset($this->aces[$ace->get_dn_hash()]);
    }
    
    //! Check if acl is empty
    public function is_empty()
    {
        return empty($this->aces);
    }
    
    //! Get all aces of this list
    public function get_aces()
    {
        return $this->aces;
    }
        
    //! Get the effective ace for the tuple role-action.
    public function effective_ace($role, $action)
    {
        $effective_ace = null;

        foreach($this->aces as $ace)
        {
            if ($ace->get_action() !== $action)
                continue;

            if ($ace->get_role() == $role)
                $effective_ace = $ace;
            
            if ((!$effective_ace) || $effective_ace->is_role_null())
                if ($ace->is_role_null())
                    $effective_ace = $ace;
        }
        
        return $effective_ace;
    }
}

?>
