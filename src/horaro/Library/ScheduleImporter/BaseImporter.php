<?php
/*
 * Copyright (c) 2014, Sgt. Kabukiman, https://bitbucket.org/sgt-kabukiman/
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\Library\ScheduleImporter;

use Doctrine\ORM\EntityManager;
use horaro\Library\Entity\Schedule;
use horaro\WebApp\Validator\ScheduleValidator;

class BaseImporter {
	protected $em;
	protected $log;
	protected $validator;

	public function __construct(EntityManager $em, ScheduleValidator $validator) {
		$this->em        = $em;
		$this->validator = $validator;
		$this->log       = [];
	}

	protected function persist($o) {
		$this->em->persist($o);
	}

	protected function remove($o) {
		$this->em->remove($o);
	}

	protected function flush() {
		$this->em->flush();
	}

	protected function log($type, $msg) {
		$this->log[] = [$type, $msg];
	}

	protected function returnLog() {
		$l = $this->log;
		$this->log = [];

		return $l;
	}
}
