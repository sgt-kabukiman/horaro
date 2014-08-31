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

class I18N {
	protected $app;

	public function __construct(Application $app) {
		$this->app = $app;
	}

	public function initLanguage() {
		if ($this->app['user']) {
			$this->app['language'] = $this->app['user']->getLanguage();
		}
		else {
			$this->app['language'] = 'en_us';
		}
	}
}
