<?php

//! The event object passed to listeners
class Event
{
    //! The name of the event
    public $name;

    //! User parameters passed to event
    public $parameters = array();

    //! Value to be filtered by the event
    public $value = NULL;

    //! Type of notification
    public $type;

    //! Flag if event has been processed
    public $processed = false;

    //! Construct event object
    public function __construct($name, $type, $parameters = array())
    {   $this->name = $name;
        $this->parameters = $parameters;
        $this->type = $type;
    }
}

//! Dispatch events to their listeners
/** 
    EventDispatcher holds an array with all events. Events can
    be declared at the dispatcher using declare_event() function. The
    concept is that an object raises_events and the registered listeners
    get informed using the callback function that they previously defined.
    
    @note There are is a special event, that you cannot raise it or declare it
        the '*'. This is an alias for @b any event.
        
    @b Example \n
    To understand the concept of EventDispatcher we will demonstrate it with Cat,
    Bob and Alice. Let's say Bob wants to listen TheirCat if it is hungry to feed it,
    and Alice wants to listen TheirCat to see if it is bored so that she entertains it.
    
    First we define the Cat class and PetHolder class and we create our actors.
    @code
    class Cat
    {
        public $events;
        
        public function __construct()
        {    
            $this->events = new EventDispatcher(array('hungry', 'bored'));
        }
        
        public function random_mood()
        {
            if (my_random())
                $this->events->notify('hungry', $this);
            else
                $this->events->notify('bored', $this);
        }
    }
    
    class PetHolder
    {
        public function feed_pet($pet)
        {}
        
        public function entertain_pet($pet)
        {}
    }
    
    $TheirCat = new Cat();
    $Bob = new PetHolder();
    $Alice = new PetHolder();
    @endcode
    
    Now that we have all our actors we need to declare who wants to be informed for.
    @code
    $TheirCat->events->connect('hungry', array($Bob, 'feed_pet'));
    $TheirCat->events->connect('bored', array($Alice, 'entertain_pet'));
    @endcode
    
    When ever $TheirCat->random_mood() the appropriate callback of Bob or Alice will be called
    to handle the event.
*/
class EventDispatcher
{
    //! An array with all events and their listeners.
    private $event_listeners = array();

    //! An array with global listeners
    private $global_listeners = array();
    
    //! Create an EventDispatcher object and declare the events.
    /**
     * @param $event_names An array with all events that will be declared
     */
    public function __construct($event_names = array())
    {   foreach($event_names as $e)
            self::declare_event($e);
    }
    
    //! Declare an event on this dispatcher
    /**
     * @param $event_name The name of the event to declare
     * @return @b true if it was declared otherwise @b false
     */
    public function declare_event($event_name)
    {   // Must be a valid value
        if (empty($event_name))
            return false;
            
        // Must not exist
        if ($this->has_event($event_name))
             return false;

        // Create listeners pool for this event
        $this->event_listeners[$event_name] = array();
        return true;
    }
    
    //! Check if an event is already declared
    /**
     * @param $event_name The name of the event
     * @return @b true if exists otherwise @b false
     */
    public function has_event($event_name)
    {   return array_key_exists($event_name, $this->event_listeners);    }

    //! Get all events
    /**
     * @return An array with all events declared at this dispatcher.
     */
    public function get_events()
    {   return array_keys($this->event_listeners);   }

    //! Check if event has a specific listener
    /**
     * @param $event_name The name of the event or @b NULL for global listeners.
     * @param $callable The callable of the listener.
     * @return @b true if it has listener otherwise @b false
     */
    public function has_listener($event_name, $callable)
    {   // Check global listeners
        if ($event_name === NULL)
            return (array_search($callable, $this->global_listeners, true) !== false);

        // Must exist
        if (! $this->has_event($event_name))
             return false;

        return (array_search($callable, $this->event_listeners[$event_name], true) !== false);
    }
    
    //! Get all listeners of an event
    /**
     * @param $event_name The name of the event or @b NULL for global listeners.
     * @return @b Array with callbacks or @b NULL if event is unknown.
     */
    public function get_listeners($event_name)
    {   // Check for global listeners
        if ($event_name === NULL)
            return $this->global_listeners;

        // Event must not exist
        if (! $this->has_event($event_name))
             return NULL;
        return $this->event_listeners[$event_name];
    }
    
    //! Connect on event
    /** 
     * @param $event_name The name of the event or @b NULL for @i any event.
     * @param $callable The callable object to be called when the event is raised.
     * @return @b true if it was connected succesfully or @b false on any error.
     */
    public function connect($event_name, $callable)
    {   // Check if it wants to connect to global listeners
        if ($event_name === NULL)
        {   if (array_search($callable, $this->global_listeners, true) === false)
            {   $this->global_listeners[] = $callable;
                return true;
            }
            return false;
        }
        
        // Check that the event exists
        if (! $this->has_event($event_name))
            return false;
            
        // Check if this callable has been registered again
        if (array_search($callable, $this->event_listeners[$event_name], true) !== false)
            return false;

        $this->event_listeners[$event_name][] = $callable;
        return true;
    }

    //! Disconnect from event
    /** 
     * @param $event_name The name of the event or @b NULL for @i any event.
     * @param $callable The callable object that was passed on connection.
     * @return @b true if it was disconnected succesfully or @b false on any error.
     */
    public function disconnect($event_name, $callable)
    {   
        // Check if it wants to disconnect from global listeners
        if ($event_name === NULL)
        {   $cb_key = array_search($callable, $this->global_listeners, true);

            if ($cb_key !== false)
            {   unset($this->global_listeners[$cb_key]);
                $this->global_listeners = array_values($this->global_listeners);
                return true;
            }
            return false;
        } 

        // Check if it is a known event
        if (! $this->has_event($event_name))
            return false;
            
        // Check if this listener exists
        if (($cb_key = array_search($callable, $this->event_listeners[$event_name], true)) === false)
            return false;
        
        // Remove listener
        unset($this->event_listeners[$event_name][$cb_key]);
        $this->event_listeners[$event_name] = array_values($this->event_listeners[$event_name]);
        return true;
    }
        
    //! Notify all listeners for this event
    /** 
     * @param $event_name The name of the event or @b NULL for @i any event.
     * @param $callable The callable object that was passed on connection.
     * @return @b true if it was disconnected succesfully or @b false on any error.
     */
    public function notify($event_name, $parameters = NULL)
    {   if (! $this->has_event($event_name))
            return false;

        // Create event object
        $e = new Event($event_name, 'notify', $parameters);
        
        // Call event listeners
        foreach($this->event_listeners[$event_name] as $callback)
        {   call_user_func($callback, $e);
            $e->processed = true;   // Mark it as processed
        }
        
        // Call global listeners
        foreach($this->global_listeners as $callback)
        {   call_user_func($callback, $e);
            $e->processed = true;   // Mark it as processed
        }

        return $e;
    }

    //! Filter value through listeners
    public function filter($event_name, & $value)
    {   if (! $this->has_event($event_name))
            return false;
        
        // Call event listeners
        foreach($this->event_listeners[$event_name] as $callback)
        {    call_user_func($callback, $event_name, $value);    }

        // Call global listeners
        foreach($this->global_listeners as $callback)
        {    $value = call_user_func($callback, $event_name, $value);    }
    }
}

?>
