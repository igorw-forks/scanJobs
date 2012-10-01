<?PHP
require_once __DIR__.'/../vendor/autoload.php';

use ScanJobs\Controller;

$app = require '../app/Bootstrap.php';

/*
 * Build the routes
 */
$app->mount('/companies', new Controller\CompanyController());

$app->get('/jobs/', 'ScanJobs\Controller\MainController::jobListAction');
$app->get('/cities/', 'ScanJobs\Controller\MainController::cityListAction');
$app->get('/', 'ScanJobs\Controller\MainController::indexAction');

/*
 * Do the deed
 */
$app->run();

