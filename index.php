<link rel="stylesheet" type="text/css" href="themes/magazine.css" />
<script src="http://www.google.com/jsapi"></script>
<script type="text/javascript" charset="utf-8">	
/* 	google.load('jquery', '1.3.2'); */
</script>


<?php
// created by: abe yang 9/17/06
// testing page for gcal class

require('gcal.class.php');
require('themes/magazine.class.php');

// echo 'Time: ' . date('G:i', time());		// tells you local time (where the server is located)

/* $dir = getcwd(); */
$shows = 'everything';
$shows = 'normal';
//$tags = 'kairos-kairos2-kairos4';

$startdate = '';
/* $startdate = '2012-08-28'; */
/* $startdate = '2012-09-17'; */
$numdays = 5;

$mag = new Magazine(array(
	'debug' => 1,
	'debugspeed' => 1,
	'shows' => $shows,
	'tags' => $tags,
	'startdate' => $startdate,
	'numdays' => $numdays
));
?>

<div class="slickpanel">

<?php echo $mag->display(); ?>

</div>
