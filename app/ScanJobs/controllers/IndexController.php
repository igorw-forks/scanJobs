<?PHP
namespace ScanJobs\Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;

class IndexController implements ControllerProviderInterface
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
		$fileName = $this->app['document_dir'].'/display.html';
		$output   = file_get_contents($fileName);
		return $output;
	}

}
