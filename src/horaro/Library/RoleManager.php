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

class RoleManager {
	protected $roles;

	public function __construct(array $roles) {
		$this->roles = $roles;
	}

	public function getRoles($role) {
		if (!isset($this->roles[$role])) {
			throw new \InvalidArgumentException('Unknown role "'.$role.'" given.');
		}

		$result = [$role];
		$stack  = $this->roles[$role];

		while (!empty($stack)) {
			$r = array_shift($stack);

			if (!in_array($r, $result, true)) {
				$result[] = $r;
			}

			foreach ($this->roles[$r] as $i) {
				$stack[] = $i;
			}
		}

		return $result;
	}

	public function isIncluded($role, $inThisRole) {
		return in_array($role, $this->getRoles($inThisRole), true);
	}
}
