#!/usr/bin/env php
<?PHP
$a = array();
$a['application_dir'] = './';
$a['document_dir'] = $a['application_dir'].'/public';
$a['database'] = array();
$a['database']['user'] = null;
$a['database']['password'] = null;
$a['database']['driver'] = 'pdo_sqlite';
$a['database']['path'] = $a['application_dir'] . '/data/scanJobs.sqlite';
$a['debug'] = false;
$a['googlemaps']['apikey'] = '';
$flags = defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE : 0;
$b = json_encode($a, $flags);
file_put_contents('../config/prod.json',$b);
