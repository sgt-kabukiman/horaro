<?php
/*
 * Copyright (c) 2014, Sgt. Kabukiman, https://bitbucket.org/sgt-kabukiman/
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\Library;

use horaro\Library\Entity\User;

class RoleManager {
	protected $roles;

	public function __construct(array $roles) {
		$this->roles = $roles;
	}

	public function getWeight($role) {
		$weight = array_search($role, $this->roles);

		if ($weight === false) {
			throw new \InvalidArgumentException('Unknown role "'.$role.'" given.');
		}

		return $weight;
	}

	public function isIncluded($role, $inThisRole) {
		return $this->getWeight($role) <= $this->getWeight($inThisRole);
	}

	public function userHasRole($role, User $user) {
		return $this->isIncluded($role, $user->getRole());
	}

	public function userIsSuperior(User $user, User $to) {
		return $this->getWeight($to->getRole()) < $this->getWeight($user->getRole());
	}

	public function userIsColleague(User $user, User $to) {
		return $to->getRole() === $user->getRole();
	}

	public function userIsOp(User $user) {
		return $this->userHasRole('ROLE_OP', $user);
	}

	public function userIsAdmin(User $user) {
		return $this->userHasRole('ROLE_ADMIN', $user);
	}
}
