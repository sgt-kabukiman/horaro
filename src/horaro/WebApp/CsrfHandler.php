<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp;

use horaro\WebApp\Exception\BadCsrfTokenException;
use RandomLib\Generator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class CsrfHandler {
	protected $name;
	protected $gen;

	const SESSION_KEY = 'horaro.csrftoken';

	public function __construct($name, Generator $generator) {
		$this->name = $name;
		$this->gen  = $generator;
	}

	public function getParamName() {
		return $this->name;
	}

	public function initSession(Session $session) {
		$session->set(self::SESSION_KEY, $this->generateToken());
	}

	public function getToken(Session $session) {
		return $session->get(self::SESSION_KEY);
	}

	public function generateToken() {
		return $this->gen->generateString(64, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_');
	}

	public function checkToken(Request $request, Session $session, $throwUp = true) {
		$ref = $this->getToken($session);

		if ($ref === null) {
			throw new BadCsrfTokenException('Cannot check CSRF token because it has not yet been set.');
		}

		$name = $this->getParamName();
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

		if (!$valid && $throwUp) {
			throw new BadCsrfTokenException('The submitted CSRF token does not match.');
		}

		return $valid;
	}
}
