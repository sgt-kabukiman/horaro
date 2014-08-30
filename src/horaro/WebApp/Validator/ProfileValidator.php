<?php
/*
 * Copyright (c) 2014, Sgt. Kabukiman, https://bitbucket.org/sgt-kabukiman/
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Validator;

class ProfileValidator extends BaseValidator {
	protected $languages;
	protected $default;

	public function __construct(array $languages, $default) {
		$this->languages = $languages;
		$this->default   = $default;
	}

	public function validate(array $profile) {
		$this->result = ['_errors' => false];

		$this->setFilteredValue('display_name', $this->validateDisplayName($profile['display_name']));
		$this->setFilteredValue('language',     $this->validateLanguage($profile['language']));
		$this->setFilteredValue('gravatar',     $this->validateGravatar($profile['gravatar']));

		return $this->result;
	}

	public function validateDisplayName($name) {
		return trim($name);
	}

	public function validateLanguage($language) {
		if (!is_string($language)) {
			$this->addError('language', 'Malformed language.');
			return $this->default;
		}

		$language = strtolower(trim($language));

		if (!in_array($language, $this->languages, true)) {
			$this->addError('language', 'Unknown language chosen.');
			return $this->default;
		}

		return $language;
	}

	public function validateGravatar($gravatar) {
		if (!is_string($gravatar)) {
			$this->addError('gravatar', 'Malformed gravatar info.');
			return null;
		}

		$gravatar = strtolower(trim($gravatar));

		// it's already a hash
		if (preg_match('/^[0-9a-f]{32}$/', $gravatar)) {
			return $gravatar;
		}

		if (mb_strlen($gravatar) === 0) {
			return null;
		}

		return md5($gravatar);
	}
}
