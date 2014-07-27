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
 * UserTeamRelation
 */
class UserTeamRelation {
	/**
	 * @var integer
	 */
	private $user_id;

	/**
	 * @var integer
	 */
	private $team_id;

	/**
	 * @var string
	 */
	private $role;

	/**
	 * @var \horaro\Library\Entity\User
	 */
	private $user;

	/**
	 * @var \horaro\Library\Entity\User
	 */
	private $team;

	/**
	 * Set user_id
	 *
	 * @param integer $userId
	 * @return UserTeamRelation
	 */
	public function setUserId($userId) {
		$this->user_id = $userId;

		return $this;
	}

	/**
	 * Get user_id
	 *
	 * @return integer
	 */
	public function getUserId() {
		return $this->user_id;
	}

	/**
	 * Set team_id
	 *
	 * @param integer $teamId
	 * @return UserTeamRelation
	 */
	public function setTeamId($teamId) {
		$this->team_id = $teamId;

		return $this;
	}

	/**
	 * Get team_id
	 *
	 * @return integer
	 */
	public function getTeamId() {
		return $this->team_id;
	}

	/**
	 * Set role
	 *
	 * @param string $role
	 * @return UserTeamRelation
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
	 * Set user
	 *
	 * @param \horaro\Library\Entity\User $user
	 * @return UserTeamRelation
	 */
	public function setUser(User $user) {
		$this->user = $user;

		return $this;
	}

	/**
	 * Get user
	 *
	 * @return \horaro\Library\Entity\User
	 */
	public function getUser() {
		return $this->user;
	}

	/**
	 * Set team
	 *
	 * @param \horaro\Library\Entity\User $team
	 * @return UserTeamRelation
	 */
	public function setTeam(User $team) {
		$this->team = $team;

		return $this;
	}

	/**
	 * Get team
	 *
	 * @return \horaro\Library\Entity\User
	 */
	public function getTeam() {
		return $this->team;
	}
}
