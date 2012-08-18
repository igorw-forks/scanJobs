#!/usr/bin/env php

<?php
require_once __DIR__.'/../vendor/autoload.php'; 

use CalEvans\ScanJobsCommand;
use CalEvans\Google\Geocode as Geocode;
use Knp\Provider\ConsoleServiceProvider;

$app = require '../app/Bootstrap.php';

$app->register(new ConsoleServiceProvider(),
                array('console.name'              => 'ScanJobs',
                      'console.version'           => '1.0.0',
                      'console.project_directory' => __DIR__.'/..'));
$application = $app['console'];
$x = new ScanJobsCommand();
$x->addGeocodeer(new Geocode());
$application->add($x);
$application->run();
