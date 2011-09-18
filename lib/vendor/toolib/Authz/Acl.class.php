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


namespace toolib\Authz;

require_once __DIR__ . '/Ace.class.php';

/**
 * @brief Implementation of an Access Control List
 */
class Acl
{

    /**
     * @brief The array with all the Ace of this Acl
     * @var array
     */
    private $aces = array();

    /**
     * @brief Add a new entry in list to allow access for a tuple.
     * @param $role The name of the role or @b null for any role.
     * @param $action The action this entry refers to.
     */
    public function allow($role, $action)
    {
        $ace = new Ace($role, $action, true);
        $this->aces[$ace->getDnHash()] = $ace;
    }
    
    /**
     * @brief Add a new entry in list to deny access for a tuple.
     * @param $role The name of the role or @b null for any role.
     * @param $action The action this entry refers to.
     */
    public function deny($role, $action)
    {
        $ace = new Ace($role, $action, false);
        $this->aces[$ace->getDnHash()] = $ace;
    }
    
    /**
     * @brief Remove an entry from this list.
     * @param $role The name of the role as it was given through allow() or deny().
     * @param $action The name of the action as it was given through allow() or deny().
     * @return boolean
     *  - @b true If the ACE was removed.
     *  - @b false If the ACE was not found.
     */
    public function removeAce($role, $action)
    {
        $ace = new Ace($role, $action, false);
        if (!isset($this->aces[$ace->getDnHash()]))
            return false;
            
        unset($this->aces[$ace->getDnHash()]);
        return true;
    }
    

    /**
     * @brief Check if this list is emptry
     */
    public function isEmpty()
    {
        return empty($this->aces);
    }
    
    /**
     * @brief Get all the ACE of this list
     */
    public function getAces()
    {
        return $this->aces;
    }
        

    /**
     * @brief Get the effective ACE for the tuple role-action.
     * 
     * Traverse this list and find the most effective ACE for
     * the given tuple.
     * @return \toolib\Authz\Ace
     *  - @b ACE If the effective Ace was found.
     *  - @b null If no ACE was found for this tuple.
     *  .
     */
    public function effectiveAce($role, $action)
    {
        $effective_ace = null;

        foreach($this->aces as $ace)
        {
            if ($ace->getAction() !== $action)
                continue;

            if ($ace->getRole() == $role)
                $effective_ace = $ace;
            
            if ((!$effective_ace) || $effective_ace->isRoleNull())
                if ($ace->isRoleNull())
                    $effective_ace = $ace;
        }
        
        return $effective_ace;
    }
}
