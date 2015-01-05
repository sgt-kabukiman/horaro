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
use horaro\Library\Entity\User;

/**
 * Event Repository
 */
class EventRepository extends EntityRepository {
	public function count(User $user = null) {
		$dql = 'SELECT COUNT(e.id) FROM horaro\Library\Entity\Event e';

		if ($user) {
			$query = $this->_em->createQuery($dql.' WHERE e.user = :user');
			$query->setParameter('user', $user);
		}
		else {
			$query = $this->_em->createQuery($dql);
		}

		return (int) $query->getSingleScalarResult();
	}

	public function findPublic() {
		$dql   = 'SELECT e FROM horaro\Library\Entity\Event e WHERE e.secret IS NULL ORDER BY e.id ASC';
		$query = $this->_em->createQuery($dql);

		return $query->getResult();
	}
}
