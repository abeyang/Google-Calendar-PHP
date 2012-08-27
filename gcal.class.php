<?php
/* 
 * Google Calendar PHP Framework
 * version 2.0.0 <alpha>
 * 
 * gCal PHP Framework is freely distributable under the terms of an MIT-style license.                                                                              
 * Please visit [github] for more details.  
 *
 * =Options=
 * 'cals' = calendar var; possible values:
 * 'shows' = with respective calendar, reveal:
 *		all - include private events
 *		normal - show events with '!' prepended to titles (same as '', or null)
 *		featured - show events with '!!' prepended to titles
 * 'tags' = tag or union of tags associated with each respective calendar
 * 		(if no tag is specified, then it will pull everything):
 *		for KAIROS: kairos, kairos1, kairos2, kairos3, kairos4, kairosw
 *
 * =Other Options=
 * 'debug' = verbose options (useful for debugging)
 * 'startdate' = starting date in YYYY-MM-DD format (default to 'today')
 * 'numdays' = time frame in days (default = 30): inclusive
 *
 * =TODO=
 * multi-tags per event(?)
 *
/* ---------------------------------------------------------------------------------- */

define('GCAL_PATH', dirname(__FILE__) . '/');
// include files
require_once(GCAL_PATH . 'xml.php');
require_once(GCAL_PATH . 'CONFIG.php');

class gCal {
	// ----------------------------------------------------------------------------------
	// variables
	// ----------------------------------------------------------------------------------
	var $version = 1.1;
	
	var $calendars;
	var $shows;
	var $tags;
	var $events;			// ultimately, need to fill this array
	var $exceptions;		// temporary storage array for exception events
		
	// time vars
	var $cal_startdate;		// $startdate
	var $cal_enddate;		// $enddate
	var $cal_starttime;		// $timestart
	var $cal_endtime;		// $timeend
	var $cal_windowsec;		// $windowsec

	var $gcal_suffix = 'T00:00:00-08:00';	// $gcalsuffix (-0800 = PST)
	var $gcal_startmin;						// $startmin
	var $gcal_startmax;						// $startmax
	
	// misc
	var $id = 0;				// for unique div id's
	var $debug;
	var $url = 'http://www.google.com/calendar/feeds/';
	
	// ----------------------------------------------------------------------------------
	// initialize
	// ----------------------------------------------------------------------------------
	function gCal($cals, $options = array()) {
		// error check
		if (!$cals) {
			echo 'Calendars not supplied';
			return;
		}
		
		$this->debug = $options['debug'];

		// set calendars, shows, and tags arrays
		$this->calendars = explode(',', $cals);
		$this->shows = explode(',', $options['shows']);
		$this->tags = explode(',', $options['tags']);
	
		if ($this->debug) echo 'tags: '.$options['tags'].' <br />';

		// time stuff
		$this->cal_startdate = $options['startdate'] ? $options['startdate'] : date('Y-m-d', time());
		$this->cal_starttime = strtotime($this->cal_startdate);
		$this->gcal_startmin = $this->cal_startdate.$this->gcal_suffix;
		
		$this->gcal_windowsec = 
			$options['numdays'] ? ($options['numdays'] + 1) * 86400 : 2678400; 	// 86400 sec = 1 day; 2678400 sec = 30 days (inclusive)
		$this->cal_endtime = $this->cal_starttime + $this->gcal_windowsec;
		$this->cal_enddate = date('Y-m-d', $this->cal_endtime);
		$this->gcal_startmax = $this->cal_enddate.$this->gcal_suffix;
		if ($this->debug) echo $this->cal_startdate.' -- '.$this->cal_enddate;
		
		// kick off main()
		$this->main();
		
	} // end gCal

	// ----------------------------------------------------------------------------------
	// engine
	// ----------------------------------------------------------------------------------
	
