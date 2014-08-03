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

use horaro\Library\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HomeController extends BaseController {
	public function indexAction(Request $request) {
		return $this->render('home/home.twig');
	}

	public function logoutAction(Request $request) {
		$session = $this->app['session'];
		$session->invalidate();

		return $this->redirect('/');
	}
}
