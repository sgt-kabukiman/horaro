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

		$this['twig']->addGlobal('utils', new TwigUtils());

		$this->setupServices();
		$this->setupRouting();
	}

	public function setupServices() {
		parent::setupServices();

		$this['user'] = null;

		$this['firewall'] = $this->share(function() {
			return new Firewall($this);
		});

		$this['i18n'] = $this->share(function() {
			return new I18N($this);
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

		$this['controller.schedule'] = $this->share(function() {
			return new Controller\ScheduleController($this);
		});

		$this['controller.schedule.item'] = $this->share(function() {
			return new Controller\ScheduleItemController($this);
		});

		$this['controller.schedule.column'] = $this->share(function() {
			return new Controller\ScheduleColumnController($this);
		});

		$this['controller.schedule.export'] = $this->share(function() {
			return new Controller\ScheduleExportController($this);
		});

		$this['controller.profile'] = $this->share(function() {
			return new Controller\ProfileController($this);
		});
	}

	public function setupRouting() {
		$this->before('firewall:peekIntoSession');
		$this->before('i18n:initLanguage');

		$this->get   ('/',           'controller.index:welcomeAction');
		$this->get   ('/-/login',    'controller.index:loginFormAction')->before('firewall:requireAnonymous');
		$this->post  ('/-/login',    'controller.index:loginAction')->before('firewall:requireAnonymous');
		$this->get   ('/-/logout',   'controller.index:logoutAction')->before('firewall:requireUser'); // TODO: This should be POST
		$this->get   ('/-/register', 'controller.index:registerFormAction')->before('firewall:requireAnonymous');
		$this->post  ('/-/register', 'controller.index:registerAction')->before('firewall:requireAnonymous');

		$this->get   ('/-/home', 'controller.home:indexAction')->before('firewall:requireUser');

		$this->get   ('/-/events/new',            'controller.event:newAction')->before('firewall:requireUser');
		$this->post  ('/-/events',                'controller.event:createAction')->before('firewall:requireUser');
		$this->get   ('/-/events/{event}',        'controller.event:detailAction')->before('firewall:requireUser');
		$this->get   ('/-/events/{event}/edit',   'controller.event:editAction')->before('firewall:requireUser');
		$this->put   ('/-/events/{event}',        'controller.event:updateAction')->before('firewall:requireUser');
		$this->get   ('/-/events/{event}/delete', 'controller.event:confirmationAction')->before('firewall:requireUser');
		$this->delete('/-/events/{event}',        'controller.event:deleteAction')->before('firewall:requireUser');

		$this->get   ('/-/events/{event}/schedules/new', 'controller.schedule:newAction')->before('firewall:requireUser');
		$this->post  ('/-/events/{event}/schedules',     'controller.schedule:createAction')->before('firewall:requireUser');
		$this->get   ('/-/schedules/{schedule}',         'controller.schedule:detailAction')->before('firewall:requireUser');
		$this->get   ('/-/schedules/{schedule}/edit',    'controller.schedule:editAction')->before('firewall:requireUser');
		$this->put   ('/-/schedules/{schedule}',         'controller.schedule:updateAction')->before('firewall:requireUser');
		$this->get   ('/-/schedules/{schedule}/delete',  'controller.schedule:confirmationAction')->before('firewall:requireUser');
		$this->delete('/-/schedules/{schedule}',         'controller.schedule:deleteAction')->before('firewall:requireUser');

		$this->get   ('/-/schedules/{schedule}/export', 'controller.schedule.export:jsonAction')->before('firewall:requireUser');

		$this->post  ('/-/schedules/{schedule}/items',        'controller.schedule.item:createAction')->before('firewall:requireUser');
		$this->post  ('/-/schedules/{schedule}/items/move',   'controller.schedule.item:moveAction')->before('firewall:requireUser');
		$this->patch ('/-/schedules/{schedule}/items/{item}', 'controller.schedule.item:patchAction')->before('firewall:requireUser');
		$this->delete('/-/schedules/{schedule}/items/{item}', 'controller.schedule.item:deleteAction')->before('firewall:requireUser');

		$this->get   ('/-/schedules/{schedule}/columns/edit',     'controller.schedule.column:editAction')->before('firewall:requireUser');
		$this->post  ('/-/schedules/{schedule}/columns',          'controller.schedule.column:createAction')->before('firewall:requireUser');
		$this->post  ('/-/schedules/{schedule}/columns/move',     'controller.schedule.column:moveAction')->before('firewall:requireUser');
		$this->put   ('/-/schedules/{schedule}/columns/{column}', 'controller.schedule.column:updateAction')->before('firewall:requireUser');
		$this->delete('/-/schedules/{schedule}/columns/{column}', 'controller.schedule.column:deleteAction')->before('firewall:requireUser');

		$this->get   ('/-/profile', 'controller.profile:editAction')->before('firewall:requireUser');
		$this->put   ('/-/profile', 'controller.profile:updateAction')->before('firewall:requireUser');

		$this->error('firewall:handleAuthErrors');
		$this->error('firewall:handleReverseAuthErrors');
	}
}
