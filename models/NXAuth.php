<?php

class NXAuth {

	private static $config = null;

	private static $ca_cert = null;

	private static $user = null;

	private static function resolve_filename($filename){
		global $nxauth_root;
		if ( $filename[0] == '/' ) return $filename;        /* absolute path */
		$real_path = $nxauth_root . '/' . $filename;              /* relative to api folder */
		if(!is_readable($real_path)) {
			throw new NXAuthError("Could not open $real_path for reading, check that it exists and are readable by the webserver");
		}
		return $real_path;
	}

	/**
	 * Initialize the class, this must be called before anything else
	 * @param $config
	 * @param bool $changeSessionID Allow phpCAS to change the session_id (Single Sign Out/handleLogoutRequests is based on that change)
	 * @param $debugLog Set to a path to enable debug log
	 */
	public static function init($config, $changeSessionID = true, $debugLog = null) {
		if($debugLog != null) phpCAS::setDebug($debugLog);

		phpCAS::client(CAS_VERSION_2_0, $config['site'], $config['port'], "cas", $changeSessionID);

		self::$config = $config;

		$private_key = null;

		if(isset($config['private_key'])) {
			$key = static::resolve_filename($config['private_key']);
			$private_key = openssl_get_privatekey("file:///$key");
			if($private_key === false) {
				throw new NXAuthError("Failed to open private key $key");
			}
		}

		if(isset($config['ca_cert']) && $config['ca_cert'] != null) {
			self::$ca_cert = static::resolve_filename($config['ca_cert']);
			phpCAS::setCasServerCACert(self::$ca_cert);
		} else {
			phpCAS::setNoCasServerValidation();
		}

		NXAPI::init(array(
			'private_key' => $private_key,
			'key_id' => $config['key_id'],
			'url' => "https://" . $config['site'],
			'ca_cert' => self::$ca_cert
		));

	}

	public static function login() {
		phpCAS::forceAuthentication();
	}

	public static function logout($return_uri = "") {
		$options = "";

		$host = ($_SERVER['HTTPS'] ? "https://" : "http://" . $_SERVER['HTTP_HOST']) . "/";
		if(strpos($return_uri, "http") == 0) $host = "";

		if($return_uri !== null) $options = array('service' => "$host$return_uri");
		phpCAS::logout($options);
		NXAPI::clear_cache();
	}

	public static function is_authenticated() {
		return phpCAS::isAuthenticated();
	}

	/**
	 * Get a model containing data for the current user
	 *
	 * This method is singletone'd so calling it multiple times is no performace hit
	 *
	 * @return NXUser instance or null if not authenticated
	 */
	public static function user() {
		if(self::is_authenticated()) {
			if(self::$user == null) self::$user = new NXUser();
			return self::$user;
		} else return null;
	}
}

class NXAuthError extends Exception {
	public function __construct($str) {
		parent::__construct($str);
	}
}
