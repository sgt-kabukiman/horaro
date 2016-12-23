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

class ReadableTime {
	public function parse($string) {
		$string = trim($string);

		if (mb_strlen($string) === 0) {
			return null;
		}

		if (!preg_match_all('/(\d+)(hr|h|min|m|sec|s)/i', $string, $matches, PREG_SET_ORDER)) {
			throw new \InvalidArgumentException('This time string does not contain anything I can understand.');
		}

		$time = 0;

		foreach ($matches as $match) {
			$amount = (int) $match[1];

			// take care of integer overflows
			if ($amount < 0) {
				continue;
			}

			switch ($match[2]) {
				case 'h':
				case 'hr':
					$time += $match[1] * 3600;
					break;

				case 'm':
				case 'min':
					$time += $match[1] * 60;
					break;

				case 's':
				case 'sec':
					$time += $match[1];
					break;
			}
		}

		if ($time >= 24*3600) {
			$time = 24*3600 - 1;
		}

		return \DateTime::createFromFormat('U', $time);
	}

	public function stringify(\DateTime $time) {
		// do not use format('U') and then decode down, because we often read times
		// from the database, which is read using the system timezone, which is not
		// UTC (at least we don't enforce it).

		$hours   = (int) $time->format('H');
		$minutes = (int) $time->format('i');
		$seconds = (int) $time->format('s');

		$result = [];

		if ($hours)   $result[] = $hours.'h';
		if ($minutes) $result[] = $minutes.'min';
		if ($seconds) $result[] = $seconds.'s';

		return implode(' ', $result);
	}

	public static function dateTimeToSeconds(\DateTime $dt = null) {
		if (!$dt) {
			return 0;
		}

		$parts = explode(':', $dt->format('H:i:s'));

		return $parts[0] * 3600 + $parts[1] * 60 + $parts[2];
	}

	public static function dateTimeToISODuration(\DateTime $dt = null) {
		if (!$dt) {
			return 'PT0S';
		}

		$iso = preg_replace('/(?<=[THMS])0+[HMS]/', '$1', $dt->format('\P\TG\Hi\Ms\S'));

		if ($iso === 'PT') {
			return 'PT0S';
		}

		return $iso;
	}

	public static function dateTimeToDateInterval(\DateTime $dt = null) {
		return new \DateInterval(static::dateTimeToISODuration($dt));
	}
}
