<?php

include "nxauth.php";

if ( NXAuth::user() ){
	header("Location: $base_url");
	exit;
}

NXAuth::login($base_url);
