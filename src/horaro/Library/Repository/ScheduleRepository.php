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
		$start = gmdate('Y-m-d H:i:s', time() + $days*24*3600);
		$dql   = 'SELECT s, e FROM horaro\Library\Entity\Schedule s JOIN s.event e WHERE e.secret IS NULL AND s.secret IS NULL AND s.start > :now AND s.start <= :start ORDER BY s.start ASC';
		$query = $this->_em->createQuery($dql);

		$query->setParameter('now', gmdate('Y-m-d H:i:s'));
		$query->setParameter('start', $start);

		return $query->getResult();
	}
}
