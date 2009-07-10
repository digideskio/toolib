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
    
	//! Link external references 
	private $link_refs = array();
	
	//! Extra meta tags
	private $extra_meta = array();
	
    //! Contents of body
    private $body_data = '';

    //! Character set of body content
    public $char_set = 'UTF-8';
    
    //! Title of html page
    public $title = '';
    
    //! Add a external reference entry
    /** 
    	@param $href The position of this external reference
    	@param $type Specifies the MIME type of the linked document
    	@param $rel Specifies the relationship between the current document and the linked document
    	@param $extra_html_attribs An array with extra attributes that you want to set at this link element.\n
    		Attributes are given as an associative array where key is the attribute name and value is the 
    		attribute value.\n
    */
    public function add_link_ref($href, $type, $rel, $extra_html_attribs = array())
    {	$link_el = $extra_html_attribs;
    	$link_el['href'] = $href;
    	$link_el['type'] = $type;
    	$link_el['rel'] = $rel;
    	$this->link_refs[] = $link_el;
    	return true;
    }	
    
    //! Add a new meta data entry
    /** 
    	@param $content The value of meta element's content attribute
    	@param $extra_html_attribs An array with extra attributes that you want to set at this meta element.\n
    		Attributes are given as an associative array where key is the attribute name and value is the 
    		attribute value.\n
    		Example:\n
    		@code
    		$myhtml->add_meta('text/html;charset=ISO-8859-1', array('http-equiv' => 'Content-Type'));
    		@endcode
    */
    public function add_meta($content, $extra_html_attribs = array())
    {
    	$meta_el = $extra_html_attribs;
    	$meta_el['content'] = $content;
    	$this->extra_meta[] = $meta_el;
    }
    
    //! Add a favicon of this webpage
    public function add_favicon($icon, $type = NULL)
    {	if ($type === NULL)
    	{	$ext = pathinfo($icon, PATHINFO_EXTENSION);
    	
    		if ($ext == 'gif')
    			$type = 'image/gif';
    		else if ($ext == 'png')
    			$type = 'image/png';
    		else if ($ext == 'ico')
    			$type = 'image/vnd.microsoft.icon';
    		else
    			return false;
    	}
    	
    	return $this->add_link_ref($icon, $type, 'icon');
    }
    
	//! Add a javascript reference
	public function add_ref_js($script)
	{
	    $this->js_refs[] = sprintf('<script src="%s" type="text/javascript"></script>', $script);
	}
	
	//! Add a style sheet reference
	public function add_ref_css($script)
	{	return $this->add_link_ref($script, "text/css", "stylesheet");
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
        $r .= '<meta http-equiv="Content-type" content="text/html; charset=' . $this->char_set . '" >';

		// Extra meta data
		foreach($this->extra_meta as $meta)
		{	
			$r .= '<meta';
			foreach($meta as $name => $value)
				$r .= ' ' . esc_html($name) . '="' . esc_html($value) . '"';
			$r .= ' >';
		}
		
		// Link external references
        foreach ($this->link_refs as $link_ref)
        {	$r .= '<link';
			foreach($link_ref as $name => $value)
				$r .= ' ' . esc_html($name) . '="' . esc_html($value) . '"';
            $r .= ' >';
        }
        
        // Javascript exteeernal references
        foreach ($this->js_refs as $js_ref)
            $r .= $js_ref;
            
        // Title
        $r .= '<title>' . $this->title . '</title>';
        $r .= '</head><body><div id="wrapper">';
        $r .= $this->body_data;
        $r .= '</div></body></html>';
        return $r;
    }
};
?>