<?php

$nxauth_root = dirname(__FILE__);

require_once "$nxauth_root/libs/phpCAS/CAS.php"; //Include phpCAS

$file_dir = realpath(dirname($argv[0]));

{
	require_once "$file_dir/cas_config.php";

	phpCAS::client(CAS_VERSION_2_0, $cas_config['site'], $cas_config['port'], $cas_config['uri']);
}

?>
