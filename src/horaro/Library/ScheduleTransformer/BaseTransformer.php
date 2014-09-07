<?php
/*
 * Copyright (c) 2014, Sgt. Kabukiman, https://bitbucket.org/sgt-kabukiman/
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\Library\ScheduleTransformer;

class BaseTransformer {
	protected function encodeID($id, $entityType = null) {
		return $id;
	}

	protected function decodeID($hash, $entityType = null) {
		if (!ctype_digit($hash)) {
			return null;
		}

		$id = (int) $hash;

		return $id ?: null;
	}
}
