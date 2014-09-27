<?php
/*
 * Copyright (c) 2014, Sgt. Kabukiman, https://bitbucket.org/sgt-kabukiman/
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp;

use horaro\WebApp\Exception\TooAuthorizedException;
use horaro\WebApp\Exception\UnauthorizedException;

class Firewall {
	protected $app;

	public function __construct(Application $app) {
		$this->app = $app;
	}

	public function requireUser() {
		if (!$this->app['user']) {
			throw new UnauthorizedException('Forbidden.');
		}
	}

	public function requireAnonymous() {
		if ($this->app['user']) {
			throw new TooAuthorizedException('You cannot do this while you are logged in.');
		}
	}

	public function peekIntoSession($request) {
		$name    = $this->app['session']->getName();
		$cookies = $request->cookies;

		if ($cookies->has($name)) {
			$userID = $this->app['session']->get('horaro.user');

			if ($userID) {
				$user = $this->app['entitymanager']->getRepository('horaro\Library\Entity\User')->findOneById($userID);

				if ($user) {
					$this->app['user'] = $user;
				}
			}
		}
	}
}
