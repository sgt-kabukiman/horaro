<?php
/*
 * Copyright (c) 2014, Sgt. Kabukiman, https://bitbucket.org/sgt-kabukiman/
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

	/**
	 * Get length
	 *
	 * @return \DateTime
	 */
	public function getLength() {
		return $this->length;
	}

	/**
	 * Set extra
	 *
	 * @param string $extra
	 * @return ScheduleItem
	 */
	public function setExtra($extra) {
		$this->extra = $extra;

		return $this;
	}

	/**
	 * Get extra
	 *
	 * @return string
	 */
	public function getExtra() {
		return $this->extra;
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
}
