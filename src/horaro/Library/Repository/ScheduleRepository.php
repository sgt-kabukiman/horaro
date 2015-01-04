<?php
/*
 * Copyright (c) 2014, Sgt. Kabukiman, https://bitbucket.org/sgt-kabukiman/
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\Library\Repository;

use Doctrine\ORM\EntityRepository;
use horaro\Library\Entity\Event;
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

	public function findUpcoming($days) {
		$end   = gmdate('Y-m-d H:i:s', time() + $days*24*3600);
		$dql   = 'SELECT s, e FROM horaro\Library\Entity\Schedule s JOIN s.event e WHERE e.secret IS NULL AND s.secret IS NULL AND s.start > :begin AND s.start <= :end ORDER BY s.start ASC';
		$query = $this->_em->createQuery($dql);

		// search begins at "now minus 1 day" to include events with different timezones as well;
		// this requires filtering the schedules later on by their actual start date/time.

		$query->setParameter('begin', gmdate('Y-m-d H:i:s', time() - 24*3600));
		$query->setParameter('end', $end);

		$schedules = $query->getResult();
		$result    = [];
		$now       = time();

		foreach ($schedules as $schedule) {
			$start = $schedule->getLocalStart()->format('U');

			if ($start > $now) {
				$result[] = $schedule;
			}
		}

		return $result;
	}

	public function findPublic(\DateTime $startFrom, \DateTime $startTo) {
		$from  = $startFrom->format('Y-m-d H:i:s');
		$to    = $startTo->format('Y-m-d H:i:s');
		$dql   = 'SELECT s, e FROM horaro\Library\Entity\Schedule s JOIN s.event e WHERE e.secret IS NULL AND s.secret IS NULL AND s.start BETWEEN :a AND :b ORDER BY s.start ASC';
		$query = $this->_em->createQuery($dql);

		$query->setParameter('a', $from);
		$query->setParameter('b', $to);

		return $query->getResult();
	}

	public function findRecentlyUpdated(User $user, $max) {
		$dql   = 'SELECT s, e FROM horaro\Library\Entity\Schedule s JOIN s.event e WHERE e.user = :user ORDER BY s.updated_at DESC';
		$query = $this->_em->createQuery($dql);

		$query->setParameter('user', $user);
		$query->setMaxResults($max);

		return $query->getResult();
	}
}
