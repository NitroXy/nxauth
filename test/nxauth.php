<?php

require_once "../include.php";

$base_url = dirname($_SERVER['REQUEST_URI']);

{

	/* Settings for CAS via NitroXy.com */

	$cas_config = array(
		'site' => "nitroxy.com",
		'port' => 443,
		'key_id' => "nxauth",
		'private_key' => dirname(__FILE__)."/nxauth.priv",
		'ca_cert' => "$nxauth_root/certs/GeoTrustGlobalCA.pem",
	);

	NXAuth::init($cas_config);

}
