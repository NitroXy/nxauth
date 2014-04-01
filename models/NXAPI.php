<?php

class NXAPI {
	private static $private_key = null;
	private static $key_id = null;
	private static $url = null;
	private static $ca_cert = null;

	private static $curl_options = array();

	public static function init($config) {
		self::$private_key = $config['private_key'];
		self::$key_id = $config['key_id'];
		self::$url = $config['url'];
		self::$ca_cert = $config['ca_cert'];
		if(!isset($_SESSION['nxapi_cache'])) {
			$_SESSION['nxapi_cache'] = array();
		}

		if(self::$ca_cert == null) {
			$curl_options[CURLOPT_SSL_VERIFYHOST] = 0;
			$curl_options[CURLOPT_SSL_VERIFYPEER] = 0;
		}
	}

	public static function clear_cache() {
		$_SESSION['nxapi_cache'] = array();
	}

	public static function __callStatic($func, $arguments) {
		if(self::$private_key == null) {
			throw new NXAPIError(NXAPIError::MISSING_CONFIGURATION, "No API key found, can't use API");
		} else if(self::$key_id == null) {
			throw new NXAPIError(NXAPIError::MISSING_CONFIGURATION, "No API key id found, can't use API");
		} else {
			$options = isset($arguments[0]) ? $arguments[0] : array();
			$cache_string = self::cache_string($func, $options);

			if(isset($_SESSION['nxapi_cache'][$cache_string])) {
				return $_SESSION['nxapi_cache'][$cache_string];
			}

			for($try=0; $try < 2; ++$try) {
				list($data, $body) = NXAPI::request_data(array('function' => $func, 'arguments' => $options));
				if(!$data) {
					throw new NXAPIError(NXAPIError::GOT_NULL, "NXAPI: Got null", $body);
				}
				if($data->status == "SEQERR") {
				} else break;
			}
			if($data->status == "OK") {
				$_SESSION['nxapi_cache'][$cache_string] = $data->data;
				return $data->data;
			} else if($data->status == "SEQERR") {
				throw new NXAPIError(NXAPIError::SEQUENCE_ERROR, $data->message, $body);
			} else {
				throw new NXAPIError(NXAPIError::SERVER_ERROR, $data->message, $body);
			}
		}
	}


	private static function build_array_string($options) {
		$str = "";
		foreach($options as $k => $v) {
			$str .= "$k:";
			if(is_array($v)) {
				$str .= self::build_array_string($v);
			} else {
				$str .= $v;
			}
			$str .= ",";
		}
		return "{$str}";
	}

	private static function cache_string($func, $options) {
		$option_str = self::build_array_string($options);
		return "$func($option_str)";
	}

	/**
	 * @param $call array of function calls
	 */
	private static function request_data($call) {
		$request = new CAS_Request_CurlRequest();

		if(count(self::$curl_options) > 0) {
			$request->setCurlOptions(self::$curl_options);
		}

		$options = array(
			'sequence_token' => self::sequence_token(),
			'call' => $call
		);

		$request_data = base64_encode(json_encode($options));

		$signature = self::sign($request_data);

		$params = array(
			'data' => $request_data,
			'signature' => $signature,
			'key' => self::$key_id
		);

		$request->setUrl(self::$url . "/api?" . http_build_query($params));
		if(self::$ca_cert) {
			$request->setSslCaCert(self::$ca_cert, true);
		}

		$request->addHeader("pragma: no-cache");
		$request->addHeader("accept: application/json");
		$request->addHeader("connection: keep-alive");

		if($request->send()) {
			$body = $request->getResponseBody();
			$ret = json_decode($body);
			if(isset($ret->sequence_token)) {
				self::set_sequence_token($ret->sequence_token);
			}
			return array($ret, $body);
		} else {
			throw new NXAPIError(NXAPIError::REQUEST_FAILED, "Failed to request extra data: ". $request->getErrorMessage());
		}
	}

	private static function sign($data) {
		if(self::$private_key == null) throw new NXAPIError(NXAPIError::MISSING_CONFIGURATION, "Private key required to get extra data");

		$signature = null;
		if(openssl_sign($data, $signature, self::$private_key)) {
			return base64_encode($signature);
		} else {
			throw new NXAPIError(NXAPIError::SIGN_FAILED, "Failed to sign data");
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
	const SEQUENCE_ERROR = 1;
	const SERVER_ERROR = 2;
	const GOT_NULL = 3;
	const REQUEST_FAILED = 4;
	const SIGN_FAILED = 5;
	const MISSING_CONFIGURATION = 6;

	public $type;
	public $body;

	public function __construct($type, $msg, $body = null) {
		parent::__construct($msg);
		$this->type = $type;
		$this->body = $body;
		if($this->body != null) {
			$this->message .= "\nServer response: " . $this->body;
		}
	}

	public function __toString() {
		return __CLASS__ . ": " . $this->getMessage();
	}
}
