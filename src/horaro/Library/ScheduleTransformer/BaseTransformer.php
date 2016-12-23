<?php
/*
 * Copyright (c) 2015, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\Library\ScheduleTransformer;

use horaro\Library\Entity\Schedule;
use horaro\Library\ObscurityCodec;

class BaseTransformer {
	protected $codec;

	public function __construct(ObscurityCodec $codec) {
		$this->codec = $codec;
	}

	protected function encodeID($id, $entityType = null) {
		return $this->codec->encode($id, $entityType);
	}

	protected function decodeID($hash, $entityType = null) {
		return $this->codec->decode($hash, $entityType);
	}

	protected function getEffectiveColumns(Schedule $schedule, $withHiddenColumns = false) {
		$cols = $withHiddenColumns ? $schedule->getColumns() : $schedule->getVisibleColumns();

		// never expose the special options column
		$cols = $cols->filter(function($col) {
			return $col->getName() !== Schedule::OPTION_COLUMN_NAME;
		});

		return $cols;
	}
}
