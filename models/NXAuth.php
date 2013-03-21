<?php

class NXAuth {

	private static $private_key = null; /* Cert for getting extra data */
	private static $config = null;
	private static $extra_path = null;

	private static $ca_cert = null;


	/**
	 * Initialize the class, this must be called before anything else
	 * @param $repo_root 
	 * @param $config 
	 */
	public static function init($config) {
		phpCAS::client(CAS_VERSION_2_0, $config['site'], $config['port'], "cas");

		static::$config = $config;

		if(isset($config['private_key'])) {
			$key = $config['private_key'];
			static::$private_key = openssl_get_privatekey("file:///$key");
		}

		if(isset($config['ca_cert']) && $config['ca_cert'] != null) {
			phpCAS::setCasServerCACert($config['ca_cert']);
			static::$ca_cert = $config['ca_cert'];
		} else {
			phpCAS::setNoCasServerValidation();
		}

		static::$extra_path = $config['site']. "/cas/extra";
	}

	public static function authenticate() {
		phpCAS::forceAuthentication();
	}

	public static function logout() {
		phpCAS::logout();
	}

	public static function is_authenticated() {
		return phpCAS::isAuthenticated();
	}

	public static function user() {
		if(static::is_authenticated()) {
			return new NXUser();
		} else return null;
	}

	public static function request_data($options) {
		$request = new CAS_Request_CurlRequest();

		$request_data = base64_encode(http_build_query($options));

		$signature = static::sign($request_data);

		$params = array(
			'data' => $request_data,
			'signature' => $signature,
			'site' => static::$config['site_id']
		);

		$request->setUrl(static::$extra_path . "?" . http_build_query($params));
		if(static::$ca_cert) {
			$request->setSslCaCert(static::$ca_cert, true);
		}

		$request->addHeader("pragma: no-cache");
		$request->addHeader("accept: application/json");
		$request->addHeader("connection: keep-alive");

		if($request->send()) {
			$body = $request->getResponseBody();
			return $body;
			return json_decode($body);
		} else {
			throw new NXAuthError("Failed to request extra data: ". $request->getErrorMessage());
		}
	}

	public static function sign($data) {
		if(static::$private_key == null) throw new NXAuthError("Private key required to get extra data");

		$signature = null;
		if(openssl_sign($data, $signature, static::$private_key)) {
			return base64_encode($signature);
		} else {
			throw new NXAuthError("Failed to sign data");
		}
	}
}

class NXAuthError {
	public function __construct($str) {
		parent::__construct($str);
	}
}
