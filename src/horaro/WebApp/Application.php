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

		$this['errorhandler'] = $this->share(function() {
			return new ErrorHandler($this);
		});

		$this['version'] = $this->share(function() {
			$filename = HORARO_ROOT.'/version';

			return file_exists($filename) ? trim(file_get_contents($filename)) : 'version N/A';
		});

		$this['csrf'] = $this->share(function() {
			$factory   = new \RandomLib\Factory();
			$generator = $factory->getMediumStrengthGenerator();
			$name      = $this['config']['csrf_token_name'];

			return new CsrfHandler($name, $generator);
		});

		$this->register(new TwigServiceProvider(), array(
			'twig.path' => HORARO_ROOT.'/views',
			'twig.options' => [
				'cache'       => HORARO_ROOT.'/tmp/twig',
				'auto_reload' => $this['config']['debug']
			]
		));

		$this->extend('twig', function($twig, $container) {
			$versions = json_decode(file_get_contents(HORARO_ROOT.'/tmp/assets.json'), true);
			$utils    = new TwigUtils($versions, $this);

			$twig->addGlobal('utils', $utils);
			$twig->addFilter(new \Twig_SimpleFilter('shorten', function($string, $maxlen) use ($utils) {
				return $utils->shorten($string, $maxlen);
			}));

			return $twig;
		});

		$this['controller.index']           = $this->share(function() { return new Controller\IndexController($this);          });
		$this['controller.frontend']        = $this->share(function() { return new Controller\FrontendController($this);       });
		$this['controller.home']            = $this->share(function() { return new Controller\HomeController($this);           });
		$this['controller.event']           = $this->share(function() { return new Controller\EventController($this);          });
		$this['controller.schedule']        = $this->share(function() { return new Controller\ScheduleController($this);       });
		$this['controller.schedule.item']   = $this->share(function() { return new Controller\ScheduleItemController($this);   });
		$this['controller.schedule.column'] = $this->share(function() { return new Controller\ScheduleColumnController($this); });
		$this['controller.profile']         = $this->share(function() { return new Controller\ProfileController($this);        });
		$this['controller.admin.index']     = $this->share(function() { return new Controller\Admin\IndexController($this);    });
		$this['controller.admin.user']      = $this->share(function() { return new Controller\Admin\UserController($this);     });
		$this['controller.admin.event']     = $this->share(function() { return new Controller\Admin\EventController($this);    });

		$this['validator.createaccount'] = $this->share(function() {
			$userRepo = $this['entitymanager']->getRepository('horaro\Library\Entity\User');

			return new Validator\CreateAccountValidator($userRepo);
		});

		$this['validator.event'] = $this->share(function() {
			$eventRepo = $this['entitymanager']->getRepository('horaro\Library\Entity\Event');

			return new Validator\EventValidator($eventRepo);
		});

		$this['validator.login'] = $this->share(function() {
			$userRepo = $this['entitymanager']->getRepository('horaro\Library\Entity\User');

			return new Validator\LoginValidator($userRepo);
		});

		$this['validator.profile'] = $this->share(function() {
			$config = $this['config'];

			return new Validator\ProfileValidator(array_keys($config['languages']), $config['default_languages']);
		});

		$this['validator.schedule'] = $this->share(function() {
			$scheduleRepo = $this['entitymanager']->getRepository('horaro\Library\Entity\Schedule');
			$config       = $this['config'];

			return new Validator\ScheduleValidator($scheduleRepo, array_keys($config['themes']), $config['default_schedule_theme']);
		});

		$this['validator.schedule.item'] = $this->share(function() {
			return new Validator\ScheduleItemValidator();
		});

		$this['validator.schedule.column'] = $this->share(function() {
			return new Validator\ScheduleColumnValidator();
		});

		$this['validator.admin.user'] = $this->share(function() {
			$userRepo = $this['entitymanager']->getRepository('horaro\Library\Entity\User');
			$config   = $this['config'];

			return new Validator\Admin\UserValidator($userRepo, $this['rolemanager'], array_keys($config['languages']));
		});
	}

	public function setupRouting() {
		$this->before('firewall:peekIntoSession');
		$this->before('i18n:initLanguage');

		$this->get   ('/',           'controller.index:welcomeAction');
		$this->get   ('/-/login',    'controller.index:loginFormAction')->before('firewall:requireAnonymous');
		$this->post  ('/-/login',    'controller.index:loginAction')->before('firewall:requireAnonymous');
		$this->post  ('/-/logout',   'controller.index:logoutAction')->before('firewall:requireUser');
		$this->get   ('/-/register', 'controller.index:registerFormAction')->before('firewall:requireAnonymous');
		$this->post  ('/-/register', 'controller.index:registerAction')->before('firewall:requireAnonymous');
		$this->get   ('/-/licenses', 'controller.index:licensesAction');

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
		$this->get   ('/-/schedules/{schedule}/export',  'controller.schedule:exportAction')->before('firewall:requireUser');

		$this->post  ('/-/schedules/{schedule}/items',        'controller.schedule.item:createAction')->before('firewall:requireUser');
		$this->post  ('/-/schedules/{schedule}/items/move',   'controller.schedule.item:moveAction')->before('firewall:requireUser');
		$this->patch ('/-/schedules/{schedule}/items/{item}', 'controller.schedule.item:patchAction')->before('firewall:requireUser');
		$this->delete('/-/schedules/{schedule}/items/{item}', 'controller.schedule.item:deleteAction')->before('firewall:requireUser');

		$this->get   ('/-/schedules/{schedule}/columns/edit',     'controller.schedule.column:editAction')->before('firewall:requireUser');
		$this->post  ('/-/schedules/{schedule}/columns',          'controller.schedule.column:createAction')->before('firewall:requireUser');
		$this->post  ('/-/schedules/{schedule}/columns/move',     'controller.schedule.column:moveAction')->before('firewall:requireUser');
		$this->put   ('/-/schedules/{schedule}/columns/{column}', 'controller.schedule.column:updateAction')->before('firewall:requireUser');
		$this->delete('/-/schedules/{schedule}/columns/{column}', 'controller.schedule.column:deleteAction')->before('firewall:requireUser');

		$this->get   ('/-/profile',          'controller.profile:editAction')->before('firewall:requireUser');
		$this->put   ('/-/profile',          'controller.profile:updateAction')->before('firewall:requireUser');
		$this->put   ('/-/profile/password', 'controller.profile:updatePasswordAction')->before('firewall:requireUser');

		$this->get   ('/-/admin', 'controller.admin.index:dashboardAction')->before('firewall:requireAdmin');

		$this->get   ('/-/admin/users',                 'controller.admin.user:indexAction')->before('firewall:requireAdmin');
		$this->get   ('/-/admin/users/{user}/edit',     'controller.admin.user:editAction')->before('firewall:requireAdmin');
		$this->put   ('/-/admin/users/{user}',          'controller.admin.user:updateAction')->before('firewall:requireAdmin');
		$this->put   ('/-/admin/users/{user}/password', 'controller.admin.user:updatePasswordAction')->before('firewall:requireAdmin');

		$this->get   ('/-/admin/events',                'controller.admin.event:indexAction')->before('firewall:requireAdmin');
		$this->get   ('/-/admin/events/{event}/edit',   'controller.admin.event:editAction')->before('firewall:requireAdmin');
		$this->put   ('/-/admin/events/{event}',        'controller.admin.event:updateAction')->before('firewall:requireAdmin');
		$this->get   ('/-/admin/events/{event}/delete', 'controller.admin.event:confirmationAction')->before('firewall:requireAdmin');
		$this->delete('/-/admin/events/{event}',        'controller.admin.event:deleteAction')->before('firewall:requireAdmin');

		$this->get   ('/{event}',                      'controller.frontend:eventAction');
		$this->get   ('/{event}/',                     'controller.frontend:eventAction');
		$this->get   ('/{event}/{schedule}.{format}',  'controller.frontend:scheduleExportAction')->assert('format', '(json|xml|csv|ical)');
		$this->get   ('/{event}/{schedule}',           'controller.frontend:scheduleAction');
		$this->get   ('/{event}/{schedule}/',          'controller.frontend:scheduleAction');
		$this->get   ('/{event}/{schedule}/ical-feed', 'controller.frontend:icalFaqAction');

		$this['errorhandler']->setupMiddleware($this['debug']);
	}
}
