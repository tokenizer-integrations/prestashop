<?php

if (!defined('_PS_VERSION_'))
  exit;

class Tokenizer extends Module {
	public function __construct() {
		$this->name = "tokenizer";
	    $this->tab = 'other';
	    $this->version = '1.0';
	    $this->author = 'Pawel Buchowski';
		parent::__construct();
		$this->displayName = $this->l('Tokenizer authenticator');
    	$this->description = $this->l('This module does nothing yet.');
	}
}
?>