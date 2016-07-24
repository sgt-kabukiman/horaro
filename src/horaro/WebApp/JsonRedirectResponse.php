<?php
/*
 * Copyright (c) 2015, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp;

use Symfony\Component\HttpFoundation\JsonResponse;

class JsonRedirectResponse extends JsonResponse {
	public function __construct($url, $status = 302, $headers = array()) {
		parent::__construct([
			'links' => [
				['rel' => 'redirect', 'uri' => $url]
			]
		], $status, $headers);

		$this->headers->set('Location', $url);
	}
}
