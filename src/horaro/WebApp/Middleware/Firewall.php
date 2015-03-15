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

use horaro\Library\Entity\User;
use horaro\WebApp\Application;
use horaro\WebApp\Exception as Ex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Firewall {
	protected $app;

	const REQUIRED_ROLE = 'middleware.firewall.required-role';

	public function __construct(Application $app) {
		$this->app = $app;
	}

	public function __invoke(Request $request, Application $app) {
		$user = null;

		if ($request->hasPreviousSession()) {
			$user = $this->peekIntoSession($request->getSession());
		}

		$requiredRole = $request->attributes->get(self::REQUIRED_ROLE);

		if ($requiredRole) {
			$this->checkUserRole($user, $requiredRole, $app);
		}

		$app['user'] = $user;
	}

	public function peekIntoSession(SessionInterface $session) {
		$userID = $session->get('horaro.user');

		if ($userID) {
			$user = $this->app['entitymanager']->getRepository('horaro\Library\Entity\User')->findOneById($userID);

			// check if the user has been disabled since the session was opened
			if ($user && $user->getRole() !== 'ROLE_GHOST') {
				// in the session, we note a hash of the user's password hash. We use this to
				// check if the password has been changed and kill the session in that case.
				$storedHash = $session->get('horaro.pwdhash');
				$actualHash = sha1($user->getPassword());

				if ($storedHash === $actualHash) {
					return $user;
				}
			}

			$session->invalidate();
		}

		return null;
	}

	public function checkUserRole(User $user = null, $requiredRole, Application $app) {
		switch ($requiredRole) {
			case 'ROLE_GHOST':
				if ($user) throw new Ex\TooAuthorizedException('You cannot do this while you are logged in.');
				break;

			case 'ROLE_USER':
				if (!$user) throw new Ex\UnauthorizedException('Forbidden.');
				break;

			case 'ROLE_ADMIN':
			case 'ROLE_OP':
				if (!$user) {
					throw new Ex\UnauthorizedException('Forbidden.');
				}

				if (!$app['rolemanager']->userHasRole($requiredRole, $user)) {
					throw new Ex\ForbiddenException('You need to carry the title of "User with '.$requiredRole.' role" to wander these realms.');
				}
		}
	}
}
