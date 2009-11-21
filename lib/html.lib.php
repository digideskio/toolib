<?php

/**
 * @brief Class for exporting HTML Tags
 * @author sque
 *
 */
class HTMLTag
{
	// Set the default render mode
	public static $default_render_mode = 'html';

	// List of html element that do not close
	private static $html_single_tags = array('hr', 'br', 'img', 'meta', 'link');
	
	//! The actual tag
	public $tag = '';
	
	//! The parameter of tag
	public $attributes = array();
	
	//! The childs of this tag
	public $childs = array();
	
	//! Mode of rendering
	public $render_mode = NULL;
	
	//! Escape html special entities from text blocks
	public $esc_html= true;
	
	//! Escape new lines to br
	public $esc_nl = false;
	
	//! General Constructor
	/**
	 * 
	 * How to use it:
	 * new HTMLTag($name_and_options, [array $extra_options], [$child1], [$child2])
	 * 
	 * name_and_options: can be
	 * - 'div'
	 * - 'div class="test"'
	 * .
	 * $extra_options can be
	 * - array(class, test);
	 * .
	 */
	public function __construct()
	{		
		$args = func_get_args();
		
		if (count($args) == 0)
			throw new InvalidArgumentException('HTMLTag constructor must take at leaste one argument with the tag');
		
		// Analyze tag options
		$tag_exploded = explode(' ', $args[0]);
		foreach(array_slice($tag_exploded, 1) as $option)
			if ($option == "")
				continue;
			else if ($option == 'html_escape_off')
				$this->esc_html = false;
			else if ($option == 'html_escape_on')
				$this->esc_html = true;
			else if ($option == 'nl_escape_on')
				$this->esc_nl = true;
			else if ($option == 'nl_escape_off')
				$this->esc_nl = false;
			else if ($option == 'html_mode')
				$this->render_mode = 'html';
			else if ($option == 'xhtml_mode')
				$this->render_mode = 'xhtml';
			else
			{	// Directly html attribute
				$options = explode('=', $option);
				if (count($options) == 2)
					$this->attributes[trim($options[0], ' "')] = trim($options[1], ' "');
				
				else
					$this->attributes[trim($options[0], ' "')] = '';
			}
				
		$this->tag = $tag_exploded[0];
		
		// Add more arguments
		foreach(array_slice($args, 1) as $arg)
		{	if (is_array($arg))
				$this->attributes = array_merge($this->attributes, $arg);
			else if (is_string($arg) || is_object($arg))
				$this->childs[] = $arg;			
		}
	}
	
	//! Custom nl2br to be compliant with pre-php5.3 version
	public static function nl2br($string, $is_xhtml)
	{	return str_replace("\n", $is_xhtml?'<br />':'<br>', $string);		
	}
	
	//! Render tag attributes
	public static function render_tag_attributes($hash_map)
	{ 	$str = '';
		foreach($hash_map as $attr_key => $attr_value)
			$str .= sprintf(' %s="%s"', esc_html($attr_key), esc_html($attr_value));
		return $str;
	}
	
	//! Set an attribute
	public function attr($attr_name, $attr_value = NULL)
	{	if ($attr_value === NULL)
			return isset($this->attributes[$attr_name])?$this->attributes[$attr_name]:NULL; 
		$this->attributes[$attr_name] = $attr_value;
		return $this;
	}
	
	//! Check if it has a class
	public function has_class($class_name)
	{	return in_array($class_name, explode(' ', $this->attr('class')), true);	}
	
	//! Remove class
	public function remove_class($class_name)
	{	$classes = explode(' ', $this->attr('class'));
		if (($key = array_search($class_name, $classes)) === FALSE)
			return $this;
		unset($classes[$key]);
		$this->attr('class', implode(' ', array_values($classes)));
		return $this;
	}
	
	//! Add a class in tag
	public function add_class($class_name)
	{	if ($this->has_class($class_name))
			return $this;
			
		if (($prev_class = $this->attr('class')) === NULL)
			$this->attr('class', $class_name);
		else
			$this->attr('class', $prev_class . ' ' . $class_name);	
		return $this;
	}
	
	//! Append a child
	public function append()
	{	if (func_num_args() > 0)
			foreach(func_get_args() as $arg)
				$this->childs[] = $arg;
		return $this;
	}
	
	//! Prepend a child
	public function prepend()
	{	if (func_num_args() > 0)
		{	$this->childs = array_merge(func_get_args(), $this->childs);	}				
		return $this;
	}
	
	//! Append to another tag
	public function appendTo($tag)
	{	$tag->append($this);
		return $this;
	}
	
	//! Prepend to another tag
	public function prependTo($tag)
	{	$tag->prepend($this);
		return $this;
	}
	
	//! Find all ancestors with this tag name
	public function getElementsByTagName($tag)
	{	$elements = array();
	
		foreach($this->childs as $child)
			if (is_object($child))
			{	if ($child->tag == $tag)
					$elements[] = $child;
				$elements = array_merge($elements, $child->getElementsByTagName($tag));
			}
		return $elements;
	}
	
