<?php

//! Dispatch events to their handlers
/** 
    EventDispatcher holds an array with all events. Events can
    be declared at the dispatcher using declare_event() function. The
    concept is that an object raises_events and the registered observers
    get informed using the callback function that they previously defined.
    
    @note There are is a special event, that you cannot raise it or declare it
        the '*'. This is an alias for @b any event.
        
    @b Example \n
    To understand the concept of EventDispatcher we will demonstrate it with Cat,
    Bob and Alice. Let's say Bob wants to observe TheirCat if it is hungry to feed it,
    and Alice wants to observe TheirCat to see if it is bored so that she entertains it.
    
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
                $this->events->raise_event('hungry', $this);
            else
                $this->events->raise_event('bored', $this);
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
    $TheirCat->events->observe_event('hungry', array($Bob, 'feed_pet'));
    $TheirCat->events->observe_event('bored', array($Alice, 'entertain_pet'));
    @endcode
    
    When ever $TheirCat->random_mood() the appropriate callback of Bob or Alice will be called
    to handle the event.
*/
class EventDispatcher
{
    //! An array with all events and their callbacks.
    private $events = array();
    
    //! Create an EventDispatcher object and declare the events.    
    public function __construct($events = array())
    {   // A special events that means "any" event 
        self::declare_event("*");
        
        foreach($events as $e)
            self::declare_event($e);
    }
    
    //! Declare an event on this dispatcher
    public function declare_event($event)
    {   // Must be a valid value
        if (empty($event))
            return false;
            
        // Must not exist
        if ($this->event_exists($event))
             return false;
         
        $this->events[$event] = array();
        return true;
    }
    
    //! Check if an event is already declared
    public function event_exists($event)
    {    return array_key_exists($event, $this->events);
    }
    
    //! Observe an event
    /** 
        @param $event The name of the event that you want to observe
        @param $callback The callback function to be called when the event
            is raised. Callback function may get parameters too depending on the event.
            Check http://www.php.net/manual/en/language.pseudo-types.php#language.types.callback
            for more information on how to format callback type in PHP.
    */
    public function observe_event($event, $callback)
    {   if (! $this->event_exists($event))
            return false;
            
        // Check if this callback has been registered again
        if (array_search($callback, $this->events[$event], true) !== FALSE)
            return false;

        $this->events[$event][] = $callback;
        return true;
    }

    //! Observe all events of this dispatcher
    /** 
        This is an alias for observe_event("*", $callback);
    */
    public function observe_all($callback)
    {
        return self::observe_event("*", $callback);
    }

    //! Stop observing an event
    public function stop_observing_event($event, $previous_callback)
    {    if (! $this->event_exists($event))
            return false;
            
        // Check if this observer exists
        if (($cb_key = array_search($previous_callback, $this->events[$event], true)) === FALSE)
            return false;
        
        // Remove observer
        unset($this->events[$event][$cb_key]);
        $this->events[$event] = array_values($this->events[$event]);
        return true;
    }
    
    //! Stop observing any event
    /** 
        This is an alias for stop_observing_event('*', $previous_callback)
    */
    public function stop_observing_all($previous_callback)
    {    return self::stop_observing_event('*', $previous_callback);    }
        
    //! Raise a declared event and inform all observers of it
    public function raise_event($event, $args = NULL)
    {   if ($event == '*')
            return false;
            
        if (! $this->event_exists($event))
            return false;
        
        // Call specific observes of this event    
        foreach($this->events[$event] as $callback)
        {    call_user_func($callback, $event, $args);    }
        
        // Call specific observes of this event
        foreach($this->events['*'] as $callback)
        {    call_user_func($callback, $event, $args);    }
    }
    
}

?>