<?php
/*
 * Copyright (c) 2015, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\Library;

class PasswordEncoder {
	protected $cost;

	public function __construct($cost) {
		if ($cost < 6) {
			throw new \InvalidArgumentException('Setting the bcrypt cost factor to something this low is stupid.');
		}

		if ($cost > 15) {
			throw new \InvalidArgumentException('bcrypt cost factors this high will lead to Denial of Service even during regular usage.');
		}

		$this->cost = $cost;
	}

	public function encode($password) {
		return password_hash($password, PASSWORD_DEFAULT, ['cost' => $this->cost]);
	}
}
