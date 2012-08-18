<?PHP
namespace CalEvans\Google;
/*
 * @todo Flesh this out. Right now it's the bare min. does it need more? 
 * @todo Put it in my API wrappers bundle
 */
class Geocode
{
	protected $baseURL = 'http://maps.googleapis.com/maps/api/geocode/json?address=%s&sensor=false';

	public function __construct()
	{
		
	}

	public function fetchGeocode($location)
	{	
		$loopCounter=0;
		do {
			$urlencodedLocation = urlencode($location);
			$finalURL   = sprintf($this->baseURL,$urlencodedLocation);
			$rawPayload = file_get_contents($finalURL);
			$payload    = json_decode($rawPayload);
			if ($payload->status=='OVER_QUERY_LIMIT') {
				$continueLooping = true;
				sleep(2);
			} else {
				$continueLooping = false;
			}
			$loopCounter++;
		} while ($continueLooping AND $loopCounter<3);
		return $payload;
	}
}
