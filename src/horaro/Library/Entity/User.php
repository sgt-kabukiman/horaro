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
	private $role;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 */
	private $teams;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->teams = new \Doctrine\Common\Collections\ArrayCollection();
	}

	public function getName() {
		return $this->display_name ?: $this->login;
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
		$this->display_name = $displayName;

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
}
