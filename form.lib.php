<?php
/*************************************************************
    @title A fast way to create forms with input validation
    
    
 *************************************************************/

//! A base function to create forms
class Form
{
    private $fields;
    private $form_id;
    protected $options;
    private static $last_autoid = 0;
    
    //! Construct the form object
    /**
        @param $fields an array with parameters
        array() with all options
        - display: The text that will be displayed in front of the input
        - type: [Default=text] The type of input control. ('text', 'password', 'dropbox', 'radio', 'checkbox', 'line', 'custom', 'list')
        - options: [Default=array()]
            An array with all options in case of type that can accept options (dropbox, radio).
            The array is given in format array(key1 => text1, key2 => text2)
            - key: The key name of this option. The result of the field is the @bkey value of the selected option.
            - text: [Default: key] The text to be displayed for this option.
            .
        - mustselect: [Default: true] If the type of input has options, it force you to set an option
        - value: [Optional] A predefined value for the input that will be displayed, or the key of the selection.
        - usepost: [Default=true, Exception type=password] If true it will assign value the posted one from user.
        - hint: [Optional] A hint message for this input.
        - regcheck: [Optional] A regular expression that field must pass to be valid.
        - onerror: [Optional] The error that will be displayed if regcheck fails.
        .
        
        @param $options parameters
        - title The title of the form
        - submit The caption of the submit button
        - class An array with extra classes
        .
    */
    public function __construct($fields = array(), $options = array())
    {   $this->fields = $fields;
        $this->options = $options;
        $this->form_id = 'form_gen_' . (Form::$last_autoid ++);
        
        // Initialize default values for options
        if (!isset($this->options['class']))
            $this->options['class'] = array();
        
        // Initialize default values for fields
        foreach($this->fields as & $field)
        {   // Type
            if (!isset($field['type']))
                $field['type'] = 'text';
            
            // Usepost
            if (!isset($field['usepost']))
                $field['usepost'] = ($field['type'] == 'password')?false:true;
                
            // Options
            if (!isset($field['options']))
                $field['options'] = array();
            
            // Must select
            if (!isset($field['mustselect']))
                $field['mustselect'] = true;
            
        }
        unset($field);
        
        // Process post
        $this->process_post();
        
        // Render the form
        $this->render();
    }
    
    //! Process the posted data
    private function process_post()
    {   // Check if the form is posted
        if ((!isset($_POST['submited_form_id'])) ||
            ($_POST['submited_form_id'] != $this->form_id))
        {
            // Call user function when there is no post
            if (method_exists($this, 'on_nopost'))
                $this->on_nopost();
            return false;
        }

        // Store values and check if they are valid
        foreach($this->fields as $k => & $field)
        {   if (isset($_POST[$k]))
                $field['value'] = $_POST[$k];
            else
                $field['value'] = "";

            // Regcheck
            $field['valid'] = true;
            if (isset($field['regcheck']))
            {
                if (preg_match($field['regcheck'], $field['value']) == 0)
                {   $field['valid'] = false;
                    if (isset($field['onerror']))
                        $field['error'] = $field['onerror'];
                }
            }
            
            // Mustselect check
            if (($field['valid']) &&
                (($field['type'] == 'dropbox') || ($field['type'] == 'radio'))
                && ($field['mustselect']))
            {
                if (empty($field['value']))
                {   $field['valid'] = false;
                    if (isset($field['onerror']))
                        $field['error'] = $field['onerror'];
                }
            }


        }
        unset($field);

        // Call user function for post processing
        if (method_exists($this, 'on_post'))
            $this->on_post();
            
        // Call on_valid if form is valid
        if ($this->is_valid() && method_exists($this, 'on_valid'))
            $this->on_valid();
    }

    //! Get a value of a field
    protected function get_field_value($fname)
    {
        if (isset($this->fields[$fname]) && (isset($this->fields[$fname]['value'])) )
            return $this->fields[$fname]['value'];
    }
    
    //! Check if a field is valid
    public function is_field_valid($fname)
    {
        if (isset($this->fields[$fname]) &&
            isset($this->fields[$fname]['valid']))
                return $this->fields[$fname]['valid'];

        return false;
    }
    
