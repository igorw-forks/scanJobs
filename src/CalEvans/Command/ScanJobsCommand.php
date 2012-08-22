<?PHP
namespace CalEvans\Command;
/*
 * @todo break job specific functiosn out into a job mobel
 */
use CalEvans\Google;

use Knp\Command\Command;

class ScanJobsCommand extends Command
{	
	protected $geocoder;

    protected function configure()
    {
        $this->setName('scan')
             ->setDescription('Run the job scan');
    }

	public function addGeocodeer($geocoder)
	{
		$this->geocoder = $geocoder;
	}


    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
		$app = $this->getSilexApplication();
		$db = $app['db'];
		
		$rssFeedURL = "http://careers.stackoverflow.com/jobs/feed?a=12";
		$feed       = simplexml_load_file($rssFeedURL);
		
		foreach($feed->channel->item as $singleItem) {
			if ($jobId = $this->fetchJob($singleItem->guid)) {
				// we already know this job
				continue;
			}
			
			$pubDate = new \DateTime($singleItem->pubDate);

			// build the payload to write to the db
			$payload = array();
			$payload['guid']         = $singleItem->guid; 
			$payload['title']        = $this->parseTitle($singleItem->title);
			$payload['location']     = $this->parseLocation($singleItem->title);
			$payload['location']     = $this->geocodeLocations($payload['location']);
			$payload['telecommute']  = $this->parseTelecommute($singleItem->title);
			$payload['pubDate']      = $pubDate->format('Y-m-d h:i e');
			$this->saveJob($payload);
			$output->writeln($singleItem->title);
		}
		return;
    }


	protected function fetchJob($guid) {
		$db = $this->getSilexApplication()['db'];
		$jobId = $db->executeQuery("select id from job where guid=?",array($guid))
					->fetchColumn();
		return $jobId;
	}
	
	
	protected function parseTitle($title) 
	{
		$returnValue = array();
		$returnValue['original'] = $title;

		//strip off telecommute
		$title = trim(str_replace('(telecommute)','',$title));

		// find the location and strip it off. what remains is the full title
		$locationStart          = strripos($title,'(')+1;
		$returnValue['full']    = substr($title,0,$locationStart-1);

		// find AT
		$beginCompany = strrpos($returnValue['full'],' at ')+4;

		// Everything after AT is the company name
		$returnValue['company'] = substr($returnValue['full'],$beginCompany);

		// Everything before AT is the position title
		$returnValue['title']    = substr($returnValue['full'],0,$beginCompany-4);
		
		return $returnValue;
	}
	
	
	protected function parseLocation($title)
	{
		$returnValue = array();
		$title = trim(str_replace('(telecommute)','',$title));
		$locationStart = strripos($title,'(')+1;
		$locationEnd   = strripos($title,')');
		$location = trim(substr($title, $locationStart, ($locationEnd-$locationStart)));
		$holdingArray = explode(';',$location);
		foreach($holdingArray as $location) {
			$location = trim($location);
			$returnValue[$location] = array('originalLocation'=>$location,
											'latitude'=>null,
											'longitude'=>null,
											'cityId'=>null);

		}
		return $returnValue;
	}
	
	
	protected function parseTelecommute($title)
	{
		$returnValue = !(strpos($title,'(telecommute)')===false);
		return $returnValue;
	}
	

	protected function geocodeLocations($locationArray)
	{
		$db = $this->getSilexApplication()['db'];

		foreach($locationArray as $key=>$value) {

			$cityId = $db->executeQuery("select id from city where name=?",array($key))
						 ->fetchColumn();

			if ((int)$cityId>0) {				 
				// we know this city already
				$locationArray[$key]['cityId']=$cityId;
			} else {
				// we've got to go find this city
				$payload = $this->geocoder->fetchGeocode($key);
				
				if ($payload->status==="OK") {
					$locationArray[$key]['latitude']  = $payload->results[0]->geometry->location->lat;
					$locationArray[$key]['longitude'] = $payload->results[0]->geometry->location->lng;
					$locationArray[$key]['country']   = $this->geocoder->fetchCountry();
				}
			}
			
		}
		return $locationArray;
	}


	protected function saveJob($payload) {
		$db = $this->getSilexApplication()['db'];
		$sql = "insert into job (title, telecommute, date_posted, checksum) VALUES (?,?,?,?);";
		$db->beginTransaction();
		try {
			$db->insert('job',
						['title'       => $payload['title']['original'],
						 'telecommute' => $payload['telecommute'],
						 'date_posted' => $payload['pubDate'],
						 'guid'        => $payload['guid'] ]);
			$jobId = $db->lastInsertId();
			foreach($payload['location'] as $location=>$singleLocation) {
				if (is_null($singleLocation['cityId']) AND 
					is_null($singleLocation['latitude']) AND 
					is_null($singleLocation['longitude'])) {
					// edge case. we don't know the city and we couldn't geocode it. skip it.
					continue;
				}
				if (is_null($singleLocation['cityId'])) {
					// if we don't know this city, insert it
					$db->insert('city',
								['name'      => $location, 
								 'country'   => $singleLocation['country'],
								 'latitude'  => $singleLocation['latitude'], 
								 'longitude' => $singleLocation['longitude'] ]);
					$payload['location'][$location]['cityId'] = $db->lastInsertId();
				}

				$db->insert('job_city',
							['id_job' => $jobId,
							 'id_city' => $payload['location'][$location]['cityId']]);

			}

			$db->commit();	
			$returnValue = true;
		} catch (Exception $e) {
			$db->rollback();
print_r($e);			
			$returnValue = false;
		}

		return $returnValue;
	}
}
