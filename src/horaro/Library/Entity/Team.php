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
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Team
 */
class Team {
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
	 * @var \Doctrine\Common\Collections\Collection
	 */
	private $users;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 */
	private $events;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->users  = new ArrayCollection();
		$this->events = new ArrayCollection();
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
	 * @return Team
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
	 * @return Team
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
	 * Add user
	 *
	 * @param \horaro\Library\Entity\UserTeamRelation $user
	 * @return Team
	 */
	public function addUser(UserTeamRelation $user) {
		$this->users[] = $users;

		return $this;
	}

	/**
	 * Remove user
	 *
	 * @param \horaro\Library\Entity\UserTeamRelation $user
	 */
	public function removeUser(UserTeamRelation $user) {
		$this->users->removeElement($user);
	}

	/**
	 * Get users
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getUsers() {
		return $this->users;
	}

	/**
	 * Add event
	 *
	 * @param \horaro\Library\Entity\Event $event
	 * @return Team
	 */
	public function addEvent(Event $event) {
		$this->events[] = $event;

		return $this;
	}

	/**
	 * Remove event
	 *
	 * @param \horaro\Library\Entity\Event $event
	 */
	public function removeEvent(Event $event) {
		$this->events->removeElement($event);
	}

	/**
	 * Get events
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getEvents() {
		return $this->events;
	}
}
