<?php

//! An abstract web form constructor
/**
    Form provides a fast way to create input forms with server-side validation. It
    supports multiple types of input and an abstract way to create your own custom
    types. At the same time it provides form validation from mandatory fields to
    regular expressions checks for text boxes. If form is properly validated,
    developper can add custom code for finally processing data using special functions
    in the derived class.
    
    @par Special Functions
    Special functions are function that can be declared in the derived class, and get
    executed in special cases. There is no explicit dependency on these functions and
    Form will work too without declaring any of them, however you should probably define
    at least one to add some "real" functionality on the Form.
    \n\n
    - @b on_post():\n
        Called when form received data from the user. It does not guarantee that the form
        is properly validated.
    - @b on_valid():\n
        It is called when form received data from user and all the fields are valid. This
        function is called after on_post()
    - @b on_nopost():\n
        Called when the form was requested using GET and no data where posted from the user.
        (When user see the form for the first time)
    .

    @par Example
    To create a form object you must create a derived class that will initialize Form
    and populate any special function that it needs.
    @code
    class NewUserForm extends Form
    {
        public __construct()
        {    Form::__construct(
                array(
                    'username' => array('display' => 'Username'),
                    'password1' => array('display' => 'Password', type='password'),
                    'password2' => array('display' => 'Retype password', type='password')
                ),
                array('title' => 'New user', 'submit' => 'create')
        }
        
        public function on_valid()
        {
            // Add your code here
        }
    }
    
    // Display form
    $nufrm = new NewUserForm();
    @endcode
    
    @par Flow Chart
    Form using the same object, it displays the form, accepts user input, validates
    data and executes user defined code for form events. I will try to visualize
    the order of events and data processing.\n\n    
    @b Life-Cycle: The form's life-cycle limits in the constructor and only there.
    @code
    
    $nufrm = new NewUserForm();    // < Here, any input data is processed, is validated,
                                   ///  user events are executed and finally the form is rendered.
    @endcode
    \n
    A detailed flow chart is followed, displaying what happens inside the constructor of Form.
    @verbatim
   ( Form Constructor Start )
            |
            V
           / \
        /       \         +------------------+
      / User Post \ ----->| Call on_nopost() |
      \   Data    /  NO   +------------------+
        \       /                  V
           \ /                     |
            |                      |
            V                      |
  +---------------------+          |
  |  Process User Data  |          |
  | (validate regexp,   |          |
  |  validate mandatory |          |
  |  data, save values) |          |
  +---------------------+          |
            |                      |
            V                      |
  +---------------------+          |
  |    Call on_post()   |          |
  | (Here user can do   |          |
  |  extra validations  |          |
  |  and invalidate any |          |
  |  fields)            |          |
  +---------------------+          |
            |                      |
            V                      |
           / \                     |
        /       \                  V
      /  Is Form  \ -------------->+
      \   VALID?  /  NO            |
        \       /                  |
           \ /                     |
            |                      |
            V                      |
  +---------------------+          |
  |   Call on_valid()   |          |
  +---------------------+          |
            |                      V
            +<---------------------+
            |                      
            V                      
           / \                     
        /       \                  
      /  Is Form  \ -------->+
      \  Visible? /  NO      |
        \       /            |
           \ /               |
            |                |
            V                |
    +-------------------+    |
    |  Render Form      |    |
    +-------------------+    |
            |                V
            +<---------------+
            V
  ( Form Constructor End )
    @endverbatim
    @todo
        - Add support for multiple buttons
        .
*/
class Form
{
    //! An array with all fields
    private $fields;
    
    //! The id of the form
    private $form_id;
    
    //! The options of the form
    protected $options;

    //! Internal increment for creating unique form ids.
    private static $last_autoid = 0;
    
    //! Construct the form object
    /**
        @param $fields An associative array with all fields of the form, fields must be given in the same
            order that will be rendered too. The key of each of record defines the unique id of the field
            and the value is another associative array with the parameters of the field.\n
        <b> The supported field parameters are: </b>
        - display: The text that will be displayed at the left of the input
        - type: [Default=text] The type of input control. Currently implemented are
            ('text', 'password', 'dropbox', 'radio', 'checkbox', 'line', 'custom')
        - options: [Default=array()]
            An array with all the value options that will be displayed to user.
            This is only needed for types that have mandatory options like (dropbox, radio).
            The array is given in format array(key1 => text1, key2 => text2)
            - key: The key name of this option. The result of the field is the @b key value of the selected option.
            - text: [Default: key] The text to be displayed for this option.
            .
        - mustselect: [Default: true] If the type of input has options, it force you to set an option
        - value: [Optional] A predefined value for the input that will be displayed, or the key of the selection.
        - usepost: [Default=true, Exception type=password] If true it will assign value the posted one from user.
        - hint: [Optional] A hint message for this input.
        - regcheck: [Optional] A regular expression that field must pass to be valid.
        - onerror: [Optional] The error that will be displayed if regcheck fails.
        .\n\n
        A small example for $fields is the following
        @code
        new Form(
            array(
                'name' => array('display' => 'Name', type='text'),
                'sex' => array('display' => 'Sex', type='radio', options = array('m' => 'Male', 'f' => 'Female'))
            )
        );
        @endcode
        
        @param $options An associative array with the options of the form.\n
        Valid array keys are:
        - title The title of the form.
        - submit The caption of the submit button.
        - css [Default = array()] An array with extra classes
        .\n\n
        @p Example:
        @code
        new Form(
            array(... fields ...),
            array('title' => 'My Duper Form', 'submit' => 'Ok')
        );
        @endcode
    */
    public function __construct($fields = array(), $options = array())
    {   $this->fields = $fields;
        $this->options = $options;
        $this->form_id = 'form_gen_' . (Form::$last_autoid ++);
        
        // Initialize default values for options
        if (!isset($this->options['css']))
            $this->options['css'] = array();
        
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

    //! Get the user given value of a field
    /**
        If a this is the first time viewing the firm, the
        function will return the predefined value of this field. (if any)
    */
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
    
    //! Check if form is valid
    /**
        It will check if all fields are valid, and if they are,
        it will return true.
    */
    public function is_valid()
    {   foreach($this->fields as $k => $field)
            if(!$this->is_field_valid($k))
                return false;
        return true;
    }
    
    //! Set the error message of a field
    /**
        This does not invalidates fields, it just changes
        the error message.
    */
    protected function set_field_error($fname, $error)
    {
        if(!isset($this->fields[$fname]))
            return false;
        $this->fields[$fname]['error'] = $error;
    }
    
    //! Get a refernece to the internal field object
    /**
       The reference returned will be an array
       with the parameters of the fields, for 
       the parameterse of the field you can see
       __construct().
   */
    public function field($fname)
    {   if(!isset($this->fields[$fname]))
            return false;
        return $this->fields[$fname];
    }
    
    //! Change the display text of a field
    /**
        Display text is the text on the left of the field
        that describes it.
    */
    public function set_field_display($fname, $display)
    {   if(!isset($this->fields[$fname]))
            return false;
        $this->fields[$fname]['display'] = $display;
    }
        
    //! Render the form
    private function render()
    {   echo '<form method="post">';
        echo '<div class="ui-form';
        foreach($this->options['css'] as $cls)
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
    
    //! Don't display the form
    /**
        Makes the form hidden and will not render. You can use
        this function from any special function to prevent
        form rendering.
    */
    public function hide()
    {
        $this->options['hideform'] = true;
    }
}

?>
