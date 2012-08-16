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

$rssFeedURL ="http://careers.stackoverflow.com/jobs/feed?a=12";
$feed       = simplexml_load_file($rssFeedURL);
$geoCodeURL = 'http://maps.googleapis.com/maps/api/geocode/json?address=%s&sensor=false';
$db         = new SQlite3('../data/scanJobs.sqlite');

foreach($feed->channel->item as $item) {
	$jobId = null;
    $checksum = md5($item->title . $item->pubDate);
    $sql = sprintf("select id from job where checksum='%s';",$checksum);
    $results = $db->query($sql);
    if ($row = $results->fetchArray()) {
        // we already know this job...move along
        continue;
    }
	$telecommute = false;
	$title = $item->title;
	if (strpos($title, '(telecommute)')!==false) {
		$telecommute = true;
		$title = substr($title,0,strpos($title, '(telecommute)'));
	} 
	$locationStart = strripos($title,'(')+1;
	$locationEnd   = strripos($title,')');
	$location = trim(substr($title, $locationStart, ($locationEnd-$locationStart)));
	$locationArray = explode(';',$location);
	
	$title = substr($title,0,$locationStart-1);
	$datePublished = new DateTime((string)$item->pubDate);
	$title = SQLite3::escapeString($title);
	$sql = sprintf("insert into job (title, telecommute, date_posted, checksum) VALUES ('%s','%s','%s','%s');",$title,$telecommute,$datePublished->getTimestamp(),$checksum);
	$db->exec($sql);
	$jobId = $db->lastInsertRowId();
	foreach($locationArray as $thisLocation) {
		$results = $db->query("select * from city where name = '" . $thisLocation ."';");
		if ($row = $results->fetchArray()) {
			// we have a hit on the cache
			$cityId = $row['id'];
echo "--Found ".$thisLocation." in the cache.\n"; 
		} else {
echo "--Did not find ".$thisLocation." in the cache. checking it now.\n";
sleep(2);
			// cache miss
			$jsonPayload = file_get_contents(sprintf($geoCodeURL,urlencode($thisLocation)));
			$payload = json_decode($jsonPayload);
			$sql = sprintf("insert into city(name, latitude, longitude) VALUES ('%s','%s','%s');",
						   $thisLocation,
						   $payload->results[0]->geometry->location->lat,
						   $payload->results[0]->geometry->location->lng);
			$results = $db->exec($sql);
			if ($results) {
				$cityId = $db->lastInsertRowId();
			} else {
				echo "PROBLEM INSERTING INTO CITY TABLE!\n".$sql."\n";
			}	
			$sql = sprintf("insert into job_city(id_job,id_city) VALUES (%d,%d);",$jobId,$cityId);
			$db->exec($sql);
		}
	}
	echo $title."\n";
}
die(0);
