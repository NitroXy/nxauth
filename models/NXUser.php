<?php

class NXUser {

	private $attr;

	public function __construct() {
		$this->attr = phpCAS::getAttributes();
		$this->attr['username'] = phpCAS::getUser();
		$this->id = $this->attr['user_id'];
		unset($this->attr['sequence_token']);
	}

	public function __get($attr) {
		if(array_key_exists($attr, $this->attr)) {
			return $this->attr[$attr];
		} else {
			return parent::__get($attr);
		}
	}

	public function get_attr() {
		return $this->attr;
	}
}
