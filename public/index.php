<?PHP
require_once __DIR__.'/../vendor/autoload.php';
use ScanJobs\Controllers\JobsController as JobsController;
use ScanJobs\Controllers\IndexController as IndexController;
use ScanJobs\Controllers\ParametersController as ParametersController;

$app = require '../app/Bootstrap.php';

/*
 * Build the routes 
 */
$app->mount('/jobs', new JobsController());
$app->mount('/parameters', new ParametersController());
$app->mount('/', new IndexController());

/*
 * Do the deed
 */
$app->run();

