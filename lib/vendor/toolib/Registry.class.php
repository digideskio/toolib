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

namespace toolib;

//! A registry of entries through application life cycle
class Registry extends \ArrayObject
{
    //! Variable to hold global instance
    protected static $instance = null;
    
    //! Get global static registry
    /**
     * @return toolib\Registry The singleton instance of Registry.
     */
    public static function getInstance()
    {
        if (self::$instance === null)
            self::$instance = new Registry();
        return self::$instance;
    }

    //! Check if there is an entry in global registry
    /**
     * @param string $name The name of the entry to check if exists.
     * @return boolean @b true if it is exists otherwise @b false.
     */
    public static function has($name)
    {
        return self::getInstance()->offsetExists($name);
    }
    
    //! Get an entry from global registry
    /**
     * @param string $name The name of the entry.
     * @return
     *  - @b null If the entry does not exist.
     *  - @b mixed The value of the requested entry.
     */
    public static function get($name)
    {
        if (!self::has($name))
            return null;
        return self::getInstance()->offsetGet($name);
    }
    
    //! Set an entry in global registry
    /**
     * @param string $name The name of the entry
     * @param $value The value of the entry
     */
    public static function set($name, $value)
    {
        self::getInstance()->offsetSet($name, $value);
    }
};
