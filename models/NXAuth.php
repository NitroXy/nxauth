<?php

class NXAuth {

	private static $private_key = null; /* Cert for getting extra data */
	private static $config = null;
	private static $url = null;

	private static $ca_cert = null;

	private static $user = null;
	private static $api_model = null;


	/**
	 * Initialize the class, this must be called before anything else
	 * @param $repo_root
	 * @param $config
	 */
	public static function init($config) {
		phpCAS::client(CAS_VERSION_2_0, $config['site'], $config['port'], "cas");

		if(isset($config['key_id'])) {
			phpCAS::setServerServiceValidateURL($config['site'] . "/cas/serviceValidate/{$config['key_id']}");
		}

		phpCAS::setPostAuthenticateCallback(function($logout_token) {
			if(phpCAS::hasAttribute('sequence_token')) {
				$_SESSION['sequence_token'] = phpCAS::getAttribute('sequence_token');
			} else if(static::$config['key_id']) {
				throw new NXAuthError("key_id set, but no sequence_token was recieved. Is the api key still valid?");
			}
		});

		static::$config = $config;

		if(isset($config['private_key'])) {
			$key = $config['private_key'];
			static::$private_key = openssl_get_privatekey("file:///$key");
			if(static::$private_key === false) {
				throw new NXAuthError("Failed to open private key $key");
			}
		}

		if(isset($config['ca_cert']) && $config['ca_cert'] != null) {
			phpCAS::setCasServerCACert($config['ca_cert']);
			static::$ca_cert = $config['ca_cert'];
		} else {
			phpCAS::setNoCasServerValidation();
		}

		static::$url = $config['site']. "/cas";
	}

	public static function login() {
		phpCAS::forceAuthentication();
	}

	public static function logout() {
		phpCAS::logout();
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
		if(static::is_authenticated()) {
			if(static::$user == null) static::$user = new NXUser();
			return static::$user;
		} else return null;
	}

	/**
	 * Method used by NXAPI
	 */
	public static function request_data($func, $options) {
		$request = new CAS_Request_CurlRequest();

		$options['ticket'] = static::ticket();
		$options['sequence_token'] = static::sequence_token();

		$request_data = base64_encode(json_encode($options));

		$signature = static::sign($request_data);

		$params = array(
			'data' => $request_data,
			'signature' => $signature,
		);

		$request->setUrl(static::$url . "/api/$func?" . http_build_query($params));
		if(static::$ca_cert) {
			$request->setSslCaCert(static::$ca_cert, true);
		}

		$request->addHeader("pragma: no-cache");
		$request->addHeader("accept: application/json");
		$request->addHeader("connection: keep-alive");

		if($request->send()) {
			$body = $request->getResponseBody();
			$ret = json_decode($body);
			var_dump($body);
			if(isset($ret->sequence_token)) {
				static::set_sequence_token($ret->sequence_token);
			}
			return $ret;
		} else {
			throw new NXAuthError("Failed to request extra data: ". $request->getErrorMessage());
		}
	}

	private static function sign($data) {
		if(static::$private_key == null) throw new NXAuthError("Private key required to get extra data");

		$signature = null;
		if(openssl_sign($data, $signature, static::$private_key)) {
			return base64_encode($signature);
		} else {
			throw new NXAuthError("Failed to sign data");
		}
	}

	private static function sequence_token() {
		return $_SESSION['sequence_token']++;
	}

	private static function set_sequence_token($token) {
		$_SESSION['sequence_token'] = $token;
	}

	private static function ticket() {
		if(static::is_authenticated()) {
			return $_COOKIE['PHPSESSID'];
		} else {
			return null;
		}
	}
}

class NXAuthError extends Exception {
	public function __construct($str) {
		parent::__construct($str);
	}
}
