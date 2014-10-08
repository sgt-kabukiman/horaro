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
}
