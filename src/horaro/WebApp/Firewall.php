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
use horaro\WebApp\Exception\ForbiddenException;

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

	public function requireAdmin() {
		$this->requireUser();

		$user = $this->app['user'];
		$rm   = $this->app['rolemanager'];

		if (!$rm->userIsAdmin($user)) {
			throw new ForbiddenException('You need to be an Administrator to wander these realms.');
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
			$session = $this->app['session'];
			$userID  = $session->get('horaro.user');

			if ($userID) {
				$user = $this->app['entitymanager']->getRepository('horaro\Library\Entity\User')->findOneById($userID);

				// check if the user has been disabled since the session was opened
				if ($user && $user->getRole() !== 'ROLE_GHOST') {
					// in the session, we note a hash of the user's password hash. We use this to
					// check if the password has been changed and kill the session in that case.
					$storedHash = $session->get('horaro.pwdhash');
					$actualHash = sha1($user->getPassword());

					if ($storedHash !== $actualHash) {
						$session->invalidate();
					}
					else {
						$this->app['user'] = $user;
					}
				}
				else {
					$session->invalidate();
				}
			}
		}
	}
}
