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
use Doctrine\Common\Collections\ArrayCollection;

/**
 * User
 */
class User {
	/**
	 * @var integer
	 */
	private $id;

	/**
	 * @var string
	 */
	private $login;

	/**
	 * @var string
	 */
	private $password;

	/**
	 * @var string
	 */
	private $display_name;

	/**
	 * @var string
	 */
	private $gravatar_hash;

	/**
	 * @var string
	 */
	private $language;

	/**
	 * @var string
	 */
	private $role;

	/**
	 * @var string
	 */
	private $twitch_oauth;

	/**
	 * @var \DateTime
	 */
	private $created_at;

	/**
	 * @var integer
	 */
	private $max_events;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 */
	private $teams;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 */
	private $events;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->teams      = new ArrayCollection();
		$this->events     = new ArrayCollection();
		$this->created_at = new \DateTime('now', new \DateTimeZone('UTC'));
	}

	public function getName() {
		return $this->display_name === null ? $this->login : $this->display_name;
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
	 * Set login
	 *
	 * @param string $login
	 * @return User
	 */
	public function setLogin($login) {
		$this->login = $login;

		return $this;
	}

	/**
	 * Get login
	 *
	 * @return string
	 */
	public function getLogin() {
		return $this->login;
	}

	public function isOAuthAccount() {
		return preg_match('/^oauth:/', $this->getLogin());
	}

	/**
	 * Set password
	 *
	 * @param string $password
	 * @return User
	 */
	public function setPassword($password) {
		$this->password = $password;

		return $this;
	}

	/**
	 * Get password
	 *
	 * @return string
	 */
	public function getPassword() {
		return $this->password;
	}

	/**
	 * Set display_name
	 *
	 * @param string $displayName
	 * @return User
	 */
	public function setDisplayName($displayName) {
		$displayName = trim($displayName);

		$this->display_name = mb_strlen($displayName) === 0 ? null : $displayName;

		return $this;
	}

	/**
	 * Get display_name
	 *
	 * @return string
	 */
	public function getDisplayName() {
		return $this->display_name;
	}

	/**
	 * Set gravatar_hash
	 *
	 * @param string $hash
	 * @return User
	 */
	public function setGravatarHash($hash) {
		$hash = strtolower(trim($hash));

		$this->gravatar_hash = preg_match('/^[0-9a-f]{32}$/', $hash) ? $hash : null;

		return $this;
	}

	/**
	 * Get gravatar_hash
	 *
	 * @return string
	 */
	public function getGravatarHash() {
		return $this->gravatar_hash;
	}

	/**
	 * Set language
	 *
	 * @param string $language
	 * @return User
	 */
	public function setLanguage($language) {
		$language = strtolower($language);

		$this->language = strlen($language) > 0 ? $language : null;

		return $this;
	}

	/**
	 * Get language
	 *
	 * @return string
	 */
	public function getLanguage() {
		return $this->language;
	}

	/**
	 * Set role
	 *
	 * @param string $role
	 * @return User
	 */
	public function setRole($role) {
		$this->role = $role;

		return $this;
	}

	/**
	 * Get role
	 *
	 * @return string
	 */
	public function getRole() {
		return $this->role;
	}

	/**
	 * Set twitch_oauth
	 *
	 * @param string $twitchUserID
	 * @return User
	 */
	public function setTwitchOAuth($twitchUserID) {
		$twitchUserID = trim($twitchUserID);

		$this->twitch_oauth = strlen($twitchUserID) === 0 ? null : $twitchUserID;

		return $this;
	}

	/**
	 * Get twitch_oauth
	 *
	 * @return string
	 */
	public function getTwitchOAuth() {
		return $this->twitch_oauth;
	}

	public function getTwitchUsername() {
		preg_match('/^oauth:twitch:(.+)$/', $this->twitch_oauth, $match);

		return $match ? $match[1] : null;
	}

	/**
	 * Set created_at
	 *
	 * @param \DateTime $createdAt
	 * @return Schedule
	 */
	public function setCreatedAt($createdAt) {
		$this->created_at = $createdAt;

		return $this;
	}

	/**
	 * Get created_at (UTC)
	 *
	 * @return \DateTime
	 */
	public function getCreatedAt() {
		$tmpFrmt = 'Y-m-d H:i:s';

		return \DateTime::createFromFormat($tmpFrmt, $this->created_at->format($tmpFrmt), new \DateTimeZone('UTC')); // "inject" proper timezone
	}

	/**
	 * Get created_at with the proper local timezone
	 *
	 * @return \DateTime
	 */
	public function getLocalCreatedAt() {
		$local = $this->getCreatedAt();
		$local->setTimezone($this->getTimezoneInstance());

		return $local;
	}

	/**
	 * Set max events
	 *
	 * @param integer $maxEvents
	 * @return User
	 */
	public function setMaxEvents($maxEvents) {
		$this->max_events = $maxEvents < 0 ? 0 : (int) $maxEvents;

		return $this;
	}

	/**
	 * Get max events
	 *
	 * @return integer
	 */
	public function getMaxEvents() {
		return $this->max_events;
	}

	/**
	 * Add team
	 *
	 * @param \horaro\Library\Entity\UserTeamRelation $team
	 * @return User
	 */
	public function addTeam(UserTeamRelation $team) {
		$this->teams[] = $team;

		return $this;
	}

	/**
	 * Remove team
	 *
	 * @param \horaro\Library\Entity\UserTeamRelation $team
	 */
	public function removeTeam(UserTeamRelation $team) {
		$this->teams->removeElement($team);
	}

	/**
	 * Get teams
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getTeams() {
		return $this->teams;
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
