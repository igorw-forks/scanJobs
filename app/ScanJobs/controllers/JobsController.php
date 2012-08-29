<?PHP
namespace ScanJobs\Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use ScanJobs\Models\Job;

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

        $controller = $app['controllers_factory'];
        $controller->get('/',$getJobsList);

        return $controller;
    }   

    protected function getJobsList()
    {  
		$results = Job::fetchJobsList($this->app['db'],'US');
		$payload = array('results' => $results);
		return $this->app->json($payload,200);
	
	}   

}

