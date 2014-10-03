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
}
