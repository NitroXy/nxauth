<?php

class NXAPI {
	private static $private_key = null;
	private static $key_id = null;
	private static $url = null;
	private static $ca_cert = null;

	public static function init($config) {
		static::$private_key = $config['private_key'];
		static::$key_id = $config['key_id'];
		static::$url = $config['url'];
		static::$ca_cert = $config['ca_cert'];
	}

	public static function __callStatic($func, $arguments) {

		if(static::$private_key == null) {
			throw new NXAPIError("No API key found, can't use API");
		} else if(static::$key_id == null) {
			throw new NXAPIError("No API key id found, can't use API");
		} else {
			$options = isset($arguments[0]) ? $arguments[0] : array();

			for($try=0; $try < 2; ++$try) {
				$data = NXAPI::request_data(array('function' => $func, 'arguments' => $options));
				if(!$data) {
					throw new NXAPIError("NXAPI: Got null");
				}
				if($data->status == "SEQERR") {
				} else break;
			}
			if($data->status == "OK") {
				return $data->data;
			} else {
				throw new NXAPIError($data->message);
			}
		}
	}

	/**
	 * @param $call array of function calls
	 */
	private static function request_data($call) {
		$request = new CAS_Request_CurlRequest();

		$options = array(
			'sequence_token' => static::sequence_token(),
			'call' => $call
		);

		$request_data = base64_encode(json_encode($options));

		$signature = static::sign($request_data);

		$params = array(
			'data' => $request_data,
			'signature' => $signature,
			'key' => static::$key_id
		);

		$request->setUrl(static::$url . "/api?" . http_build_query($params));
		if(static::$ca_cert) {
			$request->setSslCaCert(static::$ca_cert, true);
		}

		$request->addHeader("pragma: no-cache");
		$request->addHeader("accept: application/json");
		$request->addHeader("connection: keep-alive");

		if($request->send()) {
			$body = $request->getResponseBody();
			$ret = json_decode($body);
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
		if(!isset($_SESSION['sequence_token'])) $_SESSION['sequence_token'] = 0;
		return $_SESSION['sequence_token']++;
	}

	private static function set_sequence_token($token) {
		$_SESSION['sequence_token'] = $token;
	}
}

class NXAPIError extends Exception {
	public function __construct($msg) {
		parent::__construct($msg);
	}
}
