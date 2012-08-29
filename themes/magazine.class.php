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

class Magazine extends gCal {

	function Magazine($options = array()) {
		parent :: gCal($options);
		
	} // end gCalWeb()
	
	function display() {
		$events = $this->events;
		if (!$events) {
			return '';
		}
		$olddate = '';
		$displaystring .= '<div class="gw-post">';

		foreach($events as $event) {
			$start = $event['starttime'];
			// check for new dates
			$newdate = date('l, n/j/y', $start);
			if (strcmp($newdate, $olddate)) {
				// $newdate != $olddate
				$displaystring .= '<div class="gw-date-wrap"><div class="gw-date">' . $newdate . '</div></div>';
				$olddate = $newdate;
			}
			if ($event['isfeatured']) {
			    $displaystring .= '<div class="gw-featured">';
			}
			if ($event['post_link']) {
			    $displaystring .= '<a href="'.$event['post_link'].'">';
			}
			$displaystring .= '<div class="gw-event ' . $event['tag'] . '">';
			$displaystring .= '<span class="gw-bullet">&nbsp;</span><div class="gw-time">' . date('g:i a', $start) . '</div>';
			$displaystring .= '<div class="gw-title">' . $event['title'];
			if ($event['location']) {
				$displaystring .= ' @ ' . $event['location'];
			}
			$displaystring .=  '</div>'; //close .gw-title
			$displaystring .= '</div>'; // close .gw-event 
			if ($event['post_link']) {
			    $displaystring .= '</a>';
			}
			if ($event['isfeatured']) {
			    $displaystring .= '</div>';
			}
			

		} // end foreach
		
		$displaystring .= '</div>'; // close .gw-post
		return $displaystring;
	} // end display()

} // end Magazine
?>

