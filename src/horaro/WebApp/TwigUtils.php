<?php
/*
 * Copyright (c) 2015, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp;

use horaro\Entity\User;

class TwigUtils {
	protected $versions = [];
	protected $app;

	public function __construct(array $assetVersions, Application $app) {
		$this->versions = $assetVersions;
		$this->app      = $app;
	}

	public function asset($path) {
		return isset($this->versions[$path]) ? $this->versions[$path] : $path;
	}

	public function shorten($string, $maxlen) {
		if (mb_strlen($string) <= $maxlen) {
			return $string;
		}

		return mb_substr($string, 0, $maxlen).'…';
	}

	public function getLicenseMarkup($path) {
		$file = HORARO_ROOT.'/'.$path;

		if (!file_exists($file)) {
			return '<p class="text-error">License file ('.htmlspecialchars($file, ENT_QUOTES, 'UTF-8').' not found.</p>';
		}

		$content = file_get_contents($file);

		return '<pre>'.htmlspecialchars($content, ENT_QUOTES, 'UTF-8').'</pre>';
	}

	public function userIsAdmin(User $user = null) {
		$user = $user ?: $this->app['user'];

		if (!$user) {
			return false;
		}

		return $this->app['rolemanager']->userIsAdmin($user);
	}

	public function userIsOp(User $user = null) {
		$user = $user ?: $this->app['user'];

		if (!$user) {
			return false;
		}

		return $this->app['rolemanager']->userIsOp($user);
	}

	public function userHasRole($role, User $user = null) {
		$user = $user ?: $this->app['user'];

		if (!$user) {
			return false;
		}

		return $this->app['rolemanager']->userHasRole($role, $user);
	}

	public function userHasAdministrativeAccess($resource, User $user = null) {
		$user = $user ?: $this->app['user'];

		if (!$user) {
			return false;
		}

		return $this->app['rolemanager']->hasAdministrativeAccess($user, $resource);
	}

	public function formValue(array $result = null, $key, $default = null) {
		return isset($result[$key]) ? $result[$key]['filtered'] : $default;
	}

	public function formClass(array $result = null, $key) {
		return empty($result[$key]['errors']) ? '' : ' has-error';
	}

	public function roleIcon($role) {
		$classes = [
			'ROLE_OP'    => 'fa-android',
			'ROLE_ADMIN' => 'fa-user-md',
			'ROLE_USER'  => 'fa-user',
			'ROLE_GHOST' => 'fa-ban'
		];
		$cls = isset($classes[$role]) ? $classes[$role] : 'fa-question';

		return sprintf('<i class="fa %s"></i>', $cls);
	}

	public function roleClass($role) {
		$classes = [
			'ROLE_OP'    => 'danger',
			'ROLE_ADMIN' => 'warning',
			'ROLE_USER'  => 'primary',
			'ROLE_GHOST' => 'default'
		];

		return isset($classes[$role]) ? $classes[$role] : 'primary';
	}

	public function roleName($role) {
		$names = [
			'ROLE_OP'    => 'Operator',
			'ROLE_ADMIN' => 'Administrator',
			'ROLE_USER'  => 'Regular User',
			'ROLE_GHOST' => 'Ghost'
		];

		return isset($names[$role]) ? $names[$role] : $role;
	}

	public function roleBadge($role) {
		$key = strtolower(str_replace('ROLE_', '', $role));

		return sprintf(
			'<span class="label h-role h-role-%s label-%s">%s %s</span>',
			$key, $this->roleClass($role), $this->roleIcon($role), $this->roleName($role)
		);
	}
}
