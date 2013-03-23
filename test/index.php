<?php

include "nxauth.php";

NXAuth::login();

function dump($data) {
	echo "<pre>\n";
	var_dump($data);
	echo "</pre>\n";
}

$functions = array(
	'groups' => null, /* null is expanded to array(array()) */
	'rights' => null,
	'has_right' => array('right' => 'handle_api_accesses'),
	'crew_groups' => null,
	'events' => null,
	'event_info' => null
);

try {
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<title>NXAuth Test</title>
	</head>
	<body>
		<p><a href="logout.php">Logga ut</a></p>
		<h1>User</h1>
		<?php dump(NXAuth::user()); ?>
		<h1>Session</h1>
		<?php dump($_SESSION) ?>
		<h1>Functions</h1>
<?php
foreach($functions as $f => $args) {
	if($args === null) $args = array();
	echo "<h2>$f</h2>\n";
	dump(NXAPI::$f($args));
}

} catch (Exception $e) {
	echo "<pre>Error: {$e}:\n";
	$e->getTraceAsString();
}