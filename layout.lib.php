<?php
	require_once('html.lib.php');
	
	class LayoutSection
	{
		private static $ob_capturing = false;
		private $name;
		public $data;
		protected $sections;
		public $extra_div_attribs;
		public $render_func;
		
		//! Create a layout section
		public function __construct($name)
		{	$this->name = $name;	
			$this->sections = array();
			$this->extra_div_attribs = array();
			$this->render_func = false;
		}

        //! Get a section handler or create the section if it does not exist
        public function section($name)
        {	if (!isset($this->sections[$name]))
                $this->sections[$name] = new LayoutSection($name);
            return $this->sections[$name];
        }
        
        //! Shortcut to section()
        public function s($name)
        {   return $this->section($name);   }

		
		//! Switch here
		public function switch_here()
		{   dbg::log('Layout('. $this->name .')::switch_here()');
		    $this->stop_capturing();
            
            LayoutSection::$ob_capturing = true;
		    ob_start(array($this, 'append_data'));    // Start a new one
		    
		    return $this;
		}
		
		//! Stop capturing
		public function stop_capturing()
		{   // dbg::printl('Layout('. $this->name .')::stop_capturing()');
		    if (LayoutSection::$ob_capturing)
		    {   
                ob_end_clean();                         // Stop previous capturing
                // dbg::printl('  stopped capture');
    	        LayoutSection::$ob_capturing = false;
    	    }
    	    return $this;
		}
		
		//! Add data at the section
		public function append_data($dt)
		{ // dbg::printl('Layout('. $this->name . ')::append_data('. $dt .')');  
		  $this->data .= $dt; }
		
		//! Render this and all children()
		public function render($path = "", $only_children = false)
		{   $rendered = "";
		
		    // Special casing when rendering only children
		    if ($only_children)
		    {   
    		    foreach ($this->sections as $sec)
	       		    $rendered .= $sec->render();
		        return $rendered;
		    }

		    // Start div
		    $rendered .= sprintf('<div id="%s"', $this->name);//(($path == "")?$this->name:$path."-".$this->name));
		    foreach($this->extra_div_attribs as $k => $v)
		        $rendered .= sprintf(' %s=\"%s\"', $k, $v);
		    $rendered .= '>';
		        
            // Render childrens
            foreach ($this->sections as $sec)
    		    $rendered .= $sec->render($path . $this->name);
		    
		    // Call render function if any
		    if ($this->render_func)
		    {
		          $this->switch_here();
		          call_user_func($this->render_func);
		          $this->stop_capturing();
		    }
		    return $rendered . $this->data . '</div>';
		}
		
		//! Set a render function on this section
		public function set_render_func($func)
		{     $this->render_func= $func;  return $this;}
		
		
	};
	
    //! A class for designing layouts
    class Layout extends LayoutSection
    {
        // Construct 
        public function __construct()
        {   parent::__construct("main");
            
        	// Hook on prerender
            HTML::push_prerender_hook(array($this, 'render'));
        }

        // Render layout        
        public function render()
        {
            $this->stop_capturing();
            echo parent::render("", true);
        }
    };
?>
