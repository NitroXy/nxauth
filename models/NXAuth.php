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
	public static init($config) {
		phpCAS::client(CAS_VERSION_2_0, $config['site'], $config['port'], "cas");

		static::$config = $config;

		if(isset($config['private_key'])) NXAuth::$private_key = $config['private_key'];

		if(isset($config['ca_cert']) && $config['ca_cert'] != null) {
			phpCAS::setCasServerCACert($config['ca_cert']);
			static::$ca_cert = $config['ca_cert'];
		} else {
			phpCAS::setNoCasServerValidation();
		}

		static::$extra_path = $config['site']. "/cas/extra";
	}

	public static authenticate() {
		phpCAS::forceAuthentication();
	}

	public static logout() {
		phpCAS::logout();
	}

	public static is_authenticated() {
		return phpCAS::isAuthenticated();
	}

	public static user() {
		if(static::is_authenticated()) {
			return new NXUser();
		} else return null;
	}

	public function request_data($options) {
		$request = new CAS_Request_CurlRequest();
		$request->setUrl(static::$extra_path);
		if(static::$ca_cert) {
			$request->setSslCaCert(static::$ca_cert, true);
		}

		$request->addHeader("pragma: no-cache");
		$request->addHeader("accept: application/json");
		$request->addHeader("connection: keep-alive");
		$request->addHeader("content-type: application/json");

		$request->makePost();
		$request->setPostBody(json_encode($options));
		if($request->send()) {
			$body = $request->getReponseBody();
			return json_decode($body);
		} else {
			throw new NXAuthError("Failed to request extra data: ". $request->getErrorMessage());
		}
	}
}

class NXAuthError {
	public function __construct($str) {
		parent::__construct($str);
	}
}
