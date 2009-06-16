<?php

//! Create an interactive grid of data
/**
	Grid is an HTML element that can read data from a source and display
	them on a grid control. It also supports custom actions for each data.

	Special functions are functions that can be declared at childern class
	and are called by grid at special occusions to alter data or interact with grid.
	Currently the following special functions are supported:
		- @b on_custom_data([column-id], [row-id], [data-record]) Called for each cell that belongs to
			a column with option 'customdata' set true. The function must return the string with data
			that will displayed in cell.
			@note The returned data are NOT ESCAPED before displaying them !! You must escape your data 
			using esc_html() if they don't include any html code.
		- @b on_mangle_data([column-id], [row-id], [data]) Called for each cell that belong to a column
			with option 'mangle' set true. This function is used for further data mangling. It is called
			after feeding data from data array and just before displaying data.
			@note It will not be called if data are fed using the @b on_data_request()
			@note The returned data are NOT ESCAPED before displaying them !! You must escape your data 
			using esc_html() if they don't include any html code.
		- @b on_click([column-id], [row-id], [data-record]) If this function is declared, it will be called
			when a user clicks on a cell that belongs to column which has the option 'clickable' set true.			
		- @b on_header_click([column-id]) Trigered when user clicks on a header.
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
				that data will be read from. This can work only if customdata option is false.
			- customdata: [Default=false] Don't write data to cells of this column from $data but execute special
				function on_custom_data().
			- mangle: [Default=false] Call on_mangle_data() with data and display the result of this function.
			- clickable: [Default=false] If set true the on_click will be executed when a user clicks any cell of this
				column.
			- headerclickable: [Default=false] Make this header clickable and when they are clicked, the on_header_click() special
				function is executed.
		@param $options An associative array with all options of the grid.
			- css: [Default=array('ui-grid')] array of extra class names
			- caption: [Default=''] The caption of the table.
			- httpmethod: [Default='post'] The method to use when user interacts with grid.
			- headerpos: [Default='top'] The position that headers will be rendered, possible values are
				@b 'top', @b 'bottom', @b 'both' or @b 'none'.
			- pagecontrolpos: [Default='top'] The position that page controls will be rendered, possible values are
				@b 'top', @b 'bottom', @b 'both' or @b 'none'.
			- maxperpage: [Default=false] If this option is set to false, grid will not enter in non-paged mode.
				If this value is bigger than 0, then each page will have the size set by this value.
			- startrow: [Default=1] You can change the starting page of a grid, by setting a different value.
				Make sure that 'maxperpage' is set to non-zero value.
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
		if (!isset($this->options['pagecontrolpos']))
		    $this->options['pagecontrolpos'] = 'top';
		if (!isset($this->options['maxperpage']))
		    $this->options['maxperpage'] = false;
		if (!isset($this->options['startrow']))
		    $this->options['startrow'] = 1;

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

            // Customdata
            if (!isset($c['customdata']))
            	$c['customdata'] = false;
            
            // Observe click event
            if (!isset($c['clickable']))
            	$c['clickable'] = false;

            // Observe click event
            if (!isset($c['headerclickable']))
            	$c['headerclickable'] = false;            	
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
            // Call user function when there is click event
            if (method_exists($this, 'on_click'))
                $this->on_click($_POST['colid'], $_POST['rowid'], $this->data[$_POST['rowid']]);
            return true;
        }
        else if ($_POST['action'] == 'headerclick')
        {
            // Call user function when there is no post
            if (method_exists($this, 'on_header_click'))
                $this->on_header_click($_POST['colid']);
            return true;
        }
        else if ($_POST['action'] == 'changepage')
        {
        	$this->options['startrow'] = (is_numeric($_POST['startrow'])?$_POST['startrow']:1);
        }
        
    }
    
    // Render column captions only
    private function render_column_captions()
    {
		// Render Headers
		echo '<tr>';
		foreach($this->columns as $col_id => $c)
		{	echo '<th ' ;
		
			if ($c['headerclickable'])
			{
				echo ' class="ui-clickable" ';
				echo ' onclick="' .
				'$(\'form#' . $this->grid_id . 	' input[name=action]\').val(\'headerclick\'); ' .
				'$(\'form#' . $this->grid_id . ' input[name=colid]\').val(\'' . $col_id . '\');' .
				' $(\'form#' . $this->grid_id . '\').submit();" ';
			}
			foreach($c['htmlattribs'] as $n => $v)
				echo esc_html($n) . '="' . esc_html($v) .'" ';

			echo '>' . esc_html($c['caption']);
		}
    }
    
    // Render page controls
    private function render_page_controls()
    {	if ($this->options['maxperpage'] == false)
    		return;
    	
    	// Calculate view parameters
    	$totalrows = count($this->data);
    	$pagesize = $this->options['maxperpage'];
    	$startrow = $this->options['startrow'];
    	$endrow = (($startrow + $pagesize) <= $totalrows)?$startrow + $pagesize -1 : $totalrows;
    	$nextpage = ($endrow == $totalrows)?false:$endrow + 1;
    	$firstpage = ($startrow == 1)?false:1;
    	if ($startrow == 1)
    		$previouspage = false;
    	else 
	    	$previouspage = ($startrow > $pagesize)?$startrow - $pagesize:1;
	    if (($endrow == $totalrows) || (($totalrows - $startrow) < $pagesize))
			$lastpage = false;
		else
			$lastpage = floor($totalrows / $pagesize) * $pagesize + 1;

		// Render Headers
		echo '<table class="ui-grid-page-controls">';
		echo '<tr> <td align="left">';
		echo $startrow . ' &rarr; ' . $endrow . '&nbsp;&nbsp;of&nbsp;&nbsp;' . $totalrows . ' results';
		echo '<td width="250px" align="right">';
		
		
		// First button
		echo '<span class="ui-grid-first ';
		if ($firstpage != false)
			echo '" onclick="$(\'form#' . $this->grid_id . 	' input[name=action]\').val(\'changepage\'); ' .
				' $(\'form#' . $this->grid_id . ' input[name=startrow]\').val(\'1\');' .
				' $(\'form#' . $this->grid_id . '\').submit();" ';
		else
			echo ' ui-grid-inactive"';
		echo ' >First</span> &#149; ';
		
		// Previous button
		echo '<span class="ui-grid-previous ';
		if ($previouspage != false)
			echo '" onclick="$(\'form#' . $this->grid_id . 	' input[name=action]\').val(\'changepage\'); ' .
				' $(\'form#' . $this->grid_id . ' input[name=startrow]\').val(\'' . $previouspage .'\');' .
				' $(\'form#' . $this->grid_id . '\').submit();" ';
		else
			echo ' ui-grid-inactive"';
		echo ' >Previous</span> &#149; ';

		// Next button
		echo '<span class="ui-grid-next ';
		if ($nextpage != false)
			echo '" onclick="$(\'form#' . $this->grid_id . 	' input[name=action]\').val(\'changepage\'); ' .
				' $(\'form#' . $this->grid_id . ' input[name=startrow]\').val(\'' . $nextpage . '\');' .
				' $(\'form#' . $this->grid_id . '\').submit();" ';
		else
			echo ' ui-grid-inactive" ';
		echo ' >Next</span> &#149; ';
		
		// Last button
		echo '<span class="ui-grid-last ';
		if ($lastpage != false)
			echo '" onclick="$(\'form#' . $this->grid_id . 	' input[name=action]\').val(\'changepage\'); ' .
				' $(\'form#' . $this->grid_id . ' input[name=startrow]\').val(\'' . $lastpage . '\');' .
				' $(\'form#' . $this->grid_id . '\').submit();" ';
		else
			echo ' ui-grid-inactive"';
		echo ' >Last</span>';
		echo '</table>';
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
        echo '<input type="hidden" name="action" value="">';
        echo '<input type="hidden" name="colid" value="">';
        echo '<input type="hidden" name="rowid" value="">';
        echo '<input type="hidden" name="startrow" value="">';
                        
        echo '</form>';
        
		// Caption
		if (!empty($this->options['caption']))
			echo '<div class="ui-grid-title">' . esc_html($this->options['caption']) . '</div>';
		
        // Page controls
        if (($this->options['pagecontrolpos'] == 'top') || ($this->options['pagecontrolpos'] == 'both'))
	        $this->render_page_controls();

        // Grid list
		echo '<table class="ui-grid-list">';
        
        // Render column captions again
        if (($this->options['headerpos'] == 'top') || ($this->options['headerpos'] == 'both'))
    		$this->render_column_captions();
			
		// Render data
		$count_rows = 0;
		foreach($this->data as $recid => $rec)
		{	$count_rows += 1;
		
			// Pagenation
			if ($this->options['maxperpage'])
			{ 	if (($count_rows -$this->options['startrow']) > $this->options['maxperpage'])
					break;
				if ($count_rows < $this->options['startrow'])
					continue;
			}
				
			// Draw new line	
			echo '<tr class="'. (($count_rows % 2)?'ui-even':'');
			echo '">';
			
			foreach($this->columns as $col_id => $c)
			{	
				// Get cell data
				if (($c['customdata']) && method_exists($this, 'on_custom_data'))
					$data = $this->on_custom_data($col_id, $recid, $rec);
				else
				{
					if (is_object($rec))
						$cell_data = $rec->$c['datakey'];
					else if (is_array($rec))
						$cell_data = $rec[$c['datakey']];
					else
						$cell_data = (string)$rec;
					if (($c['mangle']) && (method_exists($this, 'on_mangle_data')))
						$data = $this->on_mangle_data($col_id, $recid, $cell_data);
					else
						$data = (empty($cell_data))?'&nbsp;':esc_html($cell_data);
				}

				// Display cell
				echo '<td ';
				if ($c['clickable'])
				{
					echo ' class="ui-clickable" ';
					echo ' onclick="' .
						'$(\'form#' . $this->grid_id . 	' input[name=action]\').val(\'click\'); ' .
						'$(\'form#' . $this->grid_id . ' input[name=colid]\').val(\'' . $col_id . '\');' .
						' $(\'form#' . $this->grid_id .	' input[name=rowid]\').val(\'' . $recid . '\');' .
						' $(\'form#' . $this->grid_id . '\').submit();" ';
				}
				echo '>' . $data;
			}
		}
		
		// Render column captions again
        if (($this->options['headerpos'] == 'bottom') || ($this->options['headerpos'] == 'both'))
			$this->render_column_captions();

		echo '</table>';

        // Page controls
        if (($this->options['pagecontrolpos'] == 'bottom') || ($this->options['pagecontrolpos'] == 'both'))
	        $this->render_page_controls();

		echo '</div>';
	}
}

?>