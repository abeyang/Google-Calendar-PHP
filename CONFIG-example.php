<?php

/*
 *		Config File
 *		All modifications go here
 *		
/* ---------------------------------------------------------------------------------- */

$config = array();


$config['NAME'] = 'Calendar Name (optional; internal use only)';
$config['USERID'] = '<some string>%40group.calendar.google.com';
$config['MAGICCOOKIE'] = '<cookie string>';

// This tells Google Calendar the time zone that you're in

$config['STANDARDTIME'] = '-08:00';		// PST = -08:00

// $OFFSETTIME is in seconds. Make sure to use '-' to shift time back.
// This is useful if the server is located in a different timezone
// You can tell if you use this function on a php page:
// 		date('G:i', time())
// It tells you the hour and minute (military time) that the server is on
// Ex: My current time is 9:06am, but the function above displays 16:06... 7 hour time difference, in seconds, is 25200

/* $config['OFFSETTIME'] = -25200; */

?>
