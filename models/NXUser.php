<?php

class NXUser {

	private $attr;

	public function __construct() {
		$this->attr = phpCAS::getAttributes();
		$this->ticket = $_SESSION['PHPSESSID'];
		$this->attr['username'] = phpCAS::getUser();
	}

	public __get($attr) {
		if(array_key_exists($attr, $this->attr)) {
			return $this->attr[$attr];
		} else {
			return parent::__get($attr);
		}
	}
}
