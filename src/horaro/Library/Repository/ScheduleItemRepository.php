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
use horaro\Library\Entity\Schedule;

/**
 * Schedule Item Repository
 */
class ScheduleItemRepository extends EntityRepository {
	public function count(Schedule $schedule = null) {
		$dql = 'SELECT COUNT(i.id) FROM horaro\Library\Entity\ScheduleItem i';

		if ($schedule) {
			$query = $this->_em->createQuery($dql.' WHERE i.schedule = :schedule');
			$query->setParameter('schedule', $schedule);
		}
		else {
			$query = $this->_em->createQuery($dql);
		}

		return (int) $query->getSingleScalarResult();
	}
}
