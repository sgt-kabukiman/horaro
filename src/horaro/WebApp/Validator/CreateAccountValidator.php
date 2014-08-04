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

class CreateAccountValidator extends BaseValidator {
	protected $repo;

	public function __construct($userRepo) {
		$this->repo = $userRepo;
	}

	public function validate(array $account) {
		$this->result = ['_errors' => false];

		$this->setFilteredValue('login',        $this->validateLogin($account['login']));
		$this->setFilteredValue('password',     $this->validatePassword($account['password'], $account['password2']));
		$this->setFilteredValue('display_name', $this->validateDisplayName($account['display_name']));

		return $this->result;
	}

	public function validateLogin($login) {
		$login = trim($login);

		if (!preg_match('/^[a-zA-Z0-9_-]+$/u', $login)) {
			$this->addError('login', 'Your username must use only letters, numbers, underscores or dashes.');
		}
		else {
			$login = strtolower($login);
			$user  = $this->repo->findByLogin($login);

			if ($user) {
				$this->addError('login', 'This username has already been taken.');
			}
		}

		return $login;
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

	public function validateDisplayName($name) {
		return trim($name);
	}
}
