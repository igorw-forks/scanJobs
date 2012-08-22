<?PHP
namespace ScanJobs\Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;

class JobsController implements ControllerProviderInterface
{   
    protected $app;

    public function connect(Application $app)
    {  
        $this->app = $app;

        $getJobsList = function() 
        {   
            return $this->getJobsList();
        };   

        $getDayList = function() 
        {   
            return $this->getDayList();
        };   
   
		$getCityList = function()
		{
			return $this->getCityList();
		};

        $controller = $app['controllers_factory'];
		$controller->get('/dayList',$getDayList);
		$controller->get('/cityList',$getCityList);
        $controller->get('/',$getJobsList);

        return $controller;
    }   

    protected function getJobsList()
    {   
		$db = $this->app['db'];
		$country = 'US';
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
		$payload = array('results' => $results);
		return $this->app->json($payload,200);
	
	}   

	// this one needs to be in the city controller
	protected function getCityList()
	{
		$country='US'; //parameterize this
		$db = $this->app['db'];
		$sql = 'SELECT c.name,
					   c.latitude,
					   c.longitude,
					   c.country	
				  FROM city c
				 WHERE c.country=?';
        $results = $db->executeQuery($sql,array($country))
		              ->fetchAll();
		$payload = array('results' => $results);
		return $this->app->json($payload,200);
	}

	// my gut feeling is this belogns to jobs because jobs define what days are available.
    protected function getDayList()
    {   
        $payload = array();
        $db = $this->app['db'];
        $results = $db->query('SELECT substr(date_posted,1,10) as date_posted 
							     FROM job 
							    GROUP by date_posted 
								ORDER by date_posted;')
                      ->fetchAll();
        foreach($results as $row) {
            $payload[] = $row['date_posted'];
        }       
        return $this->app->json($payload,200);
    }    

}

