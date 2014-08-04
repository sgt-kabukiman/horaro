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
use Silex\Provider\TwigServiceProvider;

class Application extends BaseApplication {
	public function __construct(array $values = []) {
		parent::__construct($values);

		$this->register(new TwigServiceProvider(), array(
			'twig.path' => HORARO_ROOT.'/views',
			'twig.options' => [
				'cache'       => HORARO_ROOT.'/tmp/twig',
				'auto_reload' => true
			]
		));

		$this->setupServices();
		$this->setupRouting();
	}

	public function setupServices() {
		parent::setupServices();

		$this['user'] = null;

		$this['firewall'] = $this->share(function() {
			return new Firewall($this);
		});

		$this['controller.index'] = $this->share(function() {
			return new Controller\IndexController($this);
		});

		$this['controller.home'] = $this->share(function() {
			return new Controller\HomeController($this);
		});

		$this['controller.event'] = $this->share(function() {
			return new Controller\EventController($this);
		});
	}

	public function setupRouting() {
		$this->before('firewall:peekIntoSession');

		$this->get   ('/',           'controller.index:indexAction');
		$this->get   ('/-/login',    'controller.index:loginFormAction')->before('firewall:requireAnonymous');
		$this->post  ('/-/login',    'controller.index:loginAction')->before('firewall:requireAnonymous');
		$this->get   ('/-/register', 'controller.index:registerFormAction')->before('firewall:requireAnonymous');
		$this->post  ('/-/register', 'controller.index:registerAction')->before('firewall:requireAnonymous');

		$this->get   ('/-/home',   'controller.home:indexAction')->before('firewall:requireUser');
		$this->get   ('/-/logout', 'controller.home:logoutAction')->before('firewall:requireUser'); // TODO: This should be POST

		$this->get   ('/-/events/new',         'controller.event:newAction')->before('firewall:requireUser');
		$this->post  ('/-/events',             'controller.event:createAction')->before('firewall:requireUser');
		$this->get   ('/-/events/{id}',        'controller.event:detailAction')->before('firewall:requireUser');
		$this->get   ('/-/events/{id}/edit',   'controller.event:editAction')->before('firewall:requireUser');
		$this->put   ('/-/events/{id}',        'controller.event:updateAction')->before('firewall:requireUser');
		$this->get   ('/-/events/{id}/delete', 'controller.event:confirmationAction')->before('firewall:requireUser');
		$this->delete('/-/events/{id}',        'controller.event:deleteAction')->before('firewall:requireUser');

		$this->error('firewall:handleAuthErrors');
		$this->error('firewall:handleReverseAuthErrors');
	}
}
