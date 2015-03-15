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

class LoginValidator extends BaseValidator {
	protected $repo;

	public function __construct($userRepo) {
		$this->repo = $userRepo;
	}

	public function validate(array $login) {
		$this->result = ['_errors' => false];

		$this->setFilteredValue('form',     true);
		$this->setFilteredValue('login',    $this->validateLogin($login['login']));
		$this->setFilteredValue('password', $this->validatePassword($login['password']));

		return $this->result;
	}

	public function validateLogin($login) {
		$login = trim($login);

		if (!preg_match('/^[a-zA-Z0-9_-]+$/u', $login)) {
			$this->addError('login', 'Malformed username.');
		}
		else {
			$login = strtolower($login);
			$user  = $this->repo->findOneByLogin($login);

			if (!$user || $user->getRole() === 'ROLE_GHOST') {
				$this->addError('form', 'Invalid login credentials.');
			}

			$this->result['_user'] = $user;
		}

		return $login;
	}

	public function validatePassword($password) {
		$password = trim($password);

		if (empty($this->result['_user'])) {
			return $password;
		}

		if (!password_verify($password, $this->result['_user']->getPassword())) {
			$this->addError('form', 'Invalid login credentials.');
		}

		return $password;
	}
}
