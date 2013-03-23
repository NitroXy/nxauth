<?php

require_once "../include.php";

{

	/* Settings for CAS via NitroXy.com */

	$cas_config = array(
		'site' => "nx.dev",
		'port' => 443,
		'key_id' => "nxauth",
		'private_key' => dirname(__FILE__)."/nxauth.priv",
		'ca_cert' => "$nxauth_root/certs/GeoTrustGlobalCA.pem",
	);

	NXAuth::init($cas_config);

}
