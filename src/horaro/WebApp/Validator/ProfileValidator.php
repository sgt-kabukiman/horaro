<?php
/*
 * Copyright (c) 2015, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Validator;

use horaro\Library\Entity\User;

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

	public function validatePasswordChange(array $profile, User $user) {
		$this->result = ['_errors' => false];

		$this->setFilteredValue('current',  $this->validateCurrentPassword($profile['current'], $user));
		$this->setFilteredValue('password', $this->validatePassword($profile['password'], $profile['password2']));

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

	public function validateCurrentPassword($given, User $user) {
		if (!is_string($given)) {
			$this->addError('current', 'Malformed current password.');
			return null;
		}

		$given = trim($given);

		if (!password_verify($given, $user->getPassword())) {
			$this->addError('current', 'Wrong current password given.');
		}

		return null;
	}

	public function validatePassword($a, $b) {
		$a = trim($a);
		$b = trim($b);

		if (mb_strlen($a) < 5) {
			$this->addError('password', 'Don\'t be that lazy and give at least 5 characters.');
		}

		if (strtolower($a) === 'secret123') {
			$this->addError('password', 'You just had to try it out, didn\'t you? Please choose something else.');
		}

		if ($a !== $b) {
			$this->addError('password', 'See, you already made your first typo. The passwords don\'t match.');
		}

		return $a;
	}
}
