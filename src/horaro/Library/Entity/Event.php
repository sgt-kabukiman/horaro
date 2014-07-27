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
 * Event
 */
class Event {
	/**
	 * @var integer
	 */
	private $id;

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var string
	 */
	private $slug;

	/**
	 * @var string
	 */
	private $website;

	/**
	 * @var string
	 */
	private $twitter;

	/**
	 * @var string
	 */
	private $twitch;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 */
	private $schedules;

	/**
	 * @var \horaro\Library\Entity\Team
	 */
	private $team;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->schedules = new \Doctrine\Common\Collections\ArrayCollection();
	}

	/**
	 * Get id
	 *
	 * @return integer
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Set name
	 *
	 * @param string $name
	 * @return Event
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
	 * Set slug
	 *
	 * @param string $slug
	 * @return Event
	 */
	public function setSlug($slug) {
		$this->slug = $slug;

		return $this;
	}

	/**
	 * Get slug
	 *
	 * @return string
	 */
	public function getSlug() {
		return $this->slug;
	}

	/**
	 * Set website
	 *
	 * @param string $website
	 * @return Event
	 */
	public function setWebsite($website) {
		$this->website = $website;

		return $this;
	}

	/**
	 * Get website
	 *
	 * @return string
	 */
	public function getWebsite() {
		return $this->website;
	}

	/**
	 * Set twitter
	 *
	 * @param string $twitter
	 * @return Event
	 */
	public function setTwitter($twitter) {
		$this->twitter = $twitter;

		return $this;
	}

	/**
	 * Get twitter
	 *
	 * @return string
	 */
	public function getTwitter() {
		return $this->twitter;
	}

	/**
	 * Set twitch
	 *
	 * @param string $twitch
	 * @return Event
	 */
	public function setTwitch($twitch) {
		$this->twitch = $twitch;

		return $this;
	}

	/**
	 * Get twitch
	 *
	 * @return string
	 */
	public function getTwitch() {
		return $this->twitch;
	}

	/**
	 * Add schedule
	 *
	 * @param \horaro\Library\Entity\Schedule $schedule
	 * @return Event
	 */
	public function addSchedule(Schedule $schedule) {
		$this->schedules[] = $schedule;

		return $this;
	}

	/**
	 * Remove schedule
	 *
	 * @param \horaro\Library\Entity\Schedule $schedule
	 */
	public function removeSchedule(Schedule $schedule) {
		$this->schedules->removeElement($schedule);
	}

	/**
	 * Get schedules
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getSchedules() {
		return $this->schedules;
	}

	/**
	 * Set team
	 *
	 * @param \horaro\Library\Entity\Team $team
	 * @return Event
	 */
	public function setTeam(Team $team) {
		$this->team = $team;

		return $this;
	}

	/**
	 * Get team
	 *
	 * @return \horaro\Library\Entity\Team
	 */
	public function getTeam() {
		return $this->team;
	}
}
