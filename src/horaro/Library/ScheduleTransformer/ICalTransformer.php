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

use horaro\Library\Entity\Schedule;
use horaro\Library\Entity\ScheduleItem;

class ICalTransformer extends BaseTransformer {
	const DATE_FORMAT     = 'Ymd\THis';
	const DATE_FORMAT_UTC = 'Ymd\THis\Z';

	public function getContentType() {
		return 'text/calendar; charset=UTF-8';
	}

	public function getFileExtension() {
		return 'ics';
	}

	public function transform(Schedule $schedule) {
		$now         = new \DateTime('now UTC');
		$tz          = $schedule->getTimezone();
		$start       = $schedule->getLocalStart();
		$scheduled   = clone $start;
		$columns     = $schedule->getColumns();
		$columnNames = [];
		$columnIDs   = [];

		foreach ($columns as $col) {
			$columnIDs[] = $col->getId();
			$columnNames[$col->getId()] = $col->getName();
		}

		$summaryCol   = reset($columnIDs);
		$extendedCols = array_slice($columnIDs, 1);

		$lines = [
			'BEGIN:VCALENDAR',
			'VERSION:2.0',
			'PRODID:'.$this->getProductID()
		];

		foreach ($schedule->getItems() as $item) {
			$extra       = $item->getExtra();
			$summary     = isset($extra[$summaryCol]) ? $extra[$summaryCol] : '(unnamed)';
			$description = [];

			foreach ($extendedCols as $extCol) {
				if (isset($extra[$extCol])) {
					$colName       = $columnNames[$extCol];
					$description[] = sprintf('%s: %s', $colName, $extra[$extCol]);
				}
			}

			$lines[] = 'BEGIN:VEVENT';
			$lines[] = 'UID:'.$this->generateUID($item);
			$lines[] = 'DTSTAMP:'.$now->format(self::DATE_FORMAT_UTC);
			$lines[] = $this->getString($summary, 'SUMMARY');
			$lines[] = 'DTSTART;TZID='.$tz.':'.$scheduled->format(self::DATE_FORMAT);

			$scheduled->add($item->getDateInterval());

			$lines[] = 'DTEND;TZID='.$tz.':'.$scheduled->format(self::DATE_FORMAT);

			if (!empty($description)) {
				$lines[] = $this->getString(implode("\n", $description), 'DESCRIPTION');
			}

			$lines[] = 'CLASS:PUBLIC';
			$lines[] = 'END:VEVENT';
		}

		$lines[] = 'END:VCALENDAR';
		$result  = implode("\r\n", $lines)."\r\n";

		return $result;
	}

	public function generateUID(ScheduleItem $item) {
		$event    = $item->getSchedule()->getEvent()->getSlug();
		$schedule = $item->getSchedule()->getSlug();
		$item     = $item->getId();

		return sprintf('%s_%s_%d@horaro.example.com', $event, $schedule, $item);
	}

	protected function getProductID() {
		// http://www.xfront.com/schematron/formal-public-identifier.html
		return '-//kabukiman//horaro//EN';
	}

	public function getString($str, $property) {
		// RFC says (3.1): "Lines of text SHOULD NOT be longer than 75 **octets**, excluding the line break."
		// Plus, we don't want to cut Unicode chars in half, so until someone finds
		// a really cool algorithm to do so, we just go character by character and
		// count the bytes (character != byte).

		$str   = addcslashes($str, ',;:\\'."\n");
		$total = strtoupper($property).':'.$str;

		// simple case, do nothing
		if (mb_strlen($total, '8bit') <= 75) return $total;

		$result = '';
		$len    = 0;

		while (mb_strlen($total) !== 0) {
			$char   = mb_substr($total, 0, 1);
			$octets = mb_strlen($char, '8bit');

			if ($len+$octets > 75) {
				$result .= "\r\n $char";
				$len     = $octets + 1; // len of char + the leading space
			}
			else {
				$result .= $char;
				$len    += $octets;
			}

			// cut off the first character
			$total = mb_substr($total, 1);
		}

		return $result;
	}
}
