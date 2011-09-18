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

/**
 * @brief Interface to implement authorization roles
 */
interface Role
{
    /**
     * @brief Get the name of this role
     */
    public function getName();

    /**
     * @brief Get an array with parents of this role.
     * 
     * Parents must also be implementations of Role interface.
     */
    public function getParents();

    /**
     * @brief Check if this role has a specific parent
     * @param $name The name of the parent to look for.
     * @return
     *  - @b true If the parent was found.
     *  - @b false If this parent was unknown.
     *  .
     */
    public function hasParent($name);

    /**
     * @brief Get a specific parent
     * @param $name The name of the parent to look for.
     * @return \toolib\Authz\Role
     *  - @b \\toolib\\Authz\\Role The object of the parent
     *  - @b false If this parent was not found.
     *  .
     */
    public function getParent($name);
}