	// eventarray is an array of events (that are associative arrays)
	function getCalendar($calname, $show = '', $tagstring = '') {

		$userid = $GLOBALS['USERID'];
		$magiccookie = $GLOBALS['MAGICCOOKIE'];

		$urlstring = $this->url.$userid.'/private-'.$magiccookie.'/full?start-min='.$this->gcal_startmin.'&start-max='.$this->gcal_startmax;

		if ($this->debug) echo '<br />' . $urlstring;
		$xml = file_get_contents($urlstring);
		if (!$xml) {
			echo 'Not a valid URL';
			return false;
		}
		$data = XML_unserialize($xml);

		if ($this->debug) {
			echo "<p /><strong>$calname</strong><br />$urlstring<br />show: $show <br />";
		}

		// zero entries
		if (!$data['feed']['entry']) return;
		// one entry
		else if (!is_array($data['feed']['entry'][0])) $this->getEvent($data['feed']['entry'], $calname, $show, $tagstring);
		// two or more entries
		else {
			foreach($data['feed']['entry'] as $xmlevent) {
				$this->getEvent($xmlevent, $calname, $show, $tagstring);
			}
		}
	}  // end getCalendar

	function getEvent($xmlevent, $calname, $show, $tagstring) {
		//global $timestart, $timeend, $eventarray, $exceptionarray, $textile;
		$showevent = true;

		/* parse title field */
		// '! ...' 	denotes normal event
		// '!! ...' 	denotes featured event
		$title = $xmlevent['title'];
		if (substr($title, 0, 1) == '!') {
			$isnormal = true;
			$title = substr($title, 1);
			if (substr($title, 0, 1) == '!') {
				$isfeatured = true;
				$title = substr($title, 1);
			}
		}
		// check public/important events
		if (($show == '' || $show == 'normal') && !$isnormal) {
			$showevent = false;
			if (!$this->debug) return;
		}
		else if ($show == 'featured' && !$isfeatured) {
			$showevent = false;
			if (!$this->debug) return;
		}

		// parse for tags:
		// possible examples:
		// "Bible Study"
		// "kairos2: Bible Study"
		list($eventtag, $title) = explode(':', $title,2);

		if (!$title) {
			// in the case of no tags
			$title = $eventtag;
			$eventtag = null;
		}
		if ($tagstring) {
			// check if this tag ($eventtag) matches any tag listed in $tagstring
			if (!$eventtag) {
				$showevent = false;
				if (!$this->debug) return;
			}
			else $eventtag = str_replace(' ', '', $eventtag);	// remove spaces

			$tags = explode('-', $tagstring);
			$foundmatch = 0;
			foreach($tags as $tag) {
				if (!strcasecmp($eventtag, $tag)) {
					$foundmatch = true;
					break;
				}
			}
			if (!$foundmatch) {
				$showevent = false;
				if (!$this->debug) return;
			}
		}
		
		/* get ID */
		$explode_id = explode('/', $xmlevent['id']);
		$id = $explode_id[sizeof($explode_id) - 1];		// last element is the most interesting (actual id)
		
		/* get status */
		$status = strstr($xmlevent['gd:eventStatus attr']['value'], '#');
		// "#event.confirmed" => "confirmed"
		$status = substr($status, 7);
		if ($status == 'canceled') {
			$showevent = false;
			if (!$this->debug) return;
		}

		/* parse location/address */
		// possible text examples:
		// "getactive"
		// "getactive @ 2855 telegraph ave, berkeley 94705"
		list($location,$address) = explode('@', $xmlevent['gd:where attr']['valueString']);

		/* get content */
		$content = $xmlevent['content'];
		$content = str_replace('&quot;', '"', $content);    // aish. for textile use.
		
		/* parse start/end times */
		
		// "stub" event (phantom events with no times)
		if (!($xmlevent['gd:when'] || $xmlevent['gd:when attr'])) {
			$showevent = false;
			if (!$this->debug) return;
		}

		// recurring event: "parent"
		// TODO: SOME RECURRING EVENTS FALL ON THE DAY BEFORE CAL_STARTTIME (GCAL BUG)
		else if (count($xmlevent['gd:when']) > 2) {
			// temporary array for recurring event times
			$recurrences = array();
			
			$xmltimes = $xmlevent['gd:when'];
			for($i=0; $i<count($xmltimes)/2; $i++) {
				// fill recurring events array
				$recurrences[] = $this->parseTimes($xmltimes[$i.' attr']['startTime'], $xmltimes[$i.' attr']['endTime'], 'assoc');
			}
			
			// since the parent (recurring) event occurs at the end of xml, exception array should be filled already.
			// let's check the said exception array!
			usort($recurrences, sortByTime);
			
			// $recurrences[0] is the earliest time
			$starttime = $recurrences[0]['starttime'];
			$endtime = $recurrences[0]['endtime'];
			$allday = $recurrences[0]['allday'];
			
			$event = $this->exceptions[$id.'_'.$starttime];
		}

		// single (non-recurring) event
		else {
			list ($starttime, $endtime, $allday) = 
				$this->parseTimes($xmlevent['gd:when attr']['startTime'], $xmlevent['gd:when attr']['endTime']);
		}

		// if event doesn't exist (a recurring event might already exist w/n scope)
		if (!$event) {
			// create particular event 
			$event = array(
				'id'		=> $id,
				'title' 	=> $title, 
				'location'	=> $location, 
				'address' 	=> $address, 
				'content' 	=> $content, 
				'starttime'	=> $starttime, 
				'endtime' 	=> $endtime, 
				'allday' 	=> $allday, 
				'isnormal' 	=> $isnormal, 
				'isfeatured' 	=> $isfeatured, 
				'tag' 		=> $eventtag, 
				'cal' 		=> $calname,
				'status'	=> $status, 
				'showevent' 	=> $showevent
			);
		
			// check if this is an exception to a recurring event: if so, fill in a separate array
			if ($xmlevent['gd:originalEvent']) {
				$parentid = $xmlevent['gd:originalEvent attr']['id'];
				$event['isexception'] = true;
				$event['parentid'] = $parentid;
				$this->exceptions[$parentid.'_'.$starttime] = $event;
				return;
			}
		}
		
		/* add event to events array */
		$this->events[] = $event;

	} // end getEvent
	
