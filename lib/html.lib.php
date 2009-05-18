<?php

//! HTML Page constructor
/**
    This is an HTML page constructor, it will create a valid HTML doc
    based on user suplied data.
    
    @par Example
    @code
    $mypage = new HTMLPage();
    $mypage->title = 'My Super Duper WebSite';
    $mypage->add_ref_js('/js/jquery.js');
    $mypage->add_ref_css('/themes/fantastic.css');
    
    // Add data to body
    $mypage->append_data('Hello World');
    
    // Render and display page
    echo $mypage->render();
    @endcode
    
    @par Capture from Output Buffer
    It is very difficult and ugly to write a webpage and use append_data(). In that case
    it is better to capture output buffer and append directly to body.
    \n
    An easy way to do it is:
    @code
    $mypage = new HTMLPage();
    
    // Auto append data to html content
    ob_start(array($mypage, 'append_data'));

    // Everything echoed here will be appended to html
    echo 'Hello world';
    
    // Stop capturing and echo final page
    ob_end_clean();
    echo $mypage->render();
    @endcode
    
    @par Auto render page "trick"
    There is a trick to autorender html page at the end of each page. The example
    assumes that you have a file named layout.php where you create the basic layout
    of the site and it is included from any page.
    \n
    @b layout.php    
    @code
    $mypage = new HTMLPage();
    $mypage->title = 'My Super Duper WebSite';
    $mypage->add_ref_js('/js/jquery.js');
    $mypage->add_ref_css('/themes/fantastic.css');
    
    // Auto append data to html content
    ob_start(array($mypage, 'append_data'));

    // Create a guard object that on destruction it will render the mypage
    class auto_render_html
    {   public function __destruct()
        {   global $mypage;
            ob_end_clean();
            echo $mypage->render();
        }
    }
    $auto_render = new auto_render_html();
    @endcode
    \n
    @b index.php
    @code
    require_once('layout.php');
    
    // Everything written here will be appended to body
    echo 'Hello World';
    
    // At the end of the script all the objects will be destroyed, $auto_render too which
    // will render the HTML page "magically"
    @endcode
*/
class HTMLPage
{
    //! Javascript references
    private $js_refs = array();
    
    //! Style sheet references
    private $css_refs = array();
   
    //! Contents of body
    private $body_data = '';

    //! Character set of body content
    public $char_set = 'UTF-8';
    
    //! Title of html page
    public $title = '';
    
	//! Add a javascript reference
	public function add_ref_js($script)
	{
	    $this->js_refs[] = sprintf('<script src="%s" language="JavaScript"></script>', $script);
	}
	
	//! Add a style sheet reference
	public function add_ref_css($script)
	{
    	$this->css_refs[] = sprintf('<link rel="stylesheet" type="text/css" href="%s">', $script);
	}
    
    //! Append data in the body content
    public function append_data($str)
    {
        $this->body_data .= $str;
    }

    //! Render html code and return a string with the whole page
    public function render()
    {   // DocType
        $r = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN"><html><head>';
        
        // Character set
        $r .= '<meta http-equiv="Content-type" value="text/html; charset=' . $this->char_set . '" />';

        // Title
        $r .= '<title>' . $this->title . '</title>';
        foreach ($this->css_refs as $css_ref)
            $r .= $css_ref;
        foreach ($this->js_refs as $js_ref)
            $r .= $js_ref;
        $r .= '</head><body><div id="wrapper">';
        $r .= $this->body_data;
        $r .= '</div></body></html>';
        return $r;
    }
};
?>