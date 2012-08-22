<?PHP
namespace ScanJobs\Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;

class ParametersController implements ControllerProviderInterface
{   
    protected $app;

    public function connect(Application $app)
    {  
        $this->app = $app;

		$controller = $app['controllers_factory'];
        return $controller;
    }   


}

