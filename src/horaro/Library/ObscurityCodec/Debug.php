<?php
/*
 * Copyright (c) 2015, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\Library\ObscurityCodec;

use horaro\Library\ObscurityCodec;

class Debug implements ObscurityCodec {
	public function encode($id, $entityType = null) {
		return (string) $id;
	}

	public function decode($hash, $entityType = null) {
		if (!ctype_digit((string) $hash)) {
			return null;
		}

		$id = (int) $hash;

		return $id ?: null;
	}
}
