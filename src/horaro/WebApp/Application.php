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
use horaro\Library\ObscurityCodec;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Silex\Provider\TwigServiceProvider;

class Application extends BaseApplication {
	public function __construct(array $values = []) {
		parent::__construct($values);

		$this->setupServices();

		// Connect to DB and fetch runtime configuration, so the routing setup can properly build
		// middleware instances without having to worry whether stuff like the CSRF token name are
		// already known.
		$this['runtime-config']->init();

		$this->setupRouting();
	}

	public function setupServices() {
		parent::setupServices();

		$this['user'] = null;

		$this['i18n'] = $this->share(function() {
			return new I18N($this);
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

		$this['csp'] = $this->share(function() {
			return new ContentSecurityPolicy();
		});

		$this['resource-resolver'] = $this->share(function() {
			return new ResourceResolver($this['entitymanager'], $this['obscurity-codec']);
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

			$twig->addFilter(new \Twig_SimpleFilter('obscurify', function($id, $entityType) use ($utils) {
				return $this['obscurity-codec']->encode($id, $entityType);
			}));

			return $twig;
		});

		$this['middleware.firewall'] = $this->share(function() {
			return new Middleware\Firewall($this);
		});

		$this['middleware.resolver'] = $this->share(function() {
			return new Middleware\Resolver($this['resource-resolver']);
		});

		$this['middleware.errorhandler'] = $this->share(function() {
			return new Middleware\ErrorHandler($this['twig']);
		});

		$this['middleware.csrf'] = $this->share(function() {
			return new Middleware\Csrf($this['csrf']);
		});

		$this['middleware.acl'] = $this->share(function() {
			return new Middleware\ACL($this['rolemanager']);
		});

		$this['middleware.csp'] = $this->share(function() {
			return new Middleware\CSP($this['csp']);
		});

		$this['controller.index']                  = $this->share(function() { return new Controller\IndexController($this);                  });
		$this['controller.frontend']               = $this->share(function() { return new Controller\FrontendController($this);               });
		$this['controller.home']                   = $this->share(function() { return new Controller\HomeController($this);                   });
		$this['controller.event']                  = $this->share(function() { return new Controller\EventController($this);                  });
		$this['controller.schedule']               = $this->share(function() { return new Controller\ScheduleController($this);               });
		$this['controller.schedule.item']          = $this->share(function() { return new Controller\ScheduleItemController($this);           });
		$this['controller.schedule.column']        = $this->share(function() { return new Controller\ScheduleColumnController($this);         });
		$this['controller.profile']                = $this->share(function() { return new Controller\ProfileController($this);                });
		$this['controller.admin.index']            = $this->share(function() { return new Controller\Admin\IndexController($this);            });
		$this['controller.admin.user']             = $this->share(function() { return new Controller\Admin\UserController($this);             });
		$this['controller.admin.event']            = $this->share(function() { return new Controller\Admin\EventController($this);            });
		$this['controller.admin.schedule']         = $this->share(function() { return new Controller\Admin\ScheduleController($this);         });
		$this['controller.admin.utils']            = $this->share(function() { return new Controller\Admin\Utils\BaseController($this);       });
		$this['controller.admin.utils.config']     = $this->share(function() { return new Controller\Admin\Utils\ConfigController($this);     });
		$this['controller.admin.utils.serverinfo'] = $this->share(function() { return new Controller\Admin\Utils\ServerInfoController($this); });

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
			return new Validator\ScheduleItemValidator($this['obscurity-codec']);
		});

		$this['validator.schedule.column'] = $this->share(function() {
			return new Validator\ScheduleColumnValidator();
		});

		$this['validator.admin.user'] = $this->share(function() {
			$userRepo = $this['entitymanager']->getRepository('horaro\Library\Entity\User');
			$config   = $this['config'];

			return new Validator\Admin\UserValidator($userRepo, $this['rolemanager'], array_keys($config['languages']));
		});

		$this['validator.admin.event'] = $this->share(function() {
			$eventRepo = $this['entitymanager']->getRepository('horaro\Library\Entity\Event');

			return new Validator\Admin\EventValidator($eventRepo);
		});

		$this['validator.admin.schedule'] = $this->share(function() {
			$scheduleRepo = $this['entitymanager']->getRepository('horaro\Library\Entity\Schedule');
			$config       = $this['config'];

			return new Validator\Admin\ScheduleValidator($scheduleRepo, array_keys($config['themes']), $config['default_schedule_theme']);
		});

		$this['validator.admin.utils.config'] = $this->share(function() {
			$config = $this['config'];
			$languages       = array_keys($config['languages']);
			$defaultLanguage = $config['default_language'];
			$themes          = array_keys($config['themes']);
			$defaultTheme    = $config['default_schedule_theme'];

			return new Validator\Admin\Utils\ConfigValidator($languages, $defaultLanguage, $themes, $defaultTheme);
		});
	}

