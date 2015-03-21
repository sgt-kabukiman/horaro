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

class CsvTransformer extends BaseTransformer {
	const DATE_FORMAT = 'r';

	public function getContentType() {
		return 'text/csv; charset=UTF-8';
	}

	public function getFileExtension() {
		return 'csv';
	}

	public function transform(Schedule $schedule, $public = false) {
		$rows      = [];
		$cols      = $schedule->getColumns();
		$toCSV     = function($val) {
			return '"'.addcslashes($val, '"').'"';
		};

		$header = [$toCSV('Scheduled'), $toCSV('Length')];

		foreach ($cols as $col) {
			$header[] = $toCSV($col->getName());
		}

		$rows[] = implode(';', $header);

		foreach ($schedule->getScheduledItems() as $item) {
			$extra = $item->getExtra();
			$row   = [
				'scheduled' => $toCSV($item->getScheduled()->format(self::DATE_FORMAT)),
				'length'    => $toCSV($item->getLength()->format('H:i:s'))
			];

			foreach ($cols as $col) {
				$colID = $col->getId();
				$row[] = isset($extra[$colID]) ? $toCSV($extra[$colID]) : '';
			}

			$rows[] = implode(';', $row);
		}

		return implode("\n", $rows);
	}
}
