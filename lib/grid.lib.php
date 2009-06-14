<?php

//! Create an interactive grid of data
/**
	Grid is an HTML element that can read data from a source and display
	them on a grid control. It also supports custom actions for each data.

	Special functions are functions that can be declared at childern class
	and are called by grid at special occusions to filter or alter data.
	Currently the following special functions are supported:
		- @b on_data_request([column-id], [row-id], [data-record]) Called for each cell that belongs to
			a column with option 'autofeed' set false. The function must return the string with data
			that will displayed in cell.
			@note The returned data are NOT ESCAPED before displaying them !! You must escape your data 
			using esc_html() if they don't include any html code.
		- @b on_data_mangle([column-id], [row-id], [data]) Called for each cell that belong to a column
			with option 'mangle' set true. This function is used for further data mangling. It is called
			after feeding data from data array and just before displaying data.
			@note It will not be called if data are fed using the @b on_data_request()
			@note The returned data are NOT ESCAPED before displaying them !! You must escape your data 
			using esc_html() if they don't include any html code.
		- @b on_click([column-id], [row-id], [data-record]) If this function is declared, it will be called
			when a user clicks on a cell that belongs to column which has the option 'observeclick' set true.			
		- @b on_filter_row([row-id], [data-record]) If this function is declared, Grid will execute
			it for each row of data. If it returns true the row will be shown in the Grid, if
			it returns false the row will be fitered out.
 */
class Grid
{
	//! All the columns of the grid
	public $columns;
	
	//! Data of the grid
	public $data;
	
	//! Options of the grid
	public $options;
	
	//! Grid id
	public $grid_id;
	
	//! Last auto grid id
	private static $last_autoid = 0;
	
	//! Constructor of a grid
	/**
		
		@param $columns An associative array with a description of each column. Each item of this array
			must have another array with the options of the column. This array must be associative and the options
			are passed as key => value
			- caption: [Default=(column-id)] The caption of this column
			- htmlattribs: [Default=array()] Attributes of HTML TD element for all rows.
			- datakey: [Default=(column-id)] If the feed is done by data parameter then key of the records associative array/object
				that data will be read from. This can work only if autofeed option is true.
			- autofeed: [Default=true] Get data from supplied $data at the constructor else,
				get data from user implemented on_data_request()
			- mangle: [Default=false] Call on_data_mangle() with data and display the result of this function.
			- observeclick: [Default=false] If set true the on_click will be executed when a user clicks any cell of this
				column.
		@param $options An associative array with all options of the grid.
			- css: [Default=array('ui-grid')] array of extra class names
			- caption: [Default=''] The caption of the table.
			- httpmethod: [Default='post'] The method to use when user interacts with grid.
			- headerpos: [Default='top'] The position that headers will be rendered, possible values are
				@b 'top', @b 'bottom', @b 'both' or @b 'none'.
		@param $data = null An array with data for each row. Each item of the array can be another array with all info of records
				or an object with properties.
	*/
	public function __construct($columns, $options, $data = null)
	{
		$this->columns = $columns;
		$this->data = $data;
        $this->options = $options;
        $this->grid_id = 'grid_gen_' . (Grid::$last_autoid ++);
        
        // Initialize default values for options
        if (!isset($this->options['css']))
            $this->options['css'] = array('ui-grid');
        if (!isset($this->options['caption']))
            $this->options['caption'] = '';
		if (!isset($this->options['httpmethod']))
		    $this->options['httpmethod'] = 'post';
		if (!isset($this->options['headerpos']))
		    $this->options['headerpos'] = 'top';

        // Initialize default values for columns
        foreach($this->columns as $k => & $c)
        {   // Data key
            if (!isset($c['datakey']))
                $c['datakey'] = $k;
                
            // Caption
            if (!isset($c['caption']))
                $c['caption'] = $k;
                
            // Mangle
            if (!isset($c['mangle']))
                $c['mangle'] = false;

			// HTML Attribs
            if (!isset($c['htmlattribs']))
                $c['htmlattribs'] = array();

            // Autofeed
            if (!isset($c['autofeed']))
            	$c['autofeed'] = true;
            
            // Observe click event
            if (!isset($c['observeclick']))
            	$c['observeclick'] = false;
        }
        unset($c);
        
        // Process post
        $this->process_post();
        
        // Render the form
        $this->render();
	}
	
