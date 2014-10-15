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
		$this['config'] = $this->share(function() {
			$config = new Configuration();
			$dir    = HORARO_ROOT.'/resources/config/';

			$config->loadFile($dir.'config.yml');
			$config->loadFile($dir.'parameters.yml');

			return $config;
		});

		$this['runtime-config'] = $this->share(function() {
			return new RuntimeConfiguration($this['config'], $this['entitymanager']->getRepository('horaro\Library\Entity\Config'), $this['entitymanager']);
		});

		$this['session.storage.options'] = function() {
			return [
				'cookie_httponly' => true,
				'cookie_lifetime' => $this['config']['cookie_lifetime']
			];
		};

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

		$this['encoder'] = $this->share(function() {
			return new PasswordEncoder($this['config']['bcrypt_cost']);
		});

		$this['obscurity-codec'] = $this->share(function() {
			return $this['debug'] ? new ObscurityCodec\Debug() : new ObscurityCodec\Hashids($this['config']['secret'], 8);
		});

		$this['schedule-transformer-json'] = $this->share(function() {
			return new ScheduleTransformer\JsonTransformer($this['obscurity-codec']);
		});

		$this['schedule-transformer-jsonp'] = $this->share(function() {
			return new ScheduleTransformer\JsonpTransformer($this['request'], $this['obscurity-codec']);
		});

		$this['schedule-transformer-xml'] = $this->share(function() {
			return new ScheduleTransformer\XmlTransformer($this['obscurity-codec']);
		});

		$this['schedule-transformer-csv'] = $this->share(function() {
			return new ScheduleTransformer\CsvTransformer($this['obscurity-codec']);
		});

		$this['schedule-transformer-ical'] = $this->share(function() {
			$secret = $this['config']['secret'];
			$host   = $this['request']->getHost();

			return new ScheduleTransformer\ICalTransformer($secret, $host, $this['obscurity-codec']);
		});

		// set Silex' debug flag
		$this['debug'] = $this['config']['debug'];
	}
}
