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

/**
 * User Repository
 */
class UserRepository extends EntityRepository {
	public function countUsers() {
		return (int) $this->_em->createQuery('SELECT COUNT(u.id) FROM horaro\Library\Entity\User u')->getSingleScalarResult();
	}

	public function findFiltered($query, $size, $offset) {
		return $this->createQueryBuilder('u')
			->where('u.login LIKE :query')
			->orWhere('u.display_name LIKE :query')
			->setParameter('query', '%'.$query.'%')
			->add('orderBy', 'u.login ASC')
   		->setMaxResults($size)
			->setFirstResult($offset)
			->getQuery()
			->getResult();
	}

	public function countFiltered($query) {
		return $this->createQueryBuilder('u')
			->select('COUNT(u)')
			->where('u.login LIKE :query')
			->orWhere('u.display_name LIKE :query')
			->setParameter('query', '%'.$query.'%')
			->getQuery()
			->getSingleScalarResult();
	}

	public function findInactiveOAuthAccounts() {
		$dql   = 'SELECT DISTINCT u FROM horaro\Library\Entity\User u LEFT JOIN u.events e WHERE u.password IS NULL AND e.id IS NULL AND u.twitch_oauth IS NOT NULL AND u.created_at < :threshold ORDER BY u.id ASC';
		$query = $this->_em->createQuery($dql);

		$query->setParameter('threshold', gmdate('Y-m-d H:i:s', strtotime('-1 month')));

		return $query->getResult();
	}
}
