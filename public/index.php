<?PHP
require_once __DIR__.'/../vendor/autoload.php';

use ScanJobs\Controllers;

$app = require '../app/Bootstrap.php';

/*
 * Build the routes 
 */
$app->mount('/jobs', new Controllers\JobsController());
$app->mount('/cities', new Controllers\CityController());
$app->mount('/companies', new Controllers\CompanyController());
$app->mount('/', new Controllers\IndexController());

/*
 * Do the deed
 */
$app->run();

