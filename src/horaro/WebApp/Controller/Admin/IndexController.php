<?php
/*
 * Copyright (c) 2015, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;

class IndexController extends BaseController {
	public function dashboardAction(Request $request) {
		$this->app['session']->set('navbar', 'admin');

		return $this->render('admin/dashboard.twig');
	}
}