    //! Invalidate a field and set an error message
    public function invalidate_field($fname, $error_msg)
    {
        if (isset($this->fields[$fname]))
        {
            $this->fields[$fname]['valid'] = false;
            $this->fields[$fname]['error'] = $error_msg;
        }
    }
    
    //! Check if all fields of form are valid
    public function is_valid()
    {   foreach($this->fields as $k => $field)
            if(!$this->is_field_valid($k))
                return false;
        return true;
    }
    
    //! Set error on a field
    protected function set_field_error($fname, $error)
    {
        if(!isset($this->fields[$fname]))
            return false;
        $this->fields[$fname]['error'] = $error;
    }
    
    //! Get a refernece to a field
    public function field($fname)
    {   if(!isset($this->fields[$fname]))
            return false;
        return $this->fields[$fname];
    }
    
    //! Set a field display
    public function set_field_display($fname, $display)
    {   if(!isset($this->fields[$fname]))
            return false;
        $this->fields[$fname]['display'] = $display;
    }
        
    //! Render the form
    private function render()
    {   echo '<form method="post">';
        echo '<div class="ui-form';
        foreach($this->options['class'] as $cls)
            echo ' ' . esc_html($cls);
        echo '">';
        echo '<input type="hidden" name="submited_form_id" value="' . esc_html($this->form_id) .'">';
        echo '<table>';
        if (isset($this->options['title']))
            echo '<tr><th colspan="2">'. esc_html($this->options['title']);
        
        // Render all fields
        foreach($this->fields as $id => $field)
        {   
            echo '<tr><td';
            // Line type
            if ($field['type'] == 'line')
            {
                echo ' colspan="2"><hr>';
                continue;
            }
            
        
            // Show input pertype
            if (isset($field['error']) || isset($field['hint']))
                  echo ' rowspan="2" ';
            echo '>' . (isset($field['display'])?esc_html($field['display']):'') . '<td>';
            switch($field['type'])
            {
            case 'text':
            case 'password':
                echo '<input name="' . esc_html($id) . '" type="' . esc_html($field['type']) . '" ';
                if (($field['usepost']) && isset($field['value'])) echo 'value="' . esc_html($field['value']) . '"';
                echo '>';
                break;
            case 'radio':
                foreach($field['options'] as $opt_key => $opt_text)
                {
                    echo '<input name="' . esc_html($id) . '" ';
                    if (($field['usepost']) && isset($field['value']) && ($opt_key == $field['value']))
                        echo 'checked="checked" ';
                    echo 'type="radio" value="' . esc_html($opt_key) . '">&nbsp;' . esc_html($opt_text) . '&nbsp;&nbsp;&nbsp;&nbsp;';
                }
                break;
            case 'dropbox':
                echo '<select name="' . esc_html($id) . '">';
                foreach($field['options'] as $opt_key => $opt_text)
                {
                    echo '<option ';
                    if (($field['usepost']) && isset($field['value']) && ($opt_key == $field['value']))
                        echo 'selected="selected" ';
                    echo ' value="' . esc_html($opt_key) . '">' . esc_html($opt_text) . '</option>';
                }
                echo '</select>';
                break;
            case 'checkbox':
                echo '<input type="checkbox" name="' . esc_html($id) .'" ';
                if (($field['usepost']) && isset($field['value']) && ($field['value'] == 'on'))
                        echo 'checked="checked" ';
                echo '>';
                break;
            case 'custom':
                if (isset($field['value']))
                    echo $field['value'];
                break;
            }
            
            if (isset($field['error']))
                echo '<tr><td><span class="ui-form-error">' . esc_html($field['error']) . '</span>';
            else if (isset($field['hint']))
                echo '<tr><td><span class="ui-form-hint">' . esc_html($field['hint']) . '</span>';
        }
        
        // Render button
        echo '<tr><td colspan="2"><input type="submit"' . 
            (isset($this->options['submit'])?'value="' . esc_html($this->options['submit']) . '"':'') . '>';
        echo '</table>';
        echo '</div>';
        echo '</form>';
    }
    
    //! Dont display the form
    public function hide()
    {
        $this->options['hideform'] = true;
    }
}

?>
