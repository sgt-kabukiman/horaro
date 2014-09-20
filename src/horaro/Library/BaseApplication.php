<?php
/*
 * Copyright (c) 2014, Sgt. Kabukiman, https://bitbucket.org/sgt-kabukiman/
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
		$this['session.storage.options'] = [
			'cookie_httponly' => true,
			'cookie_lifetime' => 24*3600 // 1 day
		];

		$this['session.storage.handler'] = $this->share(function() {
			$connection = $this['entitymanager']->getConnection();
			$connection = $connection->getWrappedConnection();
			$config     = $this['config'];

			return new PdoSessionHandler(
				$connection,
				$config['session'],
				$this['session.storage.options']
			);
		});

		$this['config'] = $this->share(function() {
			$config = new Configuration();
			$dir    = HORARO_ROOT.'/resources/config/';

			$config->loadFile($dir.'config.yml');
			$config->loadFile($dir.'parameters.yml');

			return $config;
		});

		$this['entitymanager'] = $this->share(function() {
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
		});

		$this['rolemanager'] = $this->share(function() {
			return new RoleManager($this['config']['roles']);
		});

		$this['schedule-transformer-json'] = $this->share(function() {
			return new ScheduleTransformer\JsonTransformer();
		});

		$this['schedule-transformer-xml'] = $this->share(function() {
			return new ScheduleTransformer\XmlTransformer();
		});

		$this['schedule-transformer-csv'] = $this->share(function() {
			return new ScheduleTransformer\CsvTransformer();
		});

		$this['schedule-transformer-ical'] = $this->share(function() {
			return new ScheduleTransformer\ICalTransformer('something super secret that should be configurable');
		});

		// set Silex' debug flag
		$this['debug'] = $this['config']['debug'];
	}
}
