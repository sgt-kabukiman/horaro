<?php
/*
 * Copyright (c) 2019, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\Library\Entity;

use Doctrine\ORM\Mapping as ORM;
use horaro\Library\ReadableTime;

/**
 * ScheduleItem
 */
class ScheduleItem {
	/**
	 * @var integer
	 */
	private $id;

	/**
	 * @var integer
	 */
	private $position;

	/**
	 * @var \DateTime
	 */
	private $length;

	/**
	 * @var string
	 */
	private $extra;

	/**
	 * @var \horaro\Library\Entity\Schedule
	 */
	private $schedule;

	/**
	 * calculated scheduled date; this is not synchronized with the database,
	 * but meant to be set by the ScheduleItemIterator.
	 *
	 * @var \DateTime
	 */
	private $scheduled;

	/**
	 * Get id
	 *
	 * @return integer
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Set position
	 *
	 * @param integer $position
	 * @return ScheduleItem
	 */
	public function setPosition($position) {
		$this->position = $position;

		return $this;
	}

	/**
	 * Get position
	 *
	 * @return integer
	 */
	public function getPosition() {
		return $this->position;
	}

	/**
	 * Set length
	 *
	 * @param \DateTime $length
	 * @return ScheduleItem
	 */
	public function setLength($length) {
		$this->length = $length;

		return $this;
	}

	public function setLengthInSeconds($seconds) {
		return $this->setLength(\DateTime::createFromFormat('U', $seconds));
	}

	/**
	 * Get length
	 *
	 * @return \DateTime
	 */
	public function getLength() {
		return $this->length;
	}

	/**
	 * Get length in seconds
	 *
	 * @return int
	 */
	public function getLengthInSeconds() {
		$parts = explode(':', $this->getLength()->format('H:i:s'));

		return $parts[0] * 3600 + $parts[1] * 60 + $parts[2];
	}

	/**
	 * Get length as DateInterval
	 *
	 * @return \DateInterval
	 */
	public function getDateInterval() {
		return new \DateInterval($this->getISODuration());
	}

	/**
	 * Get length as ISO duration
	 *
	 * @return string
	 */
	public function getISODuration() {
		$iso = preg_replace('/(?<=[THMS])0+[HMS]/', '$1', $this->length->format('\P\TG\Hi\Ms\S'));

		if ($iso === 'PT') {
			$iso = 'PT0S';
		}

		return $iso;
	}

	/**
	 * Set extra
	 *
	 * @param array $extra
	 * @return ScheduleItem
	 */
	public function setExtra(array $extra) {
		foreach ($extra as $key => $value) {
			if (mb_strlen(trim($value)) === 0) {
				unset($extra[$key]);
			}
		}

		ksort($extra);
		$this->extra = json_encode($extra);

		return $this;
	}

	/**
	 * Get extra
	 *
	 * @return array
	 */
	public function getExtra() {
		return json_decode($this->extra, true);
	}

	/**
	 * Set schedule
	 *
	 * @param \horaro\Library\Entity\Schedule $schedule
	 * @return ScheduleItem
	 */
	public function setSchedule(Schedule $schedule) {
		$this->schedule = $schedule;

		return $this;
	}

	/**
	 * Get schedule
	 *
	 * @return \horaro\Library\Entity\Schedule
	 */
	public function getSchedule() {
		return $this->schedule;
	}

	public function getWidth($columns) {
		$len   = 0;
		$extra = $this->getExtra();

		foreach ($columns as $idx => $column) {
			if (isset($extra[$column->getId()]) && mb_strlen(trim($extra[$column->getId()])) > 0) {
				$len = $idx;
			}
		}

		return $len;
	}

	public function getOptions(ScheduleColumn $optionsCol = null) {
		if ($optionsCol === null) {
			$optionsCol = $this->getSchedule()->getOptionsColumn();

			if ($optionsCol === null) {
				return null;
			}
		}

		$colID   = $optionsCol->getID();
		$extra   = $this->getExtra();
		$options = null;

		if (isset($extra[$colID])) {
			$decoded = @json_decode($extra[$colID], false, 5);

			if (json_last_error() === JSON_ERROR_NONE && $decoded instanceof \stdClass) {
				$options = (array) $decoded;
			}
		}

		return $options;
	}

	public function getSetupTime(ScheduleColumn $optionsCol = null) {
		$options = $this->getOptions($optionsCol);

		if (!empty($options['setup'])) {
			try {
				$parser = new ReadableTime();
				return $parser->parse(trim($options['setup']));
			}
			catch (\InvalidArgumentException $e) {
				// ignore bad user input
			}
		}

		return null;
	}

	/**
	 * Get setup time in seconds
	 *
	 * @return int
	 */
	public function getSetupTimeInSeconds(ScheduleColumn $optionsCol = null) {
		return ReadableTime::dateTimeToSeconds($this->getSetupTime($optionsCol));
	}

	/**
	 * Get setup time as ISO duration
	 *
	 * @return string
	 */
	public function getSetupTimeISODuration(ScheduleColumn $optionsCol = null) {
		return ReadableTime::dateTimeToISODuration($this->getSetupTime($optionsCol));
	}

	/**
	 * Get setup time as DateInterval
	 *
	 * @return \DateInterval
	 */
	public function getSetupTimeDateInterval(ScheduleColumn $optionsCol = null) {
		return ReadableTime::dateTimeToDateInterval($this->getSetupTime($optionsCol));
	}

	/**
	 * Set scheduled
	 *
	 * @param \DateTime $scheduled
	 * @return Schedule
	 */
	public function setScheduled(\DateTime $scheduled) {
		$this->scheduled = clone $scheduled;

		return $this;
	}

	/**
	 * Get scheduled
	 *
	 * @return \DateTime
	 */
	public function getScheduled(\DateTimeZone $timezone = null) {
		$scheduled = clone $this->scheduled;

		if ($timezone) {
			$scheduled->setTimezone($timezone);
		}

		return $scheduled;
	}

	/**
	 * Get scheduled
	 *
	 * @return \DateTime
	 */
	public function getScheduledEnd(\DateTimeZone $timezone = null) {
		if ($this->scheduled === null) {
			throw new \LogicException('Can only determine the scheduled end if the schedule start has been set.');
		}

		$scheduled = clone $this->scheduled;
		$scheduled->add($this->getDateInterval());

		if ($timezone) {
			$scheduled->setTimezone($timezone);
		}

		return $scheduled;
	}
}
