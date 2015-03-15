<?php
/*
 * Copyright (c) 2015, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp;

use Doctrine\ORM\EntityManager;
use horaro\WebApp\Exception\NotFoundException;

class ResourceResolver {
	protected $em;
	protected $codec;

	public function __construct(EntityManager $em, $codec) {
		$this->em    = $em;
		$this->codec = $codec;
	}

	public function resolveEventID($eventID, $encoded) {
		$input = $eventID;

		if ($encoded) {
			$eventID = $this->decode($eventID, 'event');
		}

		$repo  = $this->getRepository('Event');
		$event = $repo->findOneById($eventID);

		if (!$event) {
			throw new NotFoundException('Event '.$input.' could not be found.');
		}

		return $event;
	}

	public function resolveScheduleID($scheduleID, $encoded) {
		$input = $scheduleID;

		if ($encoded) {
			$scheduleID = $this->decode($scheduleID, 'schedule');
		}

		$repo     = $this->getRepository('Schedule');
		$schedule = $repo->findOneById($scheduleID);

		if (!$schedule) {
			throw new NotFoundException('Schedule '.$input.' could not be found.');
		}

		return $schedule;
	}

	public function resolveScheduleItemID($itemID, $encoded) {
		$input = $itemID;

		if ($encoded) {
			$itemID = $this->decode($itemID, 'schedule.item', 'schedule item');
		}

		$repo = $this->getRepository('ScheduleItem');
		$item = $repo->findOneById($itemID);

		if (!$item) {
			throw new NotFoundException('Schedule item '.$input.' could not be found.');
		}

		return $item;
	}

	public function resolveScheduleColumnID($columnID, $encoded) {
		$input = $columnID;

		if ($encoded) {
			$columnID = $this->decode($columnID, 'schedule.column', 'schedule column');
		}

		$repo   = $this->getRepository('ScheduleColumn');
		$column = $repo->findOneById($columnID);

		if (!$column) {
			throw new NotFoundException('Schedule column '.$input.' could not be found.');
		}

		return $column;
	}

	public function resolveUserID($userID, $encoded) {
		$input = $userID;

		if ($encoded) {
			$userID = $this->decode($userID, 'user');
		}

		$repo = $this->getRepository('User');
		$user = $repo->findOneById($userID);

		if (!$user) {
			throw new NotFoundException('User '.$input.' could not be found.');
		}

		return $user;
	}

	protected function getRepository($className) {
		return $this->em->getRepository('horaro\Library\Entity\\'.$className);
	}

	protected function decode($hash, $entityType, $name = null) {
		$id = $this->codec->decode($hash, $entityType);

		if ($id === null) {
			throw new NotFoundException('The '.($name ?: $entityType).' '.$hash.' could not be found.');
		}

		return $id;
	}
}