	//! Process the posted data
    private function process_post()
    {   // Check if this grid is posted
        if ((!isset($_POST['submited_grid_id'])) ||
            ($_POST['submited_grid_id'] != $this->grid_id))
        {
            // Call user function when there is no post
            if (method_exists($this, 'on_nopost'))
                $this->on_nopost();
            return false;
        }

        if ($_POST['action'] == 'click')
        {
            // Call user function when there is no post
            if (method_exists($this, 'on_click'))
                $this->on_click($_POST['colid'], $_POST['rowid'], $this->data[$_POST['rowid']]);
            return true;
        }
        
    }
    
    // Render column captions only
    private function render_column_captions()
    {
		// Render Headers
		echo '<tr>';
		foreach($this->columns as $c)
		{	echo '<th ' ;
			foreach($c['htmlattribs'] as $n => $v)
				echo esc_html($n) . '="' . esc_html($v) .'" ';

			echo '>' . esc_html($c['caption']);
		}
    }
    
	//! Render grid
	private function render()
	{	echo '<div class="';
        foreach($this->options['css'] as $cls)
            echo esc_html($cls) . ' ';
        echo '">';
        // Form hidden event
        echo '<form method="post" id="' . $this->grid_id . '">';
        echo '<input type="hidden" name="submited_grid_id" value="' . $this->grid_id . '">';
        echo '<input type="hidden" name="action" value="click">';
        echo '<input type="hidden" id="colid" name="colid" value="">';
        echo '<input type="hidden" id="rowid" name="rowid" value="">';
                
        echo '</form>';
        
        // Table
		echo '<table>';
        echo '<caption>' . esc_html($this->options['caption']) . '</caption>';
        
        // Render column captions again
        if (($this->options['headerpos'] == 'top') || ($this->options['headerpos'] == 'both'))
			$this->render_column_captions();
		
		// Render data
		// TODO: Accept both objects and array for records
		$even = false;
		foreach($this->data as $recid => $rec)
		{	
			// Check for filter
            if (method_exists($this, 'on_filter_row'))
                if (!$this->on_filter_row($recid, $rec))
                	continue;
		
			echo '<tr class="'. ($even?'ui-even':'');
			echo '">';
			$even = !$even;
			
			foreach($this->columns as $col_id => $c)
			{	
				if ($c['autofeed'])
					if (($c['mangle']) && (method_exists($this, 'on_data_mangle')))
						$data = $this->on_data_mangle($col_id, $recid, $rec->$c['datakey']);
					else
						$data = (empty($rec->$c['datakey']))?'&nbsp;':esc_html($rec->$c['datakey']);
				else if (method_exists($this, 'on_data_request'))
					$data = $this->on_data_request($col_id, $recid, $rec);
				echo '<td ';
				
				if ($c['observeclick'])
				{
					echo ' class="ui-clickable" ';
					echo ' onclick="$(\'form#' . $this->grid_id . 
						' input#colid\').val(\'' . $col_id . '\');' .
						' $(\'form#' . $this->grid_id . 
						' input#rowid\').val(\'' . $recid . '\');' .
						' $(\'form#' . $this->grid_id . '\').submit();" ';
				}
				echo '>' . $data;
			}
		}
		
		// Render column captions again
        if (($this->options['headerpos'] == 'bottom') || ($this->options['headerpos'] == 'both'))
			$this->render_column_captions();
			
		echo '</table></div>';
	}
}

?>