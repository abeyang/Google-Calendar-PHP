<?php
/* 
 * gCal Extension - Magazine Theme
 * Abe Yang (c) 2012
/* ---------------------------------------------------------------------------------- */

// precondition: events (array) must be fed in to desired function
// postcondition: string to be displayed will be concatenated with all events

/* 
 * =Additional Params=
 * 'displaytag' = displays tag in subject line (default = 0)
 *
/* ---------------------------------------------------------------------------------- */

require_once('gcal.class.php');

class gCalWeb extends gCal {
	// rss feeds
	var $rsstitle;
	var $rssdesc;
	var $rsslink;

	function gCalWeb($cals, $options = array()) {
		parent :: gCal($cals, $options);
		
		$this->rsstitle = $options['title'];
		$this->rssdesc = $options['desc'];
		$this->rsslink = $options['link'];
		
	} // end gCalWeb()
	
	function widgetDisplay() {
		$events = $this->events;
		if (!$events) return '';
		$olddate = '';
		$displaystring .= '<div class="gw-post slickpanel">';

		foreach($events as $event) {

			$this_id = $event['cal'].'_'.$event['id'];
			$start = $event['starttime'];
			// check for new dates
			$newdate = date('l, n/j/y', $start);
			if (strcmp($newdate, $olddate)) {
				// $newdate != $olddate
				$displaystring .= '<div class="gw-date-wrap"><div class="gw-date">' . $newdate . '</div></div>';
				$olddate = $newdate;
			}
			$displaystring .= '<div class="gw-event"><span class="gw-bullet">&nbsp;</span><div class="gw-time">' . date('g:i a', $start) . '</div><div class="gw-title">' . $event['title'];
			if ($event['location']) {
				$displaystring .= ' @ ' . $event['location'];
			}
			$displaystring .=  '</div>'; 
			$displaystring .= '</div>'; // close (.gw-date or .gw-event) 
			
		} // end foreach
		
		$displaystring .= '</div>'; // close .gw-post
		return $displaystring;
	} // end widgetDisplay()

} // end gCalWeb
?>

