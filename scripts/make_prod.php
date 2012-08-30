#!/usr/bin/env php
<?PHP
// there has got to be an easier way
$a = array();
$a['application_dir'] = './';
$a['document_dir'] = $a['application_dir'].'/public';
$a['database'] = array();
$a['database']['user'] = null;
$a['database']['password'] = null;
$a['database']['driver'] = 'pdo_sqlite';
$a['database']['path'] = $a['application_dir'] . '/data/scanJobs.sqlite';
$a['debug'] = true;
$a['googlemaps']['apikey'] = 'AIzaSyANajU1W0SpMTU0OSRJdQqea0i7ecv8BjQ';
$b = json_encode($a);
file_put_contents('../config/prod.json',$b);
