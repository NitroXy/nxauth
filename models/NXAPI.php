<?php

class NXAPI {
	public static function __callStatic($func, $arguments) {
		if(NXAuth::is_authenticated() && array_key_exists('sequence_token', $_SESSION)) {
			$options = isset($arguments[0]) ? $arguments[0] : array();

			for($try=0; $try < 2; ++$try) {
				$data = NXAuth::request_data($func, $options);
				if(!$data) {
					throw new NXAPIError("NXAPI: Got null");
				}
				if($data->status == "SEQERR") {
					trigger_error("NXAPI: Invalid sequence token, retrying", E_USER_NOTICE);
				} else break;
			}
			if($data->status == "OK") {
				return $data->data;
			} else {
				throw new NXAPIError($data->message);
			}
		} else if(NXAuth::is_authenticated()) {
			throw new NXAuthError("Called method on NXAPI when not authenticated with api key");
		} else {
			throw new NXAuthError("Called method on NXAPI when not user was not authenticated");
		}
	}
}

class NXAPIError extends Exception {
	public function __construct($msg) {
		parent::__construct($msg);
	}
}