	public function setupRouting() {
		$this->before($this['middleware.errorhandler']);
		$this->before($this['middleware.csrf']);
		$this->before($this['middleware.firewall']);
		$this->before($this['middleware.resolver']);
		$this->before($this['middleware.acl']);
		$this->before('i18n:initLanguage');

		$this->before('middleware.csp:before');
		$this->after('middleware.csp:after');

		///////////////////////////////////////////////////////////////////////////////////////////
		// general routes

		$this->route('GET',   '/',           'index:welcome');
		$this->route('GET',   '/-/licenses', 'index:licenses');
		$this->route('GET',   '/-/login',    'index:loginForm',    'ghost');
		$this->route('POST',  '/-/login',    'index:login',        'ghost', true);
		$this->route('GET',   '/-/register', 'index:registerForm', 'ghost');
		$this->route('POST',  '/-/register', 'index:register',     'ghost', true);
		$this->route('POST',  '/-/logout',   'index:logout',       'user');

		///////////////////////////////////////////////////////////////////////////////////////////
		// user backend

		$this->route('GET',    '/-/home',                                               'home:index',             'user');

		$this->route('GET',    '/-/events/new',                                         'event:new',              'user');
		$this->route('POST',   '/-/events',                                             'event:create',           'user');
		$this->route('GET',    '/-/events/{event_e}',                                   'event:detail',           'user');
		$this->route('GET',    '/-/events/{event_e}/edit',                              'event:edit',             'user');
		$this->route('PUT',    '/-/events/{event_e}',                                   'event:update',           'user');
		$this->route('GET',    '/-/events/{event_e}/delete',                            'event:confirmation',     'user');
		$this->route('DELETE', '/-/events/{event_e}',                                   'event:delete',           'user');

		$this->route('GET',    '/-/events/{event_e}/schedules/new',                     'schedule:new',           'user');
		$this->route('POST',   '/-/events/{event_e}/schedules',                         'schedule:create',        'user');
		$this->route('GET',    '/-/schedules/{schedule_e}',                             'schedule:detail',        'user');
		$this->route('GET',    '/-/schedules/{schedule_e}/edit',                        'schedule:edit',          'user');
		$this->route('PUT',    '/-/schedules/{schedule_e}',                             'schedule:update',        'user');
		$this->route('GET',    '/-/schedules/{schedule_e}/delete',                      'schedule:confirmation',  'user');
		$this->route('DELETE', '/-/schedules/{schedule_e}',                             'schedule:delete',        'user');
		$this->route('GET',    '/-/schedules/{schedule_e}/export',                      'schedule:export',        'user');

		$this->route('POST',   '/-/schedules/{schedule_e}/items',                       'schedule.item:create',   'user');
		$this->route('POST',   '/-/schedules/{schedule_e}/items/move',                  'schedule.item:move',     'user');
		$this->route('PATCH',  '/-/schedules/{schedule_e}/items/{schedule_item_e}',     'schedule.item:patch',    'user');
		$this->route('DELETE', '/-/schedules/{schedule_e}/items/{schedule_item_e}',     'schedule.item:delete',   'user');

		$this->route('GET',    '/-/schedules/{schedule_e}/columns/edit',                'schedule.column:edit',   'user');
		$this->route('POST',   '/-/schedules/{schedule_e}/columns',                     'schedule.column:create', 'user');
		$this->route('POST',   '/-/schedules/{schedule_e}/columns/move',                'schedule.column:move',   'user');
		$this->route('PUT',    '/-/schedules/{schedule_e}/columns/{schedule_column_e}', 'schedule.column:update', 'user');
		$this->route('DELETE', '/-/schedules/{schedule_e}/columns/{schedule_column_e}', 'schedule.column:delete', 'user');

		$this->route('GET',    '/-/profile',                                            'profile:edit',           'user');
		$this->route('PUT',    '/-/profile',                                            'profile:update',         'user');
		$this->route('PUT',    '/-/profile/password',                                   'profile:updatePassword', 'user');

		///////////////////////////////////////////////////////////////////////////////////////////
		// admin backend

		$this->route('GET',    '/-/admin',                             'admin.index:dashboard',       'admin');

		$this->route('GET',    '/-/admin/users',                       'admin.user:index',            'admin');
		$this->route('GET',    '/-/admin/users/{user}/edit',           'admin.user:edit',             'admin');
		$this->route('PUT',    '/-/admin/users/{user}',                'admin.user:update',           'admin');
		$this->route('PUT',    '/-/admin/users/{user}/password',       'admin.user:updatePassword',   'admin');

		$this->route('GET',    '/-/admin/events',                      'admin.event:index',           'admin');
		$this->route('GET',    '/-/admin/events/{event}/edit',         'admin.event:edit',            'admin');
		$this->route('PUT',    '/-/admin/events/{event}',              'admin.event:update',          'admin');
		$this->route('GET',    '/-/admin/events/{event}/delete',       'admin.event:confirmation',    'admin');
		$this->route('DELETE', '/-/admin/events/{event}',              'admin.event:delete',          'admin');

		$this->route('GET',    '/-/admin/schedules',                   'admin.schedule:index',        'admin');
		$this->route('GET',    '/-/admin/schedules/{schedule}/edit',   'admin.schedule:edit',         'admin');
		$this->route('PUT',    '/-/admin/schedules/{schedule}',        'admin.schedule:update',       'admin');
		$this->route('GET',    '/-/admin/schedules/{schedule}/delete', 'admin.schedule:confirmation', 'admin');
		$this->route('DELETE', '/-/admin/schedules/{schedule}',        'admin.schedule:delete',       'admin');

		///////////////////////////////////////////////////////////////////////////////////////////
		// operator-only extensions to the admin interface

		$this->route('GET', '/-/admin/utils',                    'admin.utils:index',              'op');

		$this->route('GET', '/-/admin/utils/config',             'admin.utils.config:form',        'op');
		$this->route('PUT', '/-/admin/utils/config',             'admin.utils.config:update',      'op');

		$this->route('GET', '/-/admin/utils/serverinfo',         'admin.utils.serverinfo:form',    'op');
		$this->route('GET', '/-/admin/utils/serverinfo/phpinfo', 'admin.utils.serverinfo:phpinfo', 'op');

		///////////////////////////////////////////////////////////////////////////////////////////
		// generic event/schedule routes

		$this->route('GET', '/{eventslug}',                          'frontend:event');
		$this->route('GET', '/{eventslug}/',                         'frontend:event');
		$this->route('GET', '/{eventslug}/{scheduleslug}.{format}',  'frontend:scheduleExport')->assert('format', '(jsonp?|xml|csv|ical)');
		$this->route('GET', '/{eventslug}/{scheduleslug}',           'frontend:schedule');
		$this->route('GET', '/{eventslug}/{scheduleslug}/',          'frontend:schedule');
		$this->route('GET', '/{eventslug}/{scheduleslug}/ical-feed', 'frontend:icalFaq');
	}

	protected function route($method, $pattern, $endpoint, $requiredRole = null, $noCsrf = false) {
		$endpoint   = 'controller.'.$endpoint.'Action';
		$controller = $this->match($pattern, $endpoint)->method($method);
		$route      = $controller->getRoute();

		if ($requiredRole) {
			$route->setDefault(Middleware\Firewall::REQUIRED_ROLE, 'ROLE_'.strtoupper($requiredRole));

			if ($requiredRole === 'admin') {
				$route->setDefault(Middleware\ACL::ADMIN_MODE, true);
			}
		}

		if ($noCsrf) {
			$route->setDefault(Middleware\Csrf::REQUIRE_NO_CSRF_TOKEN, true);
		}

		return $controller;
	}
}
