<?php
/*
 * Copyright (c) 2019, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\Library;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\SimplifiedYamlDriver;
use Doctrine\ORM\Tools\Setup;
use Jenssegers\Optimus\Optimus;
use Sentry\ClientBuilder;
use Silex\Application;
use Silex\Provider;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

class BaseApplication extends Application {
	public function __construct(array $values = []) {
		parent::__construct($values);

		$this->register(new Provider\ServiceControllerServiceProvider());
		$this->register(new Provider\SessionServiceProvider());
	}

	public function setupServices() {
		$this['config'] = function() {
			$config = new Configuration();
			$dir    = HORARO_ROOT.'/resources/config/';

			$config->loadFile($dir.'config.yml');
			$config->loadFile($dir.'parameters.yml');

			return $config;
		};

		$this['runtime-config'] = function() {
			return new RuntimeConfiguration($this['config'], $this['entitymanager']->getRepository('horaro\Library\Entity\Config'), $this['entitymanager']);
		};

		$this['session.storage.options'] = $this->factory(function() {
			return [
				'cookie_httponly' => true,
				'cookie_lifetime' => $this['config']['cookie_lifetime'],
				'cookie_secure'   => $this['config']['cookie_secure']
			];
		});

		$this['session.storage.handler'] = function() {
			$connection = $this['entitymanager']->getConnection();
			$connection = $connection->getWrappedConnection();
			$config     = $this['config'];

			return new PdoSessionHandler(
				$connection,
				$config['session'],
				$this['session.storage.options']
			);
		};

		$this['entitymanager'] = function() {
			// the connection configuration
			$config   = $this['config'];
			$paths    = [HORARO_ROOT.'/resources/config' => 'horaro\Library\Entity'];
			$proxyDir = $config['doctrine_proxies'];

			// relative path?
			if ($proxyDir[0] !== '/' && !preg_match('#^[a-z]:[\\\\/]#i', $proxyDir)) {
				$proxyDir = HORARO_ROOT.DIRECTORY_SEPARATOR.$proxyDir;
			}

			$driver = new SimplifiedYamlDriver($paths);
			$driver->setGlobalBasename('schema');

			$configuration = Setup::createConfiguration($config['debug'], $proxyDir, new ArrayCache());
			$configuration->setMetadataDriverImpl($driver);

			return EntityManager::create($config['database'], $configuration);
		};

		$this['rolemanager'] = function() {
			return new RoleManager($this['config']['roles']);
		};

		$this['encoder'] = function() {
			return new PasswordEncoder($this['config']['bcrypt_cost']);
		};

		$this['obscurity-codec'] = function() {
			if ($this['debug']) {
				return new ObscurityCodec\Debug();
			}

			$config  = $this['config']['optimus'];
			$optimus = new Optimus($config['prime'], $config['inverse'], $config['random']);

			return new ObscurityCodec\Optimus($optimus);
		};

		$this['sentry-client'] = function() {
			return ClientBuilder::create([
				'dsn' => $this['config']['sentry_dsn'],
			])->getClient();
		};

		$this['schedule-transformer-json'] = function() {
			return new ScheduleTransformer\JsonTransformer($this['obscurity-codec']);
		};

		$this['schedule-transformer-jsonp'] = function() {
			return new ScheduleTransformer\JsonpTransformer($this['request'], $this['obscurity-codec']);
		};

		$this['schedule-transformer-xml'] = function() {
			return new ScheduleTransformer\XmlTransformer($this['obscurity-codec']);
		};

		$this['schedule-transformer-csv'] = function() {
			return new ScheduleTransformer\CsvTransformer($this['obscurity-codec']);
		};

		$this['schedule-transformer-ical'] = function() {
			$secret = $this['config']['secret'];
			$host   = $this['request']->getHost();

			return new ScheduleTransformer\ICalTransformer($secret, $host, $this['obscurity-codec']);
		};

		// FIXME: The validators belong to the WebApp and should not be referenced in here.
		//        We break this rule at the moment to avoid having to duplicate validation rules.

		$this['schedule-importer-csv'] = function() {
			return new ScheduleImporter\CsvImporter($this['entitymanager'], $this['validator.schedule']);
		};

		$this['schedule-importer-json'] = function() {
			return new ScheduleImporter\JsonImporter($this['entitymanager'], $this['validator.schedule']);
		};

		// set Silex' debug flag
		$this['debug'] = $this['config']['debug'];
	}
}
