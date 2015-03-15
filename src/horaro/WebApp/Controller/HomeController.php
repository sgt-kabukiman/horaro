<?php
/*
 * Copyright (c) 2015, Sgt. Kabukiman, https://github.com/sgt-kabukiman
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
		$this->app['session']->set('navbar', 'regular');

		return $this->render('home/home.twig', [
			'isFull' => $this->exceedsMaxEvents($this->getCurrentUser())
		]);
	}
}
