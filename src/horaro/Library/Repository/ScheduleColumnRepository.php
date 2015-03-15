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
 * Schedule Column Repository
 */
class ScheduleColumnRepository extends EntityRepository {
	public function count(Schedule $schedule = null) {
		$dql = 'SELECT COUNT(c.id) FROM horaro\Library\Entity\ScheduleColumn c';

		if ($schedule) {
			$query = $this->_em->createQuery($dql.' WHERE c.schedule = :schedule');
			$query->setParameter('schedule', $schedule);
		}
		else {
			$query = $this->_em->createQuery($dql);
		}

		return (int) $query->getSingleScalarResult();
	}
}
