<?php
//! The smallest division of a Layout is the LayoutSection
/**
    A section can hold other child sections and extra html content.
    LayoutSections are created from their parent using the section()
    function.
*/
class LayoutSection
{
    //! Flag if any ob is captured at the moment
	private static $ob_capturing = false;
	
	//! The name of this section
	private $name;
	
	//! The assigned function to render this section
	private $render_func;
	
	//! The sub sections of this section
	protected $sections;
	
	//! Extra html attributes for the \<div /\> element
	/**
	    Attributes are given in associative array and translated
	    in html attributes from array(key => value, ...) to \<div key="value" .. \>
	*/
	public $extra_div_attribs;

	//! The contents of this div
	/**
	    Raw html data that will be appended in the body of this
	    section after the subsections.
	*/
	public $data;
			
	//! Creates a layout section <b> [dont use this] </b>
	/**
	    To create a layout section use section() function on the parent
	    section or on Layout, it will create the section for you and will
	    return a valid LayoutSection object.
	*/
	public function __construct($name)
	{	$this->name = $name;	
		$this->sections = array();
		$this->extra_div_attribs = array();
		$this->render_func = false;
	}

    //! Get a section handler or create the section if it does not exist
    /**
        It will search if there is a direct subsection child with that
        and will return the reference. Otherwise it will create a subsection
        with this name and will return the reference.
    */
    public function section($name)
    {	if (!isset($this->sections[$name]))
            $this->sections[$name] = new LayoutSection($name);
        return $this->sections[$name];
    }
    
    //! Shortcut to section()
    /**
        @see section() for details
    */
    public function s($name)
    {   return $this->section($name);   }

	
	//! Get contents from the Output Buffer
	/**
	    It will start capturing the output buffer and any
	    data displayed will be appended in the contets of this
	    section. To stop capturing a section you can call stop_capturing_ob().
	    
	@remarks
	    To switch capturing from one section to another you don't need
	    to stop and start. get_from_ob() will check if any previous sections
	    are capturing the ob and will stop them.
	*/
	public function get_from_ob()
	{   $this->stop_capturing_ob();
        
        LayoutSection::$ob_capturing = true;
	    ob_start(array($this, 'append_data'));    // Start a new one
	    
	    return $this;
	}
	
	//! Stop any section from capturing the Output buffer
	/**
	    If any section is capturing the ob using the get_from_ob(), 
	    it will be stopped.
	*/
	public static function stop_capturing_ob()
	{   // dbg::printl('Layout('. $this->name .')::stop_capturing_ob()');
	    if (LayoutSection::$ob_capturing)
	    {   
            ob_end_clean();                         // Stop previous capturing
            // dbg::printl('  stopped capture');
	        LayoutSection::$ob_capturing = false;
	    }
	}
	
	//! Directly append data at this section
	public function append_data($dt)
	{ // dbg::printl('Layout('. $this->name . ')::append_data('. $dt .')');  
	  $this->data .= $dt; }
	
	//! Render this and all children and return html code
	/**
	    @note If you want to render all the layout you should see
	        Layout::render(), this is for special cases only.
	*/
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
	          $this->get_from_ob();
	          call_user_func($this->render_func);
	          $this->stop_capturing_ob();
	    }
	    return $rendered . $this->data . '</div>';
	}

	//! Assign a function that will render this section
	/**
	    The assigned function will be called when render()
	    is requested on this section.
	@note Render function should export all data to the output buffer
	    using standard functions like printf, echo etc...
	*/
	public function set_render_func($func)
	{     $this->render_func= $func;  return $this;}

};

//! A class for designing layouts
/**
    A Layout is an nested set of LayoutSection. Each section is nothing
    more than an html \<div\> that has a user defined id and user defined
    content. To create a section use the section() function.
    @remarks Layout stands out for supporting ob (output buffer) capturing,
        making it very easy to reorganize sections or populating them
        in any order you want.
        
    For example we will render a layout that has a "header", "main" and "footer" section.
    
    @code
    // Create an object of our layout
    $mylayout = new Layout();
    
    // Declare sections (The declaration order is the rendering order too)
    $mylayout->section("header");
    $mylayout->section("main");
    $mylayout->section("footer");
    
    // Populating sections
    $mylayout->section("footer")->get_from_ob();
    echo '<center>Copyright (C) nobody</center>';
    
    $mylayout->section("header")->get_from_ob();
    echo '<H1> The best page ever!</h1>';
    
    $mylayout->section("main")->get_from_ob();
    echo 'bla bla bla bla';
    
    // Render and display page
    echo $mylayout->render();
    @endcode
    
    This will output the following html code
    @code
    <div id="header">
        <H1> The best page ever!</h1>
    </div>
    <div id="main">
        bla bla bla bla
    </div>
    <div id="footer">
        <center>Copyright (C) nobody</center>
    </div>
    @endcode
*/
class Layout extends LayoutSection
{
    //! Constructor
    public function __construct()
    {   parent::__construct("main");
    }

    //! Render the layout
    /**
        @return The string containing the rendered html code.
    */
    public function render()
    {
        $this->stop_capturing_ob();
        return  parent::render("", true);
    }
};
?>
