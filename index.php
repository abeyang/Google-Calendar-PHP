<link rel="stylesheet" type="text/css" href="themes/magazine.css" />
<script src="http://www.google.com/jsapi"></script>
<script type="text/javascript" charset="utf-8">	
/* 	google.load('jquery', '1.3.2'); */
</script>


<?php
// created by: abe yang 9/17/06
// testing page for gcal class

require('themes/magazine.class.php');

$dir = getcwd();
$shows = 'everything';
$shows = 'normal';
//$tags = 'kairos-kairos2-kairos4';

$startdate = '';
/* $startdate = '2012-08-30'; */
/* $startdate = '2012-09-17'; */
$numdays = 60;

$gcal = new gCalWeb(array(
	'debug' => 0,
	'debugspeed' => 1,
	'shows' => $shows,
	'tags' => $tags,
	'startdate' => $startdate,
	'numdays' => $numdays
));


echo $gcal->widgetDisplay();
?>

