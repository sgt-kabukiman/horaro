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

use horaro\Library\Entity\Event;

class EventValidator extends BaseValidator {
	protected $repo;
	protected $themes;
	protected $defaultTheme;

	public function __construct($eventRepo, array $themes, $defaultTheme) {
		$this->repo         = $eventRepo;
		$this->themes       = $themes;
		$this->defaultTheme = $defaultTheme;
	}

	public function validate(array $event, Event $ref = null) {
		$this->result = ['_errors' => false];

		$this->setFilteredValue('name',    $this->validateName($event['name'], $ref));
		$this->setFilteredValue('slug',    $this->validateSlug($event['slug'], $ref));
		$this->setFilteredValue('website', $this->validateWebsite($event['website'], $ref));
		$this->setFilteredValue('twitter', $this->validateTwitterAccount($event['twitter'], $ref));
		$this->setFilteredValue('twitch',  $this->validateTwitchAccount($event['twitch'], $ref));
		$this->setFilteredValue('theme',   $this->validateTheme($event['theme'], $ref));
		$this->setFilteredValue('secret',  $this->validateSecret($event['secret']));

		return $this->result;
	}

	public function validateDescription($description) {
		$this->result = ['_errors' => false];

		$description = trim($description);
		$this->setFilteredValue('description', $description);

		if (mb_strlen($description) > 16*1024) {
			$this->addError('description', 'The description cannot be longer than 16k characters.');
		}

		return $this->result;
	}

	public function validateName($name, Event $ref = null) {
		$name = trim($name);

		if (mb_strlen($name) === 0) {
			$this->addError('name', 'The name cannot be empty.');
		}

		return $name;
	}

	public function validateSlug($slug, Event $ref = null) {
		$slug = trim($slug);

		if (!preg_match('/^[a-z0-9-]{2,}$/', $slug)) {
			$this->addError('slug', 'You can only use lowercase letters, numbers and dashes for a slug.');
		}
		elseif (preg_match('/^-+$/', $slug)) {
			$this->addError('slug', 'The slug cannot be all dashes only.');
		}
		elseif (preg_match('/^-|-$/', $slug)) {
			$this->addError('slug', 'The slug cannot start or end with a dash.');
		}
		elseif (in_array($slug, ['-', 'assets'], true)) {
			$this->addError('slug', 'This slug is reserved for internal usage.');
		}
		else {
			$existing = $this->repo->findOneBySlug($slug);

			if ($existing && (!$ref || $existing->getId() !== $ref->getId())) {
				$this->addError('slug', 'This slug is already in use, sorry.');
			}
		}

		return $slug;
	}

	public function validateWebsite($website, Event $ref = null) {
		$website = trim($website);

		if (mb_strlen($website) > 0) {
			$parts = parse_url($website);

			if (!isset($parts['scheme']) || !in_array($parts['scheme'], ['http', 'https'], true)) {
				$this->addError('website', 'The website must use either HTTP or HTTPS.');
			}
		}

		return $website === '' ? null : $website;
	}

	public function validateTwitterAccount($account, Event $ref = null) {
		$account = trim($account);

		if (mb_strlen($account) > 0) {
			if (!preg_match('/^@?([a-zA-Z0-9-_]+)$/', $account, $match)) {
				$this->addError('twitter', 'The Twitter account name contains invalid characters.');
			}
			else {
				$account = $match[1];
			}
		}

		return $account === '' ? null : $account;
	}

	public function validateTwitchAccount($account, Event $ref = null) {
		$account = trim($account);

		if (mb_strlen($account) > 0 && !preg_match('/^[a-zA-Z0-9_-]+$/', $account)) {
			$this->addError('twitch', 'The Twitch account name contains invalid characters.');
		}

		return $account === '' ? null : $account;
	}

	public function validateTheme($theme, Event $event = null) {
		$theme = trim($theme);

		if (!in_array($theme, $this->themes, true)) {
			$this->addError('theme', 'Your selected theme is invalid.');

			return $this->defaultTheme;
		}

		return $theme;
	}

	public function validateSecret($secret) {
		$secret = trim($secret);

		if (mb_strlen($secret) > 20) {
			$this->addError('secret', 'The secret can only be up to 20 characters in length.');
		}

		if (mb_strlen($secret) > 0 && !preg_match('/^[a-zA-Z0-9_-]+$/', $secret)) {
			$this->addError('secret', 'The secret can only use the characters a-z, 0-9, dash and underscore.');
		}

		return $secret === '' ? null : $secret;
	}
}
