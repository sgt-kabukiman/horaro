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
 * Config
 */
class Config {
	/**
	 * @var string
	 */
	private $keyname;

	/**
	 * @var string
	 */
	private $value;

	/**
	 * Constructor
	 */
	public function __construct($key = null, $value = null) {
		$this->setKey($key);
		$this->setValue($value);
	}

	/**
	 * Set key
	 *
	 * @param string $key
	 * @return Config
	 */
	public function setKey($key) {
		$this->keyname = $key;

		return $this;
	}

	/**
	 * Get key
	 *
	 * @return string
	 */
	public function getKey() {
		return $this->keyname;
	}

	/**
	 * Set value
	 *
	 * @param string $value
	 * @return Config
	 */
	public function setValue($value) {
		$this->value = json_encode($value, JSON_UNESCAPED_SLASHES);

		return $this;
	}

	/**
	 * Get value
	 *
	 * @return string
	 */
	public function getValue() {
		return json_decode($this->value, true);
	}
}
