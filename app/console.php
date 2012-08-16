<?php
/*
 * THIS IS SAMPLE CODE
 */
use Knp\Provider\ConsoleServiceProvider;

$app->register(new ConsoleServiceProvider(), array(
    'console.name'              => 'MyApplication',
    'console.version'           => '1.0.0',
    'console.project_directory' => __DIR__.'/..'
));
