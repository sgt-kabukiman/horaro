<?php
/*
 * Copyright (c) 2015, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\Library\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use horaro\Library\Entity\Event;
use horaro\Library\Entity\Schedule;
use horaro\Library\Entity\User;

/**
 * Schedule Repository
 */
class ScheduleRepository extends EntityRepository {
	public function count(Event $event = null) {
		$dql = 'SELECT COUNT(s.id) FROM horaro\Library\Entity\Schedule s';

		if ($event) {
			$query = $this->_em->createQuery($dql.' WHERE s.event = :event');
			$query->setParameter('event', $event);
		}
		else {
			$query = $this->_em->createQuery($dql);
		}

		return (int) $query->getSingleScalarResult();
	}

	public function findCurrentlyRunning() {
		$day       = 24 * 3600;
		$now       = time();
		$schedules = $this->findPublicInRange($now - 21*$day, $now + 1*$day);
		$result    = [];

		foreach ($schedules as $schedule) {
			$start = $schedule->getLocalStart()->format('U');

			if ($start < $now) {
				$end = $schedule->getLocalEnd()->format('U'); // defer calculating this until we checked the start

				if ($end > $now) {
					$result[] = $schedule;
				}
			}
		}

		return $result;
	}

	public function findUpcoming($days) {
		// search begins at "now minus 1 day" to include events with different timezones as well;
		// this requires filtering the schedules later on by their actual start date/time.

		$day       = 24 * 3600;
		$now       = time();
		$schedules = $this->findPublicInRange($now - 1*$day, $now + $days*$day);
		$result    = [];

		foreach ($schedules as $schedule) {
			$start = $schedule->getLocalStart()->format('U');

			if ($start > $now) {
				$result[] = $schedule;
			}
		}

		return $result;
	}

	public function findPublic(\DateTime $startFrom, \DateTime $startTo) {
		return $this->findPublicInRange($startFrom->format('U'), $startTo->format('U'));
	}

	public function findRecentlyUpdated(User $user, $max) {
		$dql   = 'SELECT s, e FROM horaro\Library\Entity\Schedule s JOIN s.event e WHERE e.user = :user ORDER BY s.updated_at DESC';
		$query = $this->_em->createQuery($dql);

		$query->setParameter('user', $user);
		$query->setMaxResults($max);

		return $query->getResult();
	}

	public function transientLock(Schedule $schedule) {
		$rsm = new ResultSetMappingBuilder($this->_em);
		$rsm->addRootEntityFromClassMetadata('horaro\Library\Entity\Schedule', 's');

		$query = $this->_em->createNativeQuery('SELECT id FROM schedules WHERE id = :id FOR UPDATE', $rsm);
		$query->setParameter('id', $schedule->getId());
		$query->getOneOrNullResult(); // this one blocks until the lock is available
	}

	protected function findPublicInRange($from, $to) {
		$dql   = 'SELECT s, e FROM horaro\Library\Entity\Schedule s JOIN s.event e WHERE e.secret IS NULL AND s.secret IS NULL AND s.start BETWEEN :from AND :to ORDER BY s.start ASC';
		$query = $this->_em->createQuery($dql);

		$query->setParameter('from', gmdate('Y-m-d H:i:s', $from));
		$query->setParameter('to',   gmdate('Y-m-d H:i:s', $to));

		return $query->getResult();
	}
}
