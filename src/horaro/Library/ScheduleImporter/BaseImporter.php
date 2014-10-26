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

	protected function replaceColumns(Schedule $schedule, array $columns) {
		foreach ($schedule->getColumns() as $col) {
			$this->remove($col);
		}

		foreach ($columns as $col) {
			$col->setSchedule($schedule);
			$this->persist($col);
		}

		$this->flush();

		$columnIDs = [];

		foreach ($columns as $col) {
			$columnIDs[] = $col->getId();
		}

		return $columnIDs;
	}

	protected function replaceItems(Schedule $schedule, array $items, array $columnIDs) {
		foreach ($schedule->getItems() as $item) {
			$this->remove($item);
		}

		foreach ($items as $item) {
			$extra = [];

			foreach ($item->tmpExtra as $idx => $value) {
				$columnID = $columnIDs[$idx];
				$extra[$columnID] = $value;
			}

			$item->setSchedule($schedule)->setExtra($extra);
			$this->persist($item);
		}
	}
}
