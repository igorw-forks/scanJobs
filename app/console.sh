#!/usr/bin/env php
<?php
require_once __DIR__.'/../vendor/autoload.php'; 

use ScanJobs\Command;
use CalEvans\Google\Geocode as Geocode;
use Knp\Provider\ConsoleServiceProvider;

$app = require 'Bootstrap.php';

$app->register(new ConsoleServiceProvider(),
                array('console.name'              => 'Console',
                      'console.version'           => '1.0.0',
                      'console.project_directory' => __DIR__.'/..'));
$application = $app['console'];
$scan = new Command\ScanJobsCommand();
$scan->addGeocodeer(new Geocode());
$application->add($scan);
$application->add(new Command\NewDatabaseCommand());
$application->add(new Command\WorkCommand());
$application->run();
