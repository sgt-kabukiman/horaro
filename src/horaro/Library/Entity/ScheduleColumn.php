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
 * ScheduleColumn
 */
class ScheduleColumn {
	/**
	 * @var integer
	 */
	private $id;

	/**
	 * @var integer
	 */
	private $position;

	/**
	 * @var string
	 */
	private $name;

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
	 * Set name
	 *
	 * @param string $name
	 * @return ScheduleColumn
	 */
	public function setName($name) {
		$this->name = $name;

		return $this;
	}

	/**
	 * Get name
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Set schedule
	 *
	 * @param \horaro\Library\Entity\Schedule $schedule
	 * @return ScheduleColumn
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