	// ----------------------------------------------------------------------------------
	// helper functions
	// ----------------------------------------------------------------------------------

	// given start and end times (in gCal format), return into unix timestamp
	// return array: [starttime, endtime, allday]
	function parseTimes($start, $end, $typeofarray = 'regular') {

		// events that are not entire days
		// (format: 2006-07-04T18:00:00.000-07:00)
		if (strlen($start) > 10) {
			$allday = false;
			$starttime = $this->gCalTime($start);
			$endtime = $this->gCalTime($end);
		}
		// events that are all day (or more than one day)
		// (format: 2006-07-02)
		else {
			$allday = true;
			$starttime = strtotime($start);
			$endtime = strtotime($end) - 86400; 		// gCal adds an extra day
		}
		
		if ($typeofarray == 'assoc') $arr = array('starttime' => $starttime, 'endtime' => $endtime, 'allday' => $allday);
		else $arr = array($starttime, $endtime, $allday);
		return $arr;
	}
	
	// convert gCalTime to standard unix time
	// gCalTime format: 2006-07-04T18:00:00.000-07:00
	function gCalTime($time) {
		list($day, $timecode) = explode('T', $time);
		$hours = substr($timecode, 0, 2) * 3600;
		$minutes = substr($timecode, 3, 2) * 60;
		return (strtotime($day) + $hours + $minutes);
	}
	
	function main() {
		// fill array
		$this->getCalendar($NAME, $showcal, $this->tags[$i]);
		
/*
		for ($i=0; $i<count($this->calendars); $i++) {
			$showcal = is_null($this->shows[$i]) ? '' : $this->shows[$i];
			// reset array of exceptions; only local to its resepctive calendar
			if ($this->exceptions) unset($this->exceptions);	
			// get calendar info
			$this->getCalendar($this->calendars[$i], $showcal, $this->tags[$i]);
		}
*/

		// sort all events (across all calendars)
		if ($this->events) usort($this->events, sortByTime);		
		
		if ($this->debug) {
			echo '<p />';
			print_r($this->events);
		}
		
	} // end main
	
} // end class

// customized sort (sort by timestamp)
function sortByTime($a, $b) {
	if ($a['starttime'] == $b['starttime']) return 0;
	return ($a['starttime'] > $b['starttime']) ? 1 : -1;
}

?>
