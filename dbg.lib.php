<?php
/**********************************************
    Debug Interface for PHP
 
    Version:    0.1.3  
    Author:     sque
    Licence:    General Public Licence
    Copyright:  2009
***********************************************/
 
//! A static function for debugging
class dbg
{
    //! Internal implementation of html escaping
    private static function esc_html($text, $nl = false)
    {
    	$n_text = htmlspecialchars($text, ENT_QUOTES);
    	if ($nl)
         	$n_text = str_replace("\n", '<br>', $n_text);    	   
    	return $n_text;
    }

    //! The internal console log
    private static $log_table = array();
    
    //! A parameter if it is enabled
    private static $enabled = false;
    
    //! A parameter if it is hooked
    private static $hooked  = false;

    //! Dump a variable to the console log
    public static function dump($exp)
    {   dbg::$log_table[] = array('type' => 'user', 'text' => print_r($exp, true));    }
    
    //! printf like function
    public static function logf()
    {	dbg::$log_table[] = array('type' => 'user', 'text' => call_user_func_array('sprintf', $func_get_args()));    }

    //! Echo a string at the debug buffer and add a new line at the end
    public static function log($str = "", $type = 'user')
    {	dbg::$log_table[] = array('type' => $type, 'text' => $str . "\n");  }

    //! Error handler for generic errors
    public static function error_handler($errno, $errstr, $errfile, $errline, $errcontext )
    {   //echo 'ERROR catched '. sprintf("[%08d] %s. at %s(%s)", $errno, $errstr, $errfile, $errline);

        dbg::$log_table[] = array('type' => 'error', 'stacktrace' => debug_backtrace(), 'text' => sprintf("[%08d] %s. at %s(%s)", $errno, $errstr, $errfile, $errline));
       
        return false;
    }
    
    //! Exception handler
    public static function exception_handler(Exception $exc)
    {   echo 'EXCEPTION HANDLER';
        dbg::$log_table[] = array('type' => 'error', 'stacktrace' => debug_backtrace(), 'text' => sprintf("Uncaught exception!", $errno, $errstr, $errfile, $errline));
        
    }

    //! Enable output panel
    public static function enable_output()
    { 
        if (!dbg::$hooked)
        {   // Add initial log
            dbg::log("Debug Interface for PHP v0.1.3");
            
            register_shutdown_function(array('dbg', 'render_panel'));
            set_error_handler(array('dbg', 'error_handler'));
            //dbg::printl('Setting exception handler: ');
            //dbg::dump(set_exception_handler(array('dbg', 'exception_handler')));
            //dbg::dump(set_exception_handler(array('dbg', 'exception_handler')));
            dbg::$hooked = true;
        }
        
        dbg::$enabled = true;
    }
    
    //! Disable panel output
    public static function disable_output()
    {
        dbg::$enabled = false;
    }
    
    //! Check if debug is enabled
    public static function is_enabled()
    {   return dbg::$enabled;   }

