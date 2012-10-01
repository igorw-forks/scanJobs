<?PHP
namespace ScanJobs\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;

class CompanyController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controller = $app['controllers_factory'];

		$controller->get('/city/{id_city}', array($this, 'companyListAction'));

		$controller->get('/{id_city}/', array($this, 'companyListAction'))
                   ->value('id_city', null);

		$controller->get('/', array($this, 'companyListAction'))
                   ->value('id_city', null);

        return $controller;
    }

	public function companyListAction(Application $app, $id_city)
	{
		$db = $app['db'];

		if (is_null($id_city)) {
			$sql = 'Select company.id, company.company_name
                      from company
                     order by company_name ASC';
		} else {
			$sql = 'Select company.id,
                           company.company_name
                      from company left join job on company.id = job.id_company
                                   left join job_city on job.id = job_city.id_job
                                   left join city on job_city.id_city = city.id
                     where city.id = %d and
                           job.id_company >0
					 group by company.company_name
					 order by company_name ASC';
			$sql = sprintf($sql,$id_city);
		}

        $results = $db->executeQuery($sql)
		              ->fetchAll();
		$payload = array('message'=>'OK',
		                 'results' => $results);
		return $app->json($payload,200);
	}
}

