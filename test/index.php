<?php

try {

include "nxauth.php";

function dump($data) {
	echo "<pre>\n";
	var_dump($data);
	echo "</pre>\n";
}

$user = NXAuth::user();
$functions = [];

if ( $user ){
	$functions = [
		'groups' => ['user' => $user->id],
		'rights' => ['user' => $user->id],
		'has_right' => ['user' => $user->id, 'right' => 'handle_api_accesses'],
		'crew_groups' => ['user' => $user->id],
		'is_crew' => ['user' => $user->id],
		'events' => null,/* null is expanded to array(array()) */
		'event_info' => null,
	];
}

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<title>NXAuth Test</title>
	</head>
	<body>
		<h1>NXAuth Test</h1>
		<?php if ( $user ): ?>
			<form action="logout.php" method="get">
				<button type="submit">Logout</button>
			</form>
			<h2>User</h2>
			<?php dump(NXAuth::user()); ?>
			<h2>Functions</h2>
			<?php foreach($functions as $f => $args): ?>
				<?php if($args === null) $args = array(); ?>
				<h3><?=$f?></h3>
				<?php dump(NXAPI::$f($args)); ?>
			<?php endforeach; ?>
		<?php else: ?>
			<form action="login.php" method="get">
				<button type="submit">Login</button>
			</form>
		<?php endif; ?>
	</body>
</html>
<?php
} catch (Exception $e) {
	echo "<pre>Error: {$e}:\n";
	$e->getTraceAsString();
}
