<?php

require_once "libs/nxauth/include.php";

{

	/* Settings for CAS via NitroXy.com */

	$dir = dirname(__FILE__); /* path to cert must be absolute, so include this */

	$cas_config = array(
		'site' => "nitroxy.com",
		'port' => 443,
		'key_id' => "nxauth", /* The id for the local site, used thougheter with the private key for extra data */
		'private_key' => null, /* Specify path to applications private key (NEVER check in the private key to a git repo) for extra data (if any) */
		'ca_cert' => "$nxauth_root/certs/GeoTrustGlobalCA.pem", /* If this is null no cert validation will be done */
	);

	NXAuth::init($cas_config);

}

/* Include local user config */
if(file_exists("$dir/nxauth.local.php")) {
	require_once "$dir/nxauth.local.php";
}
