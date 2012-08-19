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

        $getIndex = function() 
        {   
            return $this->getIndex();
        };   
    
        $controller = $app['controllers_factory'];

        $controller->get('/',$getIndex);

        return $controller;
    }   

    protected function getIndex()
    {   
        return 'Jobs Controller';
    }   

}

