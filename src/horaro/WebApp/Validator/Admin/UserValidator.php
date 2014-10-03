<?php
/*
 * Copyright (c) 2014, Sgt. Kabukiman, https://bitbucket.org/sgt-kabukiman/
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Validator\Admin;

use horaro\Library\Entity\User;
use horaro\WebApp\Application;
use horaro\WebApp\Validator\BaseValidator;

class UserValidator extends BaseValidator {
	protected $languages;
	protected $default;
	protected $app;

	public function __construct(array $languages, $default, Application $app) {
		$this->languages = $languages;
		$this->default   = $default;
		$this->app       = $app;
	}

	public function validate(array $profile, User $user, User $editor) {
		$this->result = ['_errors' => false];

		$this->setFilteredValue('login',        $this->validateLogin($profile['login'], $user, $editor));
		$this->setFilteredValue('display_name', $this->validateDisplayName($profile['display_name'], $user, $editor));
		$this->setFilteredValue('language',     $this->validateLanguage($profile['language'], $user, $editor));
		$this->setFilteredValue('gravatar',     $this->validateGravatar($profile['gravatar'], $user, $editor));
		$this->setFilteredValue('max_events',   $this->validateMaxEvents($profile['max_events'], $user, $editor));
		$this->setFilteredValue('role',         $this->validateRole($profile['role'], $user, $editor));

		return $this->result;
	}

	public function validatePasswordChange(array $profile, User $user) {
		$this->result = ['_errors' => false];

		$this->setFilteredValue('password', $this->validatePassword($profile['password'], $profile['password2']));

		return $this->result;
	}

	public function validateLogin($login, User $user) {
		$login = trim($login);

		if (!preg_match('/^[a-zA-Z0-9_-]+$/u', $login)) {
			$this->addError('login', 'The username must use only letters, numbers, underscores or dashes.');
		}
		else {
			$login = strtolower($login);
			$em    = $this->app['entitymanager'];
			$repo  = $em->getRepository('horaro\Library\Entity\User');
			$u     = $repo->findOneByLogin($login);

			if ($u && $u->getId() !== $user->getId()) {
				$this->addError('login', 'This username has already been taken.');
			}
		}

		return $login;
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

	public function validateMaxEvents($maxEvents, User $user) {
		$maxEvents = (int) $maxEvents;
		$events    = $user->getEvents()->count();

		if ($maxEvents < $events) {
			$this->addError('max_events', 'Cannot set the limit lower than the current value.');
			return $user->getMaxEvents();
		}

		if ($maxEvents > 999) {
			$this->addError('max_events', 'More than 999 seems a bit excessive, don\'t you think?');
			return $user->getMaxEvents();
		}

		return $maxEvents;
	}

	public function validateRole($role, User $user, User $editor) {
		// forbid changing your own role
		if ($user->getId() === $editor->getId()) {
			return $user->getRole();
		}

		$rm = $this->app['rolemanager'];

		// cannot change superior's or colleague's roles
		if ($rm->userIsSuperior($user, $editor) || $rm->userIsColleague($user, $editor)) {
			return $user->getRole();
		}

		try {
			$roleWeight   = $rm->getWeight($role);
			$editorWeight = $rm->getWeight($editor->getRole());
		}
		catch (\Exception $e) {
			$this->addError('role', 'Unknown role given.');
			return $user->getRole();
		}

		// cannot give a role that's higher or the same as the editor's one
		if ($roleWeight >= $editorWeight) {
			$this->addError('role', 'You may not assign this role.');
			return $user->getRole();
		}

		return $role;
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
