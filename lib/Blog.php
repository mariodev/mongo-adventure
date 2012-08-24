<?php

require 'lib/Slim/Slim/Slim.php';

class Blog extends Slim {
	public function getUri() {
		return $this->router();
	}
}