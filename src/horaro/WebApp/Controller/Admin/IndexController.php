<?php
/*
 * Copyright (c) 2014, Sgt. Kabukiman, https://bitbucket.org/sgt-kabukiman/
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Controller\Admin;

use horaro\WebApp\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;

class IndexController extends BaseController {
	public function dashboardAction(Request $request) {
		return $this->render('admin/dashboard.twig');
	}
}
