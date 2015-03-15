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

use RandomLib\Generator;
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
}
