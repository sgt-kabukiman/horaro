<?php
/*
 * Copyright (c) 2019, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\Library\ObscurityCodec;

use horaro\Library\ObscurityCodec;
use Hashids\Hashids as VndHashids;

class Hashids implements ObscurityCodec {
	protected $secret;
	protected $minLength;
	protected $hashers;

	public function __construct($secret, $minLength) {
		$this->secret    = $secret;
		$this->minLength = $minLength;
		$this->hashers   = [];
	}

	public function encode($id, $entityType = null) {
		return $this->buildHasher($entityType)->encode($id);
	}

	public function decode($hash, $entityType = null) {
		$decoded = $this->buildHasher($entityType)->decode($hash);

		if (!is_array($decoded) || empty($decoded)) {
			return null;
		}

		return reset($decoded);
	}

	protected function buildHasher($entityType) {
		$secret = $this->secret;

		if ($entityType) {
			$secret .= ' / '.$entityType;
		}

		// not all characters in the secret are relevant, so by hashing we make sure
		// the added entity type has an actual effect on the hashing later
		$secret = md5($secret);

		if (!isset($this->hashers[$secret])) {
			$this->hashers[$secret] = new VndHashids($secret, $this->minLength, 'abcdefghijklmnopqrstuvwxyz1234567890');
		}

		return $this->hashers[$secret];
	}
}
