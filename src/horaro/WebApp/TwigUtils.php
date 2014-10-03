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

	public function userHasRole($role, User $user = null) {
		$user = $user ?: $this->app['user'];

		if (!$user) {
			return false;
		}

		return $this->app['rolemanager']->userHasRole($role, $user);
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

	public function roleBadge($role) {
		$key        = strtolower(str_replace('ROLE_', '', $role));
		$badgeClass = 'label h-role h-role-'.$key;

		switch ($role) {
			case 'ROLE_OP':
				$badgeClass .= ' label-danger';
				$text        = 'Operator';
				break;

			case 'ROLE_ADMIN':
				$badgeClass .= ' label-warning';
				$text        = 'Administrator';
				break;

			case 'ROLE_USER':
				$badgeClass .= ' label-primary';
				$text        = 'Regular User';
				break;

			case 'ROLE_GHOST':
				$badgeClass .= ' label-default';
				$text        = 'Ghost';
				break;
		}

		return sprintf('<span class="%s">%s %s</span>', $badgeClass, $this->roleIcon($role), $text);
	}
}
