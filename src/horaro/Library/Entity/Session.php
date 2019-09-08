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

/**
 * Session
 */
class Session {
	/**
	 * @var string
	 */
	private $id;

	/**
	 * @var string
	 */
	private $data;

	/**
	 * @var integer
	 */
	private $lifetime;

	/**
	 * @var integer
	 */
	private $mtime;

	/**
	 * Set id
	 *
	 * @param string $id
	 * @return Session
	 */
	public function setId($id) {
		$this->id = $id;

		return $this;
	}

	/**
	 * Get id
	 *
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Set data
	 *
	 * @param string $data
	 * @return Session
	 */
	public function setData($data) {
		$this->data = $data;

		return $this;
	}

	/**
	 * Get data
	 *
	 * @return string
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Set lifetime
	 *
	 * @param integer $lifetime
	 * @return Session
	 */
	public function setLifetime($lifetime) {
		$this->lifetime = $lifetime;

		return $this;
	}

	/**
	 * Get lifetime
	 *
	 * @return integer
	 */
	public function getLifetime() {
		return $this->lifetime;
	}

	/**
	 * Set mtime
	 *
	 * @param integer $mtime
	 * @return Session
	 */
	public function setMtime($mtime) {
		$this->mtime = $mtime;

		return $this;
	}

	/**
	 * Get mtime
	 *
	 * @return integer
	 */
	public function getMtime() {
		return $this->mtime;
	}
}
