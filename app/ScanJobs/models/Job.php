<?PHP
namespace ScanJobs\Models;

class Job
{
	protected $app;
	protected $data;
	protected $geocoder;

	public function __construct($app,$geocoder)
	{
		$this->app      = $app;
		$this->geocoder = $geocoder;
	}


	public function load($id=null)
	{
		$db = $this->app['db'];
		$sql = 'select j.*,
		               c.id as cityId,
					   c.name,
					   c.country,
					   c.latitude,
					   c.longitude
				  from job j left join job_city jc on j.id = jc.id_job
				             left join city c on jc.id_city = c.id
				 where j.id = ?;
								   ';
		$results = $db->executeQuery($sql,array((int)$id))
                      ->fetchAll();

		if (count($results)<1) {
			// probably better to throw an exception here.
			return false;
		}

		$this->data['guid']        = $results[0]['guid'];
		$this->data['pubDate']     = $results[0]['pubDate'];
		$this->data['title']       = array('title'=>$results[0]['title']);
		$this->data['telecommute'] = (bool)$results[0]['telecommute'];
		$this->data['id']          = $results['id'];
		
		// eventually all this needs to be in city models
		$this->data = array();
		foreach($results as $singleLocation) {
			$this->data['location']['name']      = $singleLocation['name'];
			$this->data['location']['cityId']    = $singleLocation['citId'];
			$this->data['location']['latitude']  = $singleLocation['latitude'];
			$this->data['location']['longitude'] = $singleLocation['longitude'];
		}
		return;
	}


	public function save()
	{
		$returnValue = false;

		if (isset($this->data['id']) &&  !is_null($this->data['id'])) {
			$returnValue = $this->updateExistingJob();
		} else {
			$returnValue = $this->insertNewJob();
		}

        return $returnValue;

	}


	public function delete()
	{
		return;
	}


	static function fetchId($guid=null,$db)
	{
		$returnValue = null;
		if (!is_null($guid)) {
        	$returnValue = $db->executeQuery("select id from job where guid=?",array($guid))
            	              ->fetchColumn();
		}
        return $returnValue;
	}


	public function parse($job)
	{
        $this->data = array();
        $this->data['guid'] = $job->guid;

		$this->parseTitle($job->title)
             ->parseLocation()
			 ->geocodeLocations()
			 ->parseTelecommute()
			 ->parseTags();

		$pubDate = new \DateTime($job->pubDate);
		$this->data['pubDate']     = $pubDate->format('Y-m-d h:i e');

        return $this;
    }


    protected function parseTitle($title)
    {
		$this->data['title'] = array();
        
		$this->data['title']['original'] = $title;
        $title = trim(str_replace('(telecommute)','',$title));

        $locationStart = strripos($title,'(')+1;
        $this->data['title']['full'] = substr($title,0,$locationStart-1);

        $beginCompany = strrpos($this->data['title']['full'],' at ')+4;

        $this->data['title']['company'] = substr($this->data['title']['full'],$beginCompany);
        $this->data['title']['title']   = substr($this->data['title']['full'],0,$beginCompany-4);

        return $this;
    }


    protected function parseLocation()
    {
		$title = $this->data['title']['original'];
        $this->data['location'] = array();
        $title = trim(str_replace('(telecommute)','',$title));
        $locationStart = strripos($title,'(')+1;
        $locationEnd   = strripos($title,')');
        $location = trim(substr($title, $locationStart, ($locationEnd-$locationStart)));
        $holdingArray = explode(';',$location);

		foreach($holdingArray as $location) {
            $location = trim($location);
            $this->data['location'][$location] = array('originalLocation'=>$location,
                                                       'latitude'  => null,
                                                       'longitude' => null,
                                                       'cityId'    => null);
        }

        return $this;
    }
	

    protected function geocodeLocations()
    {
        $db = $this->app['db'];
		
        foreach($this->data['location'] as $key=>$value) {

            $cityId = $db->executeQuery("select id from city where name=?",array($key))
                         ->fetchColumn();

            if ((int)$cityId>0) {
                // we know this city already
                $locationArray[$key]['cityId']=$cityId;
            } else {
                // we've got to go find this city
                $payload = $this->geocoder->fetchGeocode($key);

                if ($payload->status==="OK") {
                    $this->data['location'][$key]['latitude']  = $payload->results[0]->geometry->location->lat;
                    $this->data['location'][$key]['longitude'] = $payload->results[0]->geometry->location->lng;
                    $this->data['location'][$key]['country']   = $this->geocoder->fetchCountry();
                }
            }
        }
        return $this;
    }


	protected function parseTelecommute()
	{
        $this->data['telecommute'] = !(strpos($this->data['title']['original'],'(telecommute)')===false);
        return $this;
	}

	
	protected function parseTags()
	{
		// this is a bit of brute force but it'll do for now.
		$html = file_get_contents($this->data['guid']);
		preg_match_all('/<a class="post-tag" href=".*">(.*)<\/a>/mU',$html,$matches);
		$this->data['tags'] = $matches[1];
		return $this;
	}


	protected function insertNewJob()
	{
		// do some basic data checks to make sure we have a record to save.
        $db = $this->app['db'];
		$returnValue = false;

        $db->beginTransaction();
        try {
            $db->insert('job',
                        ['title'       => $this->data['title']['original'],
                         'telecommute' => $this->data['telecommute'],
                         'date_posted' => $this->data['pubDate'],
                         'guid'        => $this->data['guid'] ]);
            $this->data['id'] = $db->lastInsertId();

            // This is eventually moving into the city model
            foreach($this->data['location'] as $location=>$singleLocation) {
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

                    $this->data['location'][$location]['cityId'] = $db->lastInsertId();
                }

                $db->insert('job_city',
                            ['id_job'  => $this->data['id'],
                             'id_city' => $payload['location'][$location]['cityId']]);

            }

            $db->commit();
            $returnValue = true;
        } catch (Exception $e) {
            $db->rollback();
            // do something more clever than jsut dumping the exception to the screen!
            print_r($e);
            $returnValue = false;
        }
		
		return $returnValue;
	}


	protected function updateExistingJob()
	{
		return $returnValue;
	}
	
}
