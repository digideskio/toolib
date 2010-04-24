<?php

//! Date textual formater class
class Output_DateFormat
{
    //! The date object that will be formated
    private $date_obj;

    //! Create format object
    public function __construct($date_obj)
    {   $this->date_obj = $date_obj;    }

    //! Human representation of time span.
    /** 
	 * Humans usually prefer the time in lectical representation,
	 * e.g. '10 mins ago', 'after an hour'. This function will
	 * return a human diff of the formated time and a relative one.
	 * @param $rel_time The time to calculate the difference to.
	 * @param $html Return html formated time.
	 * @return A string with lectical time representation that
	 *  depending on $html may be encapsulated in a html \<span\> tag.
     */
    function human_diff($rel_time = time(), $html = true)
    {	$full_date = $this->date_obj->format('D, j M, Y \a\t H:i:s');
	    $sec_diff = abs($this->date_obj->format('U') - $rel_time);
	
	    $ret = '<span title="' . $full_date . '">';

	    if ($sec_diff <= 60)	// Same minute
		    $ret .= 'some moments ago';
	    else if ($sec_diff <= 3600)	// Same hour
		    $ret .= floor($sec_diff / 60) . ' minutes ago';
	    else if ($sec_diff <= 86400)	// Same day
		    $ret .= floor($sec_diff / 3600) . ' hours ago';
	    else /*if ($sec_diff <= (86400 * 14))	// Same last 2 weeks
		    $ret .= $dt->format('M j') . '(' . floor($sec_diff / 86400) . ' days ago)';*/
	    {	$cur_date = getdate();
		    $that_date = getdate($dt->format('U'));
		
		    if ($cur_date['year'] == $that_date['year'])
			    $ret .=$dt->format('M d, H:i');
		    else
			    $ret .= $dt->format('d/m/Y');
	    }
	
	    $ret .= '</span>';
	    return $ret;
    }

    //! Return as less as possible details about time
    /**
     * This smart format will omit details that are the same with
     * presence. E.g. if you are showing a date in the same year,
     * the year will be ommited, the same will happen for month and day.
     */
    function smart_details($ndate)
    {	$currentTime = time();
	    $currentTimeDay = date('d m Y', $currentTime);
	    $ndateDay = date('d m Y', $this->date_obj);
	    if ($currentTimeDay == $ndateDay)
		    return 'Today '.date('h:i a', $this->date_obj);
	    if (date('Y', $currentTime) == date('Y', $this->date_obj))
		    return substr(date('F', $this->date_obj), 0, 3) . date(' d,  h:i a', $this->date_obj);
		
	    return substr(date('F', $this->date_obj), 0, 3) . date(' d, Y', $this->date_obj);
    }
}
?>
