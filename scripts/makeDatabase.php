<?PHP
/*
 * Eventually make a cli script out of this using composer.
 */
if (file_exists('../data/scanJobs.sqlite')) {
	unlink('../data/scanJobs.sqlite');
}

if ($db = new SQLite3('../data/scanJobs.sqlite')) {
	$sql = file_get_contents('sqlite.sql');
    $results = $db->exec($sql);
	
	if (!$results) {
		$errMessage = $db->lastErrorMsg();
		echo "ERROR:".$errMessage."\n";
	}
	
	$db=null;
} else {
	echo "Something went wrong creating the database.\n";
}
die();