    //! Called by debug engine to render the panel
    public static function render_panel()
    {   if (!dbg::is_enabled())
            return false;
            
        // Close any buffer
        while(ob_list_handlers())
            ob_end_clean();
        flush();
        
        //Count errors
        $total_errors = 0;
        foreach(dbg::$log_table as $log_entry)
            if ($log_entry['type'] == 'error')
                $total_errors += 1;
?>
<script type="text/javascript">

function hide_show_tab($id)
{   var el_tab = document.getElementById("dbg-tab-" + $id);
    var el = document.getElementById("dbg-" + $id);

    if (el_tab.getAttribute('selected') != null)
    {
        el.style.display = 'none';
        el_tab.removeAttribute('selected');
        return;
    }
    
    // Hide all
    document.getElementById('dbg-log').style.display = 'none';
    document.getElementById('dbg-variables').style.display = 'none';

    // Remove the selected tab
    document.getElementById('dbg-tab-log').removeAttribute('selected');
    document.getElementById('dbg-tab-variables').removeAttribute('selected');
        
    el_tab.setAttribute('selected', 'true');
    el.style.display = 'table-row';
}

function select_var(ev)
{   var i =0;
    var el;

    // Remove previous selected
    while((el = document.getElementById('dbg-var-name-' + String(i))) != null)
    {
        el.removeAttribute('selected');
        i++;
    }
    ev.originalTarget.setAttribute('selected', '');
    var sel_count = ev.originalTarget.getAttribute('count');
    
    // Hide all values
    i = 0;
    while((el = document.getElementById('dbg-var-value-' + String(i))) != null)
    {
        el.removeAttribute('selected');
        i++;
    }
    document.getElementById('dbg-var-value-' + String(sel_count)).setAttribute('selected', '');
}
</script>
    
<style type="text/css">
#dbg-panel
{
    width: 100%;
    height: auto;
    border: 1px solid #FF0000;
    font-size: 8pt;
    font-family: Arial;
}

table#dbg-tabs
{
    width: 100%;
    color: #00FF00;
    background-color: #000000;
    border-spacing: 0px;
}

table#dbg-tabs tr#dbg-tabs-header th, table#dbg-tabs tr#dbg-tabs-header td
{   border-bottom: 2px solid #444444;
}

tr#dbg-tabs-header
{   font-size: 8pt;
}

tr#dbg-tabs-header th
{
    cursor: pointer;
    width: 150px;
}

tr#dbg-tabs-header th:active
{
    background-color: #AEAEAE;
    color: #000000;
}


tr#dbg-tabs-header th[selected]
{
    background-color: #336633;
}

table#dbg-tabs th#dbg-tab-hide
{   width: 10px;
    padding-left: 3px;
    padding-right: 3px;
}

ol#dbg-log-list
{
    font-family: monospace;
    font-size: 9pt;
}

ol#dbg-log-list li
{
    border-bottom: 1px solid #224422;
    color: #ffffff;
}

ol#dbg-log-list li.dbg-error-entry
{
    position:relative;
    font-weight: bold;
    color: #ff0000;
    cursor: pointer;
}

li.dbg-error-entry span.stack-trace
{
    top: 20px;
    left:10px;
    position: absolute;
    border: 2px solid #9A9A9A;
    background-color: #444444;
    padding: 5px;
    color: #FFFFFF;
    cursor: pointer;
    z-index: 15;
    display: none;
}

li.dbg-error-entry span.stack-trace ol
{    list-style: disc;  }

li.dbg-error-entry span.stack-trace ol li
{
    color: #00FF00;
    background-color: #444444;
    padding-bottom: 4px;
}

li.dbg-error-entry:hover span.stack-trace
{   display: block; }

ul#dbg-var-names
{
    color: #ffffff;
    font-size: 9pt;
    list-style: none;
    height: auto;
    width: auto;
    padding: 2px;
    margin: 0px;
}

ul#dbg-var-names li:hover
{
    cursor: pointer;
    text-decoration: underline;
}

ul#dbg-var-names li[selected]
{
    font-weight: bold;
    background-color: #334433;
}

td#dbg-var-names
{
    border-right: 1px solid #334433;
}

table#dbg-var-table td
{
    width: 100px;
    vertical-align: top;
    color: #ffffff;
}

span.dbg-var-value
{
    display: none;
}

span.dbg-var-value[selected]
{
    display: block;
}

span.dbg-error-entry
{
    font-weight: bold;
    color: #ff0000;
    text-decoration: blink;
}

</style>


<div id="dbg-panel">
<table id="dbg-tabs">
<tr id="dbg-tabs-header">
    <th id="dbg-tab-hide">[+]
    <th id="dbg-tab-log" onclick="hide_show_tab('log')">Log<?php if($total_errors > 0) echo "<span class=\"dbg-error-entry\"> ($total_errors)</span>"; ?>
    <th id="dbg-tab-variables" onclick="hide_show_tab('variables')">Global Variables
    <td>&nbsp;
<tr id="dbg-log" style="display: none;"><td colspan="4">
<ol id="dbg-log-list">
<?php
        foreach(array_reverse(dbg::$log_table) as $log_entry)
        {
            switch($log_entry['type'])
            {
            case 'error':
                echo '<li class="dbg-error-entry">' . dbg::esc_html($log_entry['text'], true);
                if (isset($log_entry['stacktrace']))
                {
                    echo '<span class="stack-trace">Stack trace<ol>';
                    foreach($log_entry['stacktrace'] as $stack_entry)
                        if (isset($stack_entry['file']))
                            printf('<li> %s()<br>at %s (%s)</li>', $stack_entry['function'], $stack_entry['file'], $stack_entry['line'] );
                        else
                            printf('<li> %s()</li>', $stack_entry['function']);
                    echo '</ol></span>';
                }
                echo '</li>';
                break;
            default:
                echo '<li>' . dbg::esc_html($log_entry['text'], true) . '</li>';
                break;

            }
        }
        echo '</ol>';
        
        // Variables table
        echo '<tr id="dbg-variables" style="display: none;"><td colspan="4">';
        echo '<table id="dbg-var-table"><tr><th>Name<th>Value';
        echo '<tr><td id="dbg-var-names"><ul id="dbg-var-names">';
        $count = 0;
        $values_spans = "";
        foreach ($GLOBALS as $k => $v)
        {
            if (($k == 'GLOBALS') || ($k =='HTTP_ENV_VARS') || ($k =='HTTP_POST_VARS') || ($k =='HTTP_GET_VARS') || ($k =='HTTP_COOKIE_VARS') || ($k =='HTTP_SERVER_VARS') || ($k =='HTTP_POST_FILES') || ($k =='HTTP_SESSION_VARS'))
                continue;
            echo '<li id="dbg-var-name-' .$count . '" count="'. $count . '" onclick="select_var(event)">';
            echo dbg::esc_html($k);
            echo '</li>';
            $values_spans .= '<span class="dbg-var-value" id="dbg-var-value-' . $count . '">' . dbg::esc_html(print_r($v, true), true) . '</span>';
            $count ++;
        }
        echo '<td class="var-value"><pre id="var-out">' . $values_spans . '</pre>';
        echo '</table>';

        echo '</table>';
        echo '</div>';
    }
}

?>
