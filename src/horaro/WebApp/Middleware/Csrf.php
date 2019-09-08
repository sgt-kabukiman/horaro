<?php
/*
 * Copyright (c) 2019, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Middleware;

use horaro\WebApp\CsrfHandler;
use horaro\WebApp\Exception as Ex;
use Symfony\Component\HttpFoundation\Request;

class Csrf {
	protected $handler;

	const REQUIRE_NO_CSRF_TOKEN = 'middleware.csrf.tokenless';

	public function __construct(CsrfHandler $handler) {
		$this->handler = $handler;
	}

	public function __invoke(Request $request) {
		if ($request->attributes->get(self::REQUIRE_NO_CSRF_TOKEN)) {
			return;
		}

		if ($request->isMethodSafe(false)) {
			return;
		}

		$handler = $this->handler;
		$session = $request->getSession();
		$ref     = $handler->getToken($session);

		if ($ref === null) {
			throw new Ex\BadCsrfTokenException('Cannot check CSRF token because it has not yet been set.');
		}

		$name = $handler->getParamName();
		$type = $request->getContentType();

		if ($type === 'json') {
			$content = $request->getContent();
			$payload = @json_decode($content, true);
			$error   = json_last_error();

			if ($error !== JSON_ERROR_NONE) {
				throw new Ex\BadRequestException('Request does not contain valid JSON.', 900);
			}

			$token = (isset($payload[$name]) && is_string($payload[$name])) ? $payload[$name] : null;
		}
		else {
			$token = $request->request->get($name);
		}

		$valid = is_string($token) && $token === $ref;

		if (!$valid) {
			throw new Ex\BadCsrfTokenException('The submitted CSRF token does not match.');
		}
	}
}
