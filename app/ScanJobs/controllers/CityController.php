<?PHP
namespace ScanJobs\Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;

class CityController implements ControllerProviderInterface
{   
    protected $app;

    public function connect(Application $app)
    {  
        $this->app = $app;

        $getCityList = function() 
        {   
            return $this->getCityList();
        };   

        $controller = $app['controllers_factory'];
        $controller->get('/',$getCityList);

        return $controller;
    }   


	protected function getCityList()
	{
		$country='US'; //parameterize this
		$db = $this->app['db'];
		$sql = 'SELECT c.name,
					   c.latitude,
					   c.longitude,
					   c.country	
				  FROM city c
				 WHERE c.country=?
				 ORDER BY name';
        $results = $db->executeQuery($sql,array($country))
		              ->fetchAll();
		$payload = array('results' => $results);
		return $this->app->json($payload,200);
	}

}

