<?php

//! HTML 
class HTML
{
    private static $js_array;
    private static $css_array;
    private static $auto_render;
    private static $body_data;
    private static $pre_render_func;
    public static $title;

	//! Add a new references to a JavaScript file
	static public function add_ref_jscript($script)
	{
	    HTML::$js_array[] = sprintf('<script src="%s" language="JavaScript"></script>',
	        $script);
	}
	
	//! Add a new reference to a CSS file
	static public function add_ref_css($script)
	{
    	HTML::$css_array[] = sprintf('<link rel="stylesheet" type="text/css" href="%s">',
	        $script);
	}
    
    //! Capture any on going output and embed it in the html object
    static public function start()
    {
  	    // Initialize variables
	    HTML::$js_array = array();
	    HTML::$css_array = array();
	    HTML::$body_data = "";
	    HTML::$title = "";
	    HTML::$auto_render = new html_auto_render();
	    HTML::$pre_render_func = array();

        // Capture output buffer
        ob_start();
    }
    
    //! Write more body code
    static public function append_body($str)
    {
        $this->body .= $str;
    }

    //! Display html output
    static public function render()
    {   // Call preprender function
    	foreach(HTML::$pre_render_func as $func)
            call_user_func($func);
            		
        // Get body data
        $body_data = ob_get_clean();

        // Print header
        echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN"><html><head>';
        echo '<meta http-equiv="Content-type" value="text/html; charset=UTF-8" />';

        echo '<title>';
        echo HTML::$title;
        echo '</title>';
        foreach (HTML::$css_array as $css_ref)
            echo $css_ref;
        foreach (HTML::$js_array as $js_ref)
            echo $js_ref;
        echo '</head><body><div id="wrapper">';
        echo $body_data;
        echo '</div></body></html>';
    }
    
    //! Tell the HTML object to skip auto rendering at the end of script
    static public function skip_autorender()
    {   HTML::$auto_render->render_at_end = false;    }
    
    //! Push a pre-render hook
    static public function push_prerender_hook($func)
    {	 array_push(HTML::$pre_render_func, $func);	
    }
    
    //! Pop a pre-render hook
    static public function pop_prerender_hook()
    {	 return array_pop(HTML::$pre_render_func);	}
};

//! This object is used to detect the end of a page so that html is all printed out
class html_auto_render
{
    public $render_at_end;

    public function __construct()
    {   $this->render_at_end = true;
    }
    
    // When the object is destroyed it renders HTML
    public function __destruct()
    {   if ($this->render_at_end)
            HTML::render();
    }
};

if (!(isset($html_no_autostart) && ($html_no_autostart == true)))
    HTML::start();
?>
