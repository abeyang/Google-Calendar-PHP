<link rel="stylesheet" type="text/css" href="magazine.css" />
<script src="http://www.google.com/jsapi"></script>
<script type="text/javascript" charset="utf-8">	
	google.load('jquery', '1.3.2');
</script>
<script type="text/javascript" charset="utf-8">
	$(document).ready(function() {
		$('.gcal-widget-title').click(function() {
			$('.gcal-widget-block', $(this).parent()).slideToggle('slow');
			return false;
		});
	});
</script>	

<?php
// created by: abe yang 9/17/06
// testing page for gcal class

require('magazine.class.php');

$dir = getcwd();
//$cal = 'churchwide,college,ya,youth,joyland,csueb,ism';
$cal = 'riverside';
//$cal = 'churchwide';
$shows = 'everything';
// $shows = '';
//$tags = 'kairos-kairos2-kairos4';

$startdate = '';
//$startdate = '2006-09-13';
$numdays = 60;

$gcal = new gCalWeb($cal, array(
	'debug' => 1,
	'shows' => $shows,
	'tags' => $tags,
	'startdate' => $startdate,
	'numdays' => $numdays
));


echo $gcal->widgetDisplay();
?>

