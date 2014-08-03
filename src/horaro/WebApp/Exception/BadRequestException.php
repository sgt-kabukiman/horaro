<?php
/*
 * Copyright (c) 2014, Sgt. Kabukiman, https://bitbucket.org/sgt-kabukiman/
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Exception;

class BadRequestException extends HttpException {
	public function __construct($message, $code = null, \Exception $previous = null) {
		parent::__construct($message, null, $code, $previous);
	}

	public function getHttpStatus() {
		return 400;
	}
}
