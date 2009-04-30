<?php


//! Jquery tabs or accordion
class jq_tabs
{
    private $tab_count;
    private $base_id;
    private $tab_title;
    private $tab_content;
    private $sel_tab;
    private $mode;
    private $options;
    
    //! Construct a jquery tabs
    public function __construct($tab_title, $mode = 'tabs', $options = '{}', $base_id = 'jq_tabs')
    {
        $this->tab_count = count($tab_title);
        $this->base_id = $base_id;
        $this->tab_title = $tab_title;
        foreach($this->tab_title as $k => $tt)
            $this->tab_content[$k] = '';
        $this->sel_tab = -1;
        $this->mode = $mode;
        $this->options = $options;
    }
    
    //! Function to add data to the tab
    public function append_data($dt)
    {
        $this->tab_content[$this->sel_tab] .= $dt;
    }

    //! Switch the nth rendering tab
    public function switch_tab($key)
    {
        // Check if tab is valid
        if (!isset($this->tab_title[$key]))
            return false;

        // Close previous capture            
        if ($this->sel_tab != -1)
        {
            ob_end_clean();
            $this->sel_tab == -1;
        }
        $this->sel_tab = $key;
        ob_start(array($this, 'append_data'));
    }
    
    //! Set the contents of a tab directly
    public function set_tab_content($key, $content)
    {
        // Check if tab is valid
        if (!isset($this->tab_title[$key]))
            return false;
       
        $this->tab_content[$key] = $content;
    }
    
    //! Render tabs
    private function render_tabs()
    {
        // Render tabs
        echo '<script type="text/javascript">$(function() {$("#' . $this->base_id .'").tabs(' . $this->options. ');});</script>';

        echo '<div id="' . $this->base_id . '">';
        echo '<ul>';
        foreach($this->tab_title as $k => $tt)
        {
            echo '<li><a href="#' . (is_numeric($k)?$this->base_id . '-' . $k:$k) . '">' . $tt;
            echo '</a></li>';
        }
        echo '</ul>';
        foreach($this->tab_content as $k => $tc)
        {
            echo '<div id="' . (is_numeric($k)?$this->base_id . '-' . $k:$k) . '">' . $tc;
            echo '</div>';
        }
        echo '</div>';
    }
    
    //! Render accordion
    private function render_accordion()
    {
        // Render tabs
        echo '<script type="text/javascript">$(function() {$("#' . $this->base_id .'").accordion(' . $this->options. ');});</script>';

        echo '<div id="' . $this->base_id . '">';
        foreach($this->tab_title as $k => $tt)
        {
            echo '<h3><a href="#">' . $tt . '</a></h3>';
            if (is_numeric($k))
                echo '<div>';
            else
                echo '<div id="' . $k . '">';
            echo $this->tab_content[$k] .'</div>';
        }

        echo '</div>';
    }
    
    //! End tab section and render the result
    public function end()
    {
        // Close previous capture            
        if ($this->sel_tab != -1)
        {
            ob_end_clean();
            $this->sel_tab == -1;
        }

        switch($this->mode)
        {
        case 'accordion':
            $this->render_accordion();
            break;
        default:
            $this->render_tabs();
        }
    }
};

?>