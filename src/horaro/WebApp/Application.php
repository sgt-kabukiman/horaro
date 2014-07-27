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

use horaro\Library\BaseApplication;
use Symfony\Component\Translation\Loader\YamlFileLoader;

class Application extends BaseApplication {
	public function __construct(array $values = []) {
		parent::__construct($values);

		$this->setupServices();
		$this->setupRouting();
	}

	public function setupServices() {
		parent::setupServices();

		$app = $this;

		$this['app.controller.index'] = $this->share(function() use ($app) {
			return new Controller\IndexController($app);
		});
	}

	public function setupRouting() {
		$this->get('/', 'app.controller.index:indexAction');
	}
}
