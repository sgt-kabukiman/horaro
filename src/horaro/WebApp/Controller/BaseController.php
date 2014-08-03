<?php
/*
 * Copyright (c) 2014, Sgt. Kabukiman, https://bitbucket.org/sgt-kabukiman/
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Controller;

use horaro\WebApp\Application;
use Symfony\Component\HttpFoundation\RedirectResponse;

class BaseController {
	protected $app;

	public function __construct(Application $app) {
		$this->app = $app;
	}

	protected function redirect($uri, $status = 302) {
		return new RedirectResponse($uri, $status);
	}

	protected function render($template, array $params = []) {
		return $this->app['twig']->render($template, $params);
	}

	protected function getEntityManager() {
		return $this->app['entitymanager'];
	}

	protected function getRepository($className) {
		return $this->getEntityManager()->getRepository('horaro\Library\Entity\\'.$className);
	}

	public function getCurrentUser() {
		$userID = $this->app['session']->get('horaro.user');

		if (!$userID) {
			throw new UnauthorizedException('Forbidden.');
		}

		$user = $this->getRepository('User')->findById($userID);

		if (!$user) {
			throw new UnauthorizedException('Forbidden.');
		}

		return $user;
	}
}
