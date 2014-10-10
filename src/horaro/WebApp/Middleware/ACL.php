<?php
/*
 * Copyright (c) 2014, Sgt. Kabukiman, https://bitbucket.org/sgt-kabukiman/
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Middleware;

use horaro\Library\Entity\User;
use horaro\Library\RoleManager;
use horaro\WebApp\Application;
use horaro\WebApp\Exception as Ex;
use Symfony\Component\HttpFoundation\Request;

class ACL {
	protected $rm;

	const ADMIN_MODE = 'middleware.acl.admin-mode';

	public function __construct(RoleManager $rm) {
		$this->rm = $rm;
	}

	public function __invoke(Request $request, Application $app) {
		$user      = $app['user'];
		$attr      = $request->attributes;
		$params    = $attr->get('_route_params');
		$adminMode = !empty($params[self::ADMIN_MODE]);

		foreach (array_keys($params) as $resourceKey) {
			// if the parameter is encoded, decode it
			if (substr($resourceKey, -2) === '_e') {
				$resourceKey = substr($resourceKey, 0, -2);
			}

			if (in_array($resourceKey, ['event', 'schedule', 'schedule_item', 'schedule_column', 'user'], true)) {
				$id       = $attr->get($resourceKey.':input');
				$resource = $attr->get($resourceKey);

				if ($resource) {
					$this->checkAccess($resource, $adminMode, $user, $id, $resourceKey);
				}
			}
		}
	}

	protected function checkAccess($resource, $adminMode, User $user, $id, $resourceKey) {
		if ($adminMode) {
			$allowed = $this->rm->hasAdministrativeAccess($user, $resource);
		}
		else {
			$allowed = $this->rm->hasRegularAccess($user, $resource);
		}

		if (!$allowed) {
			$name = str_replace('_', ' ', $resourceKey);
			throw new Ex\NotFoundException(ucfirst($name).' '.$id.' could not be found.');
		}
	}
}
