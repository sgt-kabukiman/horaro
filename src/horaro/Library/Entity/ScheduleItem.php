<?php
/*
 * Copyright (c) 2015, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\Library\Entity;

use Doctrine\ORM\Mapping as ORM;

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
		return preg_replace('/(?<=[THMS])0+[HMS]/', '$1', $this->length->format('\P\TG\Hi\Ms\S'));
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

	/**
	 * Set scheduled
	 *
	 * @param \DateTime $scheduled
	 * @return Schedule
	 */
	public function setScheduled(\DateTime $scheduled) {
		$this->scheduled = $scheduled;

		return $this;
	}

	/**
	 * Get scheduled
	 *
	 * @return \DateTime
	 */
	public function getScheduled(\DateTimeZone $timezone = null) {
		if (!$timezone) {
			return $this->scheduled;
		}

		$scheduled = clone $this->scheduled;
		$scheduled->setTimezone($timezone);

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
