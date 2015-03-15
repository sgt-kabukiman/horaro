<?php
/*
 * Copyright (c) 2015, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Exception;

class HttpException extends \Exception {
	protected $httpStatus;

	public function __construct($message, $httpStatus, $code = null, \Exception $previous = null) {
		parent::__construct($message, $code, $previous);

		$this->httpStatus = $httpStatus;
	}

	public function getHttpStatus() {
		return $this->httpStatus;
	}

	public function getFullStatus() {
		return ($this->getHttpStatus() * 1000) + $this->getCode();
	}
}
