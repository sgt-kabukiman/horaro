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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class RuntimeConfiguration {
	protected $config;
	protected $repo;
	protected $em;

	public function __construct(Configuration $config, EntityRepository $repo, EntityManager $em) {
		$this->config = $config;
		$this->repo   = $repo;
		$this->em     = $em;
	}

	public function init() {
		$entities = $this->repo->findBy([], ['keyname' => 'ASC']);

		foreach ($entities as $entity) {
			$this->config[$entity->getKey()] = $entity->getValue();
		}
	}

	public function set($key, $value) {
		$key      = strtolower($key);
		$existing = $this->repo->findOneByKeyname($key);

		if (!$existing) {
			$existing = new Config($key, $value);
			$this->em->persist($existing);
		}

		$existing->setValue($value);

		return $this;
	}

	public function get($key, $default = null) {
		$config = $this->repo->findOneByKeyname(strtolower($key));

		return $config ? $config->getValue() : $default;
	}
}