	//! Render this tag
	public function render()
	{	if ($this->render_mode !== NULL)
			$render_mode = &$this->render_mode;
		else
			$render_mode  = &self::$default_render_mode;
			
		$str = "<{$this->tag}" . self::render_tag_attributes($this->attributes);
	
		// Fast route for non closable HTML tags
		if (($render_mode == 'html') && in_array($this->tag, self::$html_single_tags))
		
			return $str . ' >';

		// Fast route for XHTML tags with no childs
		if (($render_mode == 'xhtml') && (count($this->childs) == 0))
			return $str . ' />';
		$str .= '>';
		
		// Add childs
		foreach($this->childs as $child)
		{	
			if (is_string($child))
			{	if ($this->esc_html) $child = esc_html($child);
				if ($this->esc_nl) $child = self::nl2br($child, ($render_mode == 'xhtml'));
				$str .= $child;
			}
			else
				$str .= (string)$child;
		}
		$str .= "</{$this->tag}>";
		return $str;
	}
	
	//! Render nested text-only
	/**
	 * @remark Text will not be escaped.
	 * @return unknown_type
	 */
	public function render_text()
	{	$str = '';
		foreach($this->childs as $child)
		{
			if (is_string($child))
				$str .= $child;
			else
				$str .= $child->render_text();
		}
		return $str;
	}
	
	//! Auto render when converted to string
	public function __toString()
	{	return $this->render();		}
	
	
	/**********************************
	 *  Sub-module for parent tracking
	 */
	
	private static $tracked_parents = array();
	
	//
	public function push_parent($append_to_current = false)
	{	if ($append_to_current)
			$this->append_to_default_parent(); 
		array_push(self::$tracked_parents, $this);
		return $this;
	}
	
	//
	public static function pop_parent($total_pops = 1)
	{	if ($total_pops <= 1)
			return array_pop(self::$tracked_parents);
		
		array_pop(self::$tracked_parents);
		return self::pop_parent($total_pops - 1);	
	}
	
	//! Get current parent returns pointer to HTMLTag or null if none.
	public static function get_current_parent()
	{	if (count(self::$tracked_parents) > 0)
		{
			$last = end(self::$tracked_parents);
			reset(self::$tracked_parents);
			return $last;
		}
		return NULL;
	}
	
	//! Append to default parent
	public function append_to_default_parent()
	{	if (($parent = HTMLTag::get_current_parent()) != NULL)
		{
			$parent->append($this);
			return true;
		}
		return false;
	}
}

//! Create a new tag
function tag()
{	$args = func_get_args();
	return call_user_func_array(
		array(new ReflectionClass('HTMLTag'), 'newInstance'),
		$args
	);
}

//! Create a tag and echo it
function etag()
{	$args = func_get_args();
	$tag = call_user_func_array('tag', $args);
	if (!$tag->append_to_default_parent())
		echo $tag;
	return $tag;
}

//! Human readable dump of a tag tree
function dump_tag($tag, $prepend = "")
{
	echo $prepend . $tag->tag . "\n";
	
	foreach ($tag->childs as $child)
	{	if (is_object($child))
			dump_tag($child, $prepend . "  ");
		else
			echo $prepend . "  " . '"' . $child . "\"\n";
	}
		
}

//! HTML Document constructor
/** 
    This is an HTML Document constructor, it will create a valid HTML doc
    based on user suplied data.
    
    @par Example
    @code
    $mypage = new HTMLDoc();
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
    $mypage = new HTMLDoc();
    
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
    $mypage = new HTMLDoc();
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
class HTMLDoc
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
    public $char_set = 'utf-8';
    
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
	    //$this->js_refs[] = sprintf('<script src="%s" type="text/javascript"></script>', $script);	    
	    $this->js_refs[] = $script;
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
    {   $is_xhtml = (HTMLTag::$default_render_mode == 'xhtml');
    	
    	// DocType
    	if ($is_xhtml)
	        $r = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"' .
    	    	' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' .
        		'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" >';
	    else
	        $r = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN"><html>';
        
        // HEAD
        $r .= '<head>';
        //$r .= tag('head',
	        // Character set
	    if ($is_xhtml)
	    	$r .= tag('meta', array('http-equiv' => 'Content-type', 'content' => 'application/xhtml+xml;charset=' . $this->char_set));
	    else
	    	$r .= tag('meta', array('http-equiv' => 'Content-type', 'content' => 'text/html;charset=' . $this->char_set));

		// Extra meta data
		foreach($this->extra_meta as $meta_attrs)
			$r .= tag('meta', $meta_attrs);
		
		// Link external references
        foreach ($this->link_refs as $link_attrs)
        	$r .= tag ('link', $link_attrs);
        
        // Javascript exteeernal references
        foreach ($this->js_refs as $js_ref)
			$r .= tag('script type="text/javascript"',  array('src' => $js_ref));            
  
        // Title
        $r .= tag('title', $this->title);
        $r .= '</head>';
        $r .= tag('body', 
        		tag('div id="wrapper" html_escape_off',
        			$this->body_data
        		)
        	);
        $r .= '</html>';
        return $r;
    }
};

?>