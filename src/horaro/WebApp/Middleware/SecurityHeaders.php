<?php
/*
 * Copyright (c) 2015, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders {
	protected $hstsMaxAge;

	public function __construct($hstsMaxAge) {
		$this->hstsMaxAge = $hstsMaxAge;
	}

	public function __invoke(Request $request, Response $response) {
		$response->headers->set('X-Content-Type-Options', 'nosniff');
		$response->headers->set('X-XSS-Protection', '1; mode=block');

		if ($this->hstsMaxAge !== null) {
			$response->headers->set('Strict-Transport-Security', 'max-age='.$this->hstsMaxAge);
		}
	}
}
