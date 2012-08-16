<?PHP
/* 
 * store the date posted
 * store the lat & long
 * Filter for US only (optional, allow other contries)
 * Build a seperate front-end using silex that brings up a US map
 * Show intensity of each job market over 30 days by enlarging the circle and deepening the color for each new job posted. Reduce as jobs go 30 days old.
 * Can we work twilio into this somehow?
 * can we work simplyhired.com's salary Db into this somehow?
 */

$rssFeedURL="http://careers.stackoverflow.com/jobs/feed?a=12";
$feed = simplexml_load_file($rssFeedURL);
$geoCodeURL = 'http://maps.googleapis.com/maps/api/geocode/json?address=%s&sensor=false'

foreach($feed->channel->item as $item) {
	$telecommute = false;
	$title = $item->title;
	if (strpos($title, '(telecommute)')!==false) {
		$telecommute = true;
		$title = substr($title,0,strpos($title, '(telecommute)'));
	} 
	$locationStart = strripos($title,'(')+1;
	$locationEnd   = strripos($title,')');
	$location = substr($title, $locationStart, ($locationEnd-$locationStart));
	$locationArray = explode(';',$location);
	if ($telecommute) {
		$locationArray[] = 'Telecommute';
	}
	$title = substr($title,0,$locationStart-1);
	echo "Title:".$title . "\n";
	echo "Location:" . implode(',',$locationArray) . "\n\n";
}
