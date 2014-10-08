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

/**
 * User Repository
 */
class UserRepository extends EntityRepository {
	public function count() {
		return (int) $this->_em->createQuery('SELECT COUNT(u.id) FROM horaro\Library\Entity\User u')->getSingleScalarResult();
	}
}
