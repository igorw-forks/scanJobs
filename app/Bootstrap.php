<?PHP
/*
 * Determine what environment we are working in. The default is production.
 */
$env = getenv('APP_ENV') ?: 'prod';

/*
 * Create the application
 */
$app = new Silex\Application(); 

/*
 * Now get the proper configuration file.
 */
$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__."/../config/$env.json"));

/*
 * Connect to the database. yes, this is probably the wrong place to do this. 
 * I'm putting it here for now until I can figure out where to put it.
 */
$app->register(new Silex\Provider\DoctrineServiceProvider(), 
               array('db.options' => $app['database']));

return $app;
