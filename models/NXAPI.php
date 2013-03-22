<?php

class NXAPI {
	public static function __callStatic($func, $arguments) {
		if(NXAuth::is_authenticated() && isset($_SESSION['sequence_token'])) {
			$option = isset($arguments[0]) ? $arguments[0] : array();

			for($try=0; $try < 2; ++$try) {
				$data = NXAuth::request_data($func, $options);
				if($data['status'] == "SEQERR") {
					trigger_error("NXAPI: Invalid sequence token, retrying", E_USER_NOTICE);
				} else break;
			}
			if($data['status'] == "OK") {
				return $data['data']
			} else {
				throw new NXAPIError($data['message']);
			}
		} else {
			throw new NXAuthError("Called method on NXAPI when not authenticated with api key");
		}
	}
}

class NXAPIError extends Exception {
	public function __construct($msg) {
		parent::__construct($msg);
	}
}
