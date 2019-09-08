<?php
/*
 * Copyright (c) 2019, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Controller\Admin;

use horaro\WebApp\Controller\BaseController as RegularBaseController;
use Symfony\Component\HttpFoundation\Request;

class BaseController extends RegularBaseController {
	protected function hasResourceAccess($resource) {
		return $this->app['rolemanager']->hasAdministrativeAccess($this->getCurrentUser(), $resource);
	}

	protected function getRequestedUser(Request $request) {
		return $request->attributes->get('user');
	}
}
