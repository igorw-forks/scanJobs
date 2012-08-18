#!/usr/bin/env php
<?PHP
// there has got to be an easier way
$a = array();
//$a['base_url'] = 'http://projx.dev';
$a['application_dir'] = '/Users/cal/Projects/scanJobs';
$a['document_dir'] = $a['application_dir'].'/public';
$a['database'] = array();
$a['database']['user'] = null;
$a['database']['password'] = null;
$a['database']['driver'] = 'pdo_sqlite';
$a['database']['path'] = $a['application_dir'] . '/data/scanJobs.sqlite';
$a['debug'] = true;
$b = json_encode($a);
file_put_contents('../config/dev.json',$b);
