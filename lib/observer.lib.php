<?php
/**
    @file Implementation of the "observer" design pattern
*/

//! Abstract definition of the Observable from the "Observer" concept.
/**
    An Observable may expose multiple actions that can be observed by Observer objects.
    An Observer can observe at the same time one or multiple actions of the same or
    different Observable objes. When an action is performed all the registered 
    Observer s are informed.
    
    To use the Observer concept you have first to create an Observable target and
    declare its actions. We will also create a demo algorithm that will singal its
    actions.
    @code
    class Cat extends Observable
    {
        public function __construct()
        {
            Observable::declareAction('hungry');
            Observable::declareAction('bored');
        }
        
        public function random_mood()
        {
            if (my_random() > 1)
                Observable::singalAction('hungry');
            else
                Observable::singalAction('bored');
        }
    }
    @endcode
    
    Now you can create Observers to observe actions of this object. At our example
    scenario we will create Bob and Alice. Bob must be responsible to feed the Cat
    and Alice is responsible to play with the cat.
    @code
    class Bob extends Observer
    {
         protected function onAction ($object, $action, $args)
         {    if ($action == 'hungry')
                 feed($object);
         }
         protected function onObservableDestruction ($observable)
         {    // Wail for the lost cat    
         }
    }
    
    class Alice extends Observer
    {
         protected function onAction ($object, $action, $args)
         {    if ($action == 'bored')
                 entertain($object);
         }
         protected function onObservableDestruction ($observable)
         {    // Wail for the lost cat    
         }

    }
    @endcode
    
    To use this classe we can do it like this.
    @code
    $tako = new Cat();
    $bob_thefirst = new Bob();
    $alice_thefirst = new Alice();
    
    $bob_thefirst->observeAction($tako, 'hungry');
    $alice_thefirst->observeAction($tako, 'bored');
    @endcode
*/
abstract class Observable
{
    //! An associative array with all the actions and their observers.
    private $actions = array();
    
    //! Destructor
    public function __destruct()
    {   $obs_to_inform = array();
    
        // Find all the unique observers
        foreach($this->actions as $action => $observers)
            foreach($observers as $observer)
                if (! in_array($observer, $obs_to_inform, true))
                    $obs_to_inform [] = $observer;
        
        // Inform all observers about the loss
        foreach($obs_to_inform as $observer)
            $observer->iocObservableDestroyed($this);
    }
    
    //! @name Action management
    //! @{
    
    //! Declare an action that can be observed
    /**
        The best place to declare actions that can
        be observed is at the initialization of the actual
        observable object.
    */
    protected function declareAction($action)
    {   // Must be a valid value
        if (empty($action))
            return false;
            
        // Must not exist
        if ($this->actionExists($action))
             return false;
         
        $this->actions[$action] = array();
        return true;
    }
    
    //! Check if an action is already declared
    public function actionExists($action)
    {    return array_key_exists($action, $this->actions);
    }
    
    //! List all actions of this object
    /**
        @return An associative array, that for each entry the key is the name of
        the action and the value is an array with all registered observers of this
        action.
    */
    public function listActions()
    {    return $this->actions;    }
    
    //! Signal an action
    /**
        All the observers of this action will be informed for the action.
    */
    public function signalAction($action, $args = array())
    {   if (! $this->actionExists($action))
            return false;
            
        foreach($this->actions[$action] as $observer)
            $observer->iocActionSignaled($this, $action, $args);
    }
    //! @}
    
    //! [Internal Objects Call]
    /**
        @note You must never call this function.
    */
    public function iocAddObservation($observer, $action)
    {    if (! $this->actionExists($action))
            return false;
            
        // Check if this observer has been registered again
        if (array_search($observer, $this->actions[$action], true) !== FALSE)
            return false;

        $this->actions[$action][] = $observer;
        return true;
    }
    
    //! [Internal Objects Call]
    /**
        @note You must never call this function.
    */
    public function iocRemoveObservation($observer, $action)
    {   if (! $this->actionExists($action))
            return false;
        
        // Check if this observer exists
        if (($ob_key = array_search($observer, $this->actions[$action], true)) === FALSE)
            return false;
        
        // Remove observer
        unset($this->actions[$action][$ob_key]);
        $this->actions[$action] = array_values($this->actions[$action]);
        return true;
    }
};

//! Abstract definition of the "Observer" concept.
/**
    For a complete expleation of the Observer concept and how to use those
    classes check Observable description.
*/    
abstract class Observer
{
    //! Actions that are currently observed
    private $observed = array();
    
    //! Destructor
    public function __destruct()
    {    
        // Stop all observations
        $copy_observed = $this->observed;
        foreach($copy_observed as $obs)
            $this->dontObserveAction($obs[0], $obs[1]);
    }
    
    //! [Internal Objects Call]
    /**
        @note You must never call this function.
    */
    public function iocActionSignaled($observable, $action, $args)
    {    $this->onAction($observable, $action, $args);    }
    
    //! [Internal Objects Call]
    /**
        @note You must never call this function.
    */
    public function iocObservableDestroyed($observable)
    {    // Delete all observations of this object
        $obs_to_remove = array();
        
        foreach($this->observed as $obs)
            if ($obs[0] === $observable)
                $obs_to_remove[] = $obs;
                
        var_dump(count($obs_to_remove));
        foreach($obs_to_remove as $obs)
            $this->dontObserveAction($obs[0], $obs[1]);

        $this->onObservableDestruction($observable);

    }

    //! Start observing an action of an Observalbe object.
    public function observeAction($observable, $action)
    {   if (!$observable->iocAddObservation($this, $action))
            return false;
        $this->observed [] = array($observable, $action);
        return true;
    }
    
    //! Stop observing an action
    public function dontObserveAction($observable, $action)
    {   if (!$observable->iocRemoveObservation($this, $action))
            return false;
        
        if (($key = array_search(array($observable, $action), $this->observed, true)) === FALSE)
            return false;
        
        unset($this->observed[$key]);
        $this->observed = array_values($this->observed);
        return true;
    }
        
    //! Called when an action is performed
    protected abstract function onAction($object, $action, $args);
    
    //! Called when an observed object is destroyed
    protected abstract function onObservableDestruction($observable);
};

?>