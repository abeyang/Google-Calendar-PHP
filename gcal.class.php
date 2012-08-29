<?php
/* 
 * Google Calendar PHP Framework
 * version 2.1
 * 
 * gCal PHP Framework is freely distributable under the terms of an MIT-style license.                                                                              
 * Please visit https://github.com/abeyang/Google-Calendar-PHP for more details.  
 *
 * =Options=
 * 'shows' = with respective calendar, reveal:
 *		all - include private events
 *		normal - show events with '!' prepended to titles (same as '', or null)
 *		featured - show events with '!!' prepended to titles
 * 'tags' = tag or union of tags associated with each respective calendar
 * 		(if no tag is specified, then it will pull everything):
 *		for KAIROS: kairos, kairos1, kairos2, kairos3, kairos4, kairosw
 *
 * =Other Options=
 * 'debug' = if TRUE, verbose options (useful for debugging)
 * 'debugtime' = if TRUE, displays total time for gcal.class to finish
 * 'startdate' = starting date in YYYY-MM-DD format (default to 'today')
 * 'numdays' = time frame in days (default = 30): inclusive
 *
 *
/* ---------------------------------------------------------------------------------- */

class gCal {
	// ----------------------------------------------------------------------------------
	// variables
	// ----------------------------------------------------------------------------------
	var $version = '2.1';
	
	var $name;				// name of calendar; for debug purposes only ($NAME in CONFIG.php)
	var $userid;			// need this to pull specific calendar from Google ($USERID in CONFIG.php)
	var $magiccookie;		// need this to pull specific calendar from Google ($MAGICCOOKIE in CONFIG.php)

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

	var $gcal_suffix;		// which standard time to use ($STANDARDTIME in CONFIG.php)
	var $gcal_startmin;		// $startmin
	var $gcal_startmax;		// $startmax
	
	// misc
	var $id = 0;			// for unique div id's / AY: not sure if this is still necessary...
	var $debug;
	var $debugspeed;
	var $debugspeed_start;
	var $url = 'http://www.google.com/calendar/feeds/';
	
	// ----------------------------------------------------------------------------------
	// initialize
	// ----------------------------------------------------------------------------------
	function gCal($options = array()) {
		define('GCAL_PATH', dirname(__FILE__) . '/');
		// include files
		require(GCAL_PATH . 'xml.php');
		require(GCAL_PATH . 'CONFIG.php');

		$this->name = $config['NAME'];
		$this->userid = $config['USERID'];
		$this->magiccookie = $config['MAGICCOOKIE'];
		if ($config['STANDARDTIME'])
			$this->gcal_suffix = 'T00:00:00' . $config['STANDARDTIME'];
		else $this->gcal_suffix = 'T00:00:00-08:00';		// default is PST (-08:00)
	
		// error check
		if (!$this->userid || !$this->magiccookie) {
			echo 'Calendars not supplied. Please supply a userid and/or magiccookie in the CONFIG.php file';
			return;
		}
		
		$this->debug = $options['debug'];
		$this->debugspeed = $options['debugspeed'];
		if ($this->debugspeed) $this->debugspeed_start = microtime(true);

		// set calendars, shows, and tags arrays
		$this->shows = $options['shows'];
		$this->tags = explode(',', $options['tags']);
	
		if ($this->debug) echo 'tags: '.$options['tags'].' <br />';

		// time stuff
		$this->cal_startdate = $options['startdate'] ? $options['startdate'] : date('Y-m-d', time() + $config['OFFSETTIME']);
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
	function getCalendar($tagstring = '') {

		$urlstring = $this->url.$this->userid.'/private-'.$this->magiccookie.'/full?start-min='.$this->gcal_startmin.'&start-max='.$this->gcal_startmax;

		if ($this->debug) echo '<br />' . $urlstring;
		$xml = file_get_contents($urlstring);
		if (!$xml) {
			echo 'Not a valid URL';
			return false;
		}
		$data = XML_unserialize($xml);
		
		// Use the below function to show _everything_ inside $data. For debugging purposes only.
		// if ($this->debug) print_r($data);
		
		$show = $this->shows;

		if ($this->debug) {
			echo "<p /><strong>$this->name</strong><br />$urlstring<br />show: $show <br />";
		}

		// zero entries
		if (!$data['feed']['entry']) return;
		// one entry
		else if (!is_array($data['feed']['entry'][0])) $this->getEvent($data['feed']['entry'], $show, $tagstring);
		// two or more entries
		else {
			foreach($data['feed']['entry'] as $xmlevent) {
				$this->getEvent($xmlevent, $show, $tagstring);
			}
		}
		
		if ($this->debug) {
			echo '<p>Exceptions array: </p>';
			print_r($this->exceptions);
		}
	}  // end getCalendar

	function getEvent($xmlevent, $show, $tagstring) {
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
		// if $showevent is false, but $debug is turned on, we'll keep it in the array
		// otherwise, we won't store it at all
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
		list($pre_open_bracket,$post_open_bracket) = explode('[', $content);
		list($inside_brackets,$post_close_bracket) = explode(']', $post_open_bracket);
		$content = $pre_open_bracket.$post_close_bracket;
		$content = str_replace('&quot;', '"', $content);    // aish. for textile use.
		$inside_brackets = str_replace('&quot;', '"', $inside_brackets);
		
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

			if ($this->debug) {
				echo '<p>$recurrences: </p>';
				print_r($recurrences);
			}
			
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
				'status'	=> $status, 
				'showevent' 	=> $showevent,
				'post_link'	=> $inside_brackets
			);
		
			// check if this is an exception to a recurring event: if so, fill in a separate array
			if ($xmlevent['gd:originalEvent']) {
				$parentid = $xmlevent['gd:originalEvent attr']['id'];
				$event['isexception'] = true;
				$event['parentid'] = $parentid;
				$this->exceptions[$parentid.'_'.$starttime] = $event;
				return;
			}
			
			// hateful: some recurring events could have gone by undetected up to this day
			// (if the numdays doesn't happen to overlap at least its second iteration)
			// must account for it here.
			// again, assumes exceptions array is filled out and has the correct event
			else if ($this->exceptions[$id.'_'.$starttime]) {
				$event = $this->exceptions[$id.'_'.$starttime];
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
		$this->getCalendar($this->tags[$i]);

		// sort all events (across all calendars)
		if ($this->events) usort($this->events, sortByTime);		
		
		if ($this->debug) {
			echo '<p />';
			print_r($this->events);
		}
		
		if ($this->debugspeed) {
			$finaltime = microtime(true) - $this->debugspeed_start;
			echo '<p>TOTAL TIME: ' . $finaltime . '</p>';
		}
		
	} // end main
	
} // end class

// customized sort (sort by timestamp)
function sortByTime($a, $b) {
	if ($a['starttime'] == $b['starttime']) return 0;
	return ($a['starttime'] > $b['starttime']) ? 1 : -1;
}

?>
