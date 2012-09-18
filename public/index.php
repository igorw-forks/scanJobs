<?PHP
require_once __DIR__.'/../vendor/autoload.php';

use ScanJobs\Controller;

$app = require '../app/Bootstrap.php';

/*
 * Build the routes 
 */
$app->mount('/jobs', new Controller\JobsController());
$app->mount('/cities', new Controller\CityController());
$app->mount('/companies', new Controller\CompanyController());
$app->mount('/', new Controller\IndexController());

/*
 * Do the deed
 */
$app->run();

