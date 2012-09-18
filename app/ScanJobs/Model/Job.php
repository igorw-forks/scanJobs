<?PHP
namespace ScanJobs\Model;

/*
 * @todo flesh out parseCompany. Pull it from the document, not the title. 
 * Grab the URL also, store them in the datastore. Future attributes include
 * Glassdoor score and LinkedIn mappings of employees.
 * @todo move all location processing into a City model.
 */
class Job
{
	protected $app;
	protected $data;
	protected $geocoder;
	protected $rawDocument;

	public function __construct($app,$geocoder)
	{
		$this->app         = $app;
		$this->geocoder    = $geocoder;
		$this->rawDocument = null;
		$this->resetData();
	}


	public function load($id=null)
	{
		$this->resetData();
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

		// tags
		$sql = "Select t.tag, 
		               t.id 
		          from tags t left join job_tag jt on t.id = jt.id_tag
				 where jt.id_job = ?";
		$results = $db->execQuery($sql,array((int)$id))
		              ->fetchAll();
		// store them in the tag array
		foreach($results as $singleTag) {
			$this->data['tag'][$singleTag['tag']] = $singleTag;	
		}

		return $this;
	}


	public function save()
	{
		$returnValue = false;

		if ($this->data['id']>=0) {
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


	static function fetchJobsList($db,$country)
	{
        $sql = "SELECT j.id,
                       j.title, 
                       j.date_posted, 
                       j.guid, 
                       c.name as city_name 
                  FROM job j LEFT JOIN job_city jc on j.id = jc.id_job
                             LEFT JOIN city c on jc.id_city = c.id
                 WHERE c.country = ?
                 ORDER BY date_posted";
        $results = $db->executeQuery($sql,array($country))
                      ->fetchAll();
        return $results;	
	}


	public function parse($job)
	{
		$this->resetData();
		$this->data['guid'] = (string)$job->guid;
		try 
		{
			$this->fetchHTML($this->data['guid']);
			$this->parseTitle((string)$job->title)
				 ->parseLocation()
				 ->geocodeLocations()
				 ->parseTelecommute()
				 ->parseCompany()
				 ->parseTags();

			$pubDate = new \DateTime($job->pubDate);
			$this->data['pubDate']     = $pubDate->format('Y-m-d h:i e');
		} Catch (\Exception $e) {
			if ($e->getCode()===1) {
				throw new \Exception('There was a problem loading the data from the site.');
			}
		}
        return $this;
    }


	protected function resetData()
	{
		$this->data = array('title'       => array(),
		                    'location'    => array(),
							'tag'         => array(),
							'company'     => array('id'           => null,
												   'company_name' => null,
												   'url'          => null),
						    'guid'        => null,
						    'pubDate'     => null,
							'id'          => -1,
						    'telecommute' => false);
		return $this;				  
	}


	protected function parseTitle($title)
    {
        
		$this->data['title']['original'] = $title;
        $title = trim(str_replace('(telecommute)','',$title));

        $locationStart = strripos($title,'(')+1;
        $this->data['title']['full'] = substr($title,0,$locationStart-1);

        $beginCompany = strrpos($this->data['title']['full'],' at ')+4;

        $this->data['title']['title']   = substr($this->data['title']['full'],0,$beginCompany-4);

        return $this;
    }


    protected function parseLocation()
    {
		// most of this should move into a city model. Let it deal with do we know it and if not geocoding it.
		$title = $this->data['title']['original'];
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
                $this->data['location'][$key]['cityId']=$cityId;
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
		if (!empty($this->rawDocument)) {
			$db = $this->app['db'];
			// this is a bit of brute force but it'll do for now.
			preg_match_all('/<a class="post-tag" href=".*">(.*)<\/a>/mU',$this->rawDocument,$matches);
			$sql = 'select id, tag from tag where tag=?';
			foreach ($matches[1] as $singleTag) {
				$singleTag = strtoupper($singleTag);
	            $this->data['tag'][$singleTag] = array('id'  => null,
	                                               'tag' => $singleTag);
				$results = $db->executeQuery($sql,array($singleTag))
				              ->fetchAll();

				if (count($results)===1) {
					$this->data['tag'][$singleTag]['id'] = $results[0]['id'];
				}

			}
		}

		return $this;
	}


	protected function parseCompany()
	{
		if (!empty($this->rawDocument)) {
			preg_match_all('/<a class="employer" href="(.*)" target="_blank">(.*)<\/a>/mU',$this->rawDocument,$matches);
			$sql = 'select id from company where company_name=?';
			if (count($matches)>0) {
				$this->data['company']['company_name']   = $matches[2][0];
				$this->data['company']['url']            = $matches[1][0];

				$results = $this->app['db']->executeQuery($sql,array($this->data['company']['company_name']))
					                       ->fetchAll();

				if (count($results)===1) {
					$this->data['company']['id'] = $results[0]['id'];
				}   

			}
		}	
		return $this;
	}


	protected function insertNewJob()
	{
		// do some basic data checks to make sure we have a record to save.
        $db = $this->app['db'];
		$returnValue = false;

        $db->beginTransaction();

        try {
			// Company
            if (is_null($this->data['company']['id'])) {
                $db->insert('company',
                            ['company_name' => $this->data['company']['company_name'],
                             'url'          => $this->data['company']['url']]);
                $this->data['company']['id'] = $db->lastInsertId(); 
            }


            $db->insert('job',
                        ['title'       => $this->data['title']['original'],
                         'telecommute' => $this->data['telecommute'],
                         'date_posted' => $this->data['pubDate'],
                         'guid'        => $this->data['guid'],
						 'id_company'  => $this->data['company']['id'] ]);

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
                             'id_city' => $this->data['location'][$location]['cityId']]);

            }
			
			// tags
			foreach($this->data['tag'] as $key=>$singleTag) {
				if (is_null($singleTag['id'])) {
					$db->insert('tag',
								['tag'=> $singleTag['tag']]);
					$this->data['tag'][$key]['id'] = $db->lastInsertId();			
				}
				$db->insert('job_tag',
						    ['id_job' => $this->data['id'],
							 'id_tag' => $this->data['tag'][$key]['id']]);
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
	
	
	protected function fetchHTML($guid)
	{
		// throw an exception here if it can't load?
		$this->rawDocument = file_get_contents($guid);
		if (empty($this->rawDocument)) {
			throw new \Exception('There was a problem loading the page from the site.',1);
		}
		return $this;
	}
	
	
}
