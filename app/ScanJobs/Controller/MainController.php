<?PHP
namespace ScanJobs\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;

use ScanJobs\Model\Job;

class MainController
{
	public function indexAction(Application $app)
	{
		$fileName = $app['document_dir'].'/display.html';
		$output   = file_get_contents($fileName);
		return $output;
	}

    public function cityListAction(Application $app)
    {
        $country='US'; //parameterize this
        $db = $app['db'];
        $sql = 'SELECT c.id,
                       c.name,
                       c.latitude,
                       c.longitude,
                       c.country
                  FROM city c
                 WHERE c.country=?
                 ORDER BY name';
        $results = $db->executeQuery($sql,array($country))
                      ->fetchAll();
        $payload = array('results' => $results);
        return $app->json($payload,200);
    }

    public function jobListAction(Application $app)
    {
        $results = Job::fetchJobsList($app['db'],'US');
        $payload = array('results' => $results);
        return $app->json($payload,200);
    }
}
