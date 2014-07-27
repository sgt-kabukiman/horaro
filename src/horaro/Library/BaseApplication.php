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
		$this['session.storage.handler'] = $this->share(function() {
			$connection = $this['app.entitymanager']->getConnection();
			$connection = $connection->getWrappedConnection();
			$config     = $this['app.config'];

			return new PdoSessionHandler(
				$connection,
				$config['session'],
				$this['session.storage.options']
			);
		});

		$this['app.config'] = $this->share(function() {
			$config = new Configuration();
			$dir    = HORARO_ROOT.'/resources/config/';

			$config->loadFile($dir.'config.yml');
			$config->loadFile($dir.'parameters.yml');

			return $config;
		});

		$this['app.entitymanager'] = $this->share(function() {
			// the connection configuration
			$config   = $this['app.config'];
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

		$this['app.rolemanager'] = $this->share(function() {
			return new RoleManager($this['app.config']['roles']);
		});

		// set Silex' debug flag
		$this['debug'] = $this['app.config']['debug'];
	}
}
