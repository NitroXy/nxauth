<?php

try {

include "nxauth.php";

NXAuth::login();

function dump($data) {
	echo "<pre>\n";
	var_dump($data);
	echo "</pre>\n";
}

$user = NXAuth::user();

$functions = array(
	'groups' => array('user' => $user->id),
	'rights' => array('user' => $user->id),
	'has_right' => array('user' => $user->id, 'right' => 'handle_api_accesses'),
	'crew_groups' => array('user' => $user->id),
	'is_crew' => array('user' => $user->id),
	'events' => null,/* null is expanded to array(array()) */
	'event_info' => null
);

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<title>NXAuth Test</title>
	</head>
	<body>
		<p><a href="logout.php">Logga ut</a></p>
		<h1>User</h1>
		<?php dump(NXAuth::user()); ?>
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
