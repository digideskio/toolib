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

/**
 * @brief The event object transmitted by EventDispatcher
 */
class Event
{
    /**
     * @brief  The name of the event
     * @var string
     */
    public $name;

    /**
     * @brief User arguments passed to event
     * @var array
     */
    public $arguments = array();

    /**
     * @brief Value to be filtered by the event
     * @var mixed
     */
    public $filtered_value = NULL;

    /**
     * @brief Type of notification
     * @var string
     */
    public $type;

    /**
     * @brief Flag if event has been processed
     * @var boolean
     */
    public $processed = false;

    /**
     * @brief Construct event object
     * @param string $name
     * @param string $type
     * @param array $arguments
     */
    public function __construct($name, $type, $arguments = array())
    {
    	$this->name = $name;
	    $this->arguments = $arguments;
	    $this->type = $type;
    }
}

/**
 * @brief Dispatch events to their listeners 
 */
class EventDispatcher
{
	/**
	 * @brief An array with all events and their listeners.
	 * @var array
	 */
    private $event_listeners = array();

    /**
     * @brief An array with global listeners
     * @var unknown_type
     */
    private $global_listeners = array();
    
    /**
     * @brief Create an EventDispatcher object and declare the events.
     * @param array $event_names An array with all events that will be declared
     */
    public function __construct($event_names = array())
    {   
        foreach($event_names as $e)
            self::declareEvent($e);
    }
    
    /**
     * @brief Declare an event on this dispatcher
     * @param string $event_name The name of the event to declare
     * @return boolean @b true if it was declared otherwise @b false
     */
    public function declareEvent($event_name)
    {   
        // Must be a valid value
        if (empty($event_name))
            return false;
            
        // Must not exist
        if ($this->hasEvent($event_name))
             return false;

        // Create listeners pool for this event
        $this->event_listeners[$event_name] = array();
        return true;
    }
    
    /**
     * @brief Check if an event is already declared
     * @param string $event_name The name of the event
     * @return boolean @b true if exists otherwise @b false
     */
    public function hasEvent($event_name)
    {
    	return array_key_exists($event_name, $this->event_listeners);
    }

    /**
     * @brief Get all events
     * @return array All events declared at this dispatcher.
     */
    public function getEvents()
    {
    	return array_keys($this->event_listeners);
    }

    /**
     * @brief Check if event has a specific listener
     * @param string $event_name The name of the event or @b NULL for global listeners.
     * @param callable $callable The callable of the listener.
     * @return boolean @b true if it has listener otherwise @b false
     */
    public function hasListener($event_name, $callable)
    {   
        // Check global listeners
        if ($event_name === NULL)
            return (array_search($callable, $this->global_listeners, true) !== false);

        // Must exist
        if (! $this->hasEvent($event_name))
             return false;

        return (array_search($callable, $this->event_listeners[$event_name], true) !== false);
    }
    
    /**
     * @brief Get all listeners of an event
     * @param string $event_name The name of the event or @b NULL for global listeners.
     * @return array @b All callbacks or @b NULL if event is unknown.
     */
    public function getListeners($event_name)
    {   
        // Check for global listeners
        if ($event_name === NULL)
            return $this->global_listeners;

        // Event must not exist
        if (! $this->hasEvent($event_name))
             return NULL;
        return $this->event_listeners[$event_name];
    }
    
    /**
     * @brief Connect on event 
     * @param string $event_name The name of the event or @b NULL for @e any event.
     * @param callable $callable The callable object to be called when the event is raised.
     * @return boolean @b true if it was connected succesfully or @b false on any error.
     */
    public function connect($event_name, $callable)
    {   
        // Check if it wants to connect to global listeners
        if ($event_name === NULL) {
        	if (array_search($callable, $this->global_listeners, true) === false) {   
                $this->global_listeners[] = $callable;
                return true;
            }
            return false;
        }
        
        // Check that the event exists
        if (! $this->hasEvent($event_name))
            return false;
            
        // Check if this callable has been registered again
        if (array_search($callable, $this->event_listeners[$event_name], true) !== false)
            return false;

        $this->event_listeners[$event_name][] = $callable;
        return true;
    }

    /**
     * @brief Disconnect from event 
     * @param string $event_name The name of the event or @b NULL for @e any event.
     * @param callable $callable The callable object that was passed on connection.
     * @return boolean @b true if it was disconnected succesfully or @b false on any error.
     */
    public function disconnect($event_name, $callable)
    {   
        // Check if it wants to disconnect from global listeners
        if ($event_name === NULL) {
        	$cb_key = array_search($callable, $this->global_listeners, true);

            if ($cb_key !== false) {   
                unset($this->global_listeners[$cb_key]);
                $this->global_listeners = array_values($this->global_listeners);
                return true;
            }
            return false;
        } 

        // Check if it is a known event
        if (! $this->hasEvent($event_name))
            return false;
            
        // Check if this listener exists
        if (($cb_key = array_search($callable, $this->event_listeners[$event_name], true)) === false)
            return false;
        
        // Remove listener
        unset($this->event_listeners[$event_name][$cb_key]);
        $this->event_listeners[$event_name] = array_values($this->event_listeners[$event_name]);
        return true;
    }
        
    /**
     * @brief Notify all listeners for this event 
     * @param string $event_name The name of the event that notification belongs to.
     * @param array $arguments Array with user defined arguments for the listeners.
     * @return toolib\Event @b Object with the details of the event.
     * @throws InvalidArgumentException if the $event_name is not valid
     */
    public function notify($event_name, $arguments = array())
    {   
        if (! $this->hasEvent($event_name))
            throw new \InvalidArgumentException("Cannot notify unknown ${event_name}");

        // Create event object
        $e = new \toolib\Event($event_name, 'notify', $arguments);
        
        // Call event listeners
        foreach($this->event_listeners[$event_name] as $callback) {
            call_user_func($callback, $e);
            $e->processed = true;   // Mark it as processed
        }
        
        // Call global listeners
        foreach($this->global_listeners as $callback) {
            call_user_func($callback, $e);
            $e->processed = true;   // Mark it as processed
        }

        return $e;
    }

    /**
     * @brief Notify all listeners for this event until one returns non null value 
     * @param string $event_name The name of the event that notification belongs to.
     * @param array $arguments Array with user defined arguments for the listeners.
     * @return toolib\Event @b Object with the details of the event.
     * @throws InvalidArgumentException if the $event_name is not valid
     */
    public function notifyUntil($event_name, $arguments = array())
    {   
        if (! $this->hasEvent($event_name))
            throw new \InvalidArgumentException("Cannot notify_until unknown ${event_name}");

        // Create event object
        $e = new \toolib\Event($event_name, 'notifyUntil', $arguments);
        
        // Call event listeners
        foreach($this->event_listeners[$event_name] as $callback)
        	if (call_user_func($callback, $e) !== NULL) {	
                $e->processed = true;   // Mark it as processed
				return $e;
			}
        
        // Call global listeners
        foreach($this->global_listeners as $callback)
			if (call_user_func($callback, $e) !== NULL) {	
                $e->processed = true;   // Mark it as processed
				return $e;
			}

        return $e;
    }

    /**
     * @brief Filter value through listeners 
     * @param string $event_name The name of the event that notification belongs to.
     * @param string $value The value that must be filtered by listeners.
     * @param array $arguments Array with user defined arguments for the listeners.
     * @return Event @b Object with the details of the event.
     * @throws InvalidArgumentException if the $event_name is not valid
     */
    public function filter($event_name, & $value, $arguments = array())
    {   
        if (! $this->hasEvent($event_name))
            throw new \InvalidArgumentException("Cannot filter unknown ${event_name}");

        // Create event object
        $e = new \toolib\Event($event_name, 'filter', $arguments);
		$e->filtered_value = & $value;
        
        // Call event listeners
        foreach($this->event_listeners[$event_name] as $callback) {   
            call_user_func($callback, $e);
            $e->processed = true;   // Mark it as processed
        }
        
        // Call global listeners
        foreach($this->global_listeners as $callback) {   
            call_user_func($callback, $e);
            $e->processed = true;   // Mark it as processed
        }

        return $e;
    }
}
