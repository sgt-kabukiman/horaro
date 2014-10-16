<?php
/*
 * Copyright (c) 2014, Sgt. Kabukiman, https://bitbucket.org/sgt-kabukiman/
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Validator\Admin\Utils;

use horaro\Library\Configuration;
use horaro\Library\PasswordEncoder;
use horaro\WebApp\Validator\BaseValidator;

class ConfigValidator extends BaseValidator {
	protected $languages;
	protected $defaultLanguage;
	protected $themes;
	protected $defaultTheme;

	public function __construct(array $languages, $defaultLanguage, array $themes, $defaultTheme) {
		$this->languages       = $languages;
		$this->defaultLanguage = $defaultLanguage;
		$this->themes          = $themes;
		$this->defaultTheme    = $defaultTheme;
	}

	public function validate(array $config, Configuration $ref) {
		$this->result = ['_errors' => false];

		$this->setFilteredValue('bcrypt_cost',         $this->validateBcryptCost($config['bcrypt_cost'], $ref));
		$this->setFilteredValue('cookie_lifetime',     $this->validateCookieLifetime($config['cookie_lifetime'], $ref));
		$this->setFilteredValue('csrf_token_name',     $this->validateCsrfTokenName($config['csrf_token_name'], $ref));
		$this->setFilteredValue('default_event_theme', $this->validateDefaultEventTheme($config['default_event_theme'], $ref));
		$this->setFilteredValue('default_language',    $this->validateDefaultLanguage($config['default_language'], $ref));
		$this->setFilteredValue('max_events',          $this->validateMaxEvents($config['max_events'], $ref));
		$this->setFilteredValue('max_schedule_items',  $this->validateMaxScheduleItems($config['max_schedule_items'], $ref));
		$this->setFilteredValue('max_schedules',       $this->validateMaxSchedules($config['max_schedules'], $ref));
		$this->setFilteredValue('max_users',           $this->validateMaxUsers($config['max_users'], $ref));
		$this->setFilteredValue('sentry_dsn',          $this->validateSentryDSN($config['sentry_dsn'], $ref));

		return $this->result;
	}

	public function validateBcryptCost($cost, Configuration $ref) {
		$cost = (int) $cost;

		try {
			$encoder = new PasswordEncoder($cost);
		}
		catch (\InvalidArgumentException $e) {
			$this->addError('bcrypt_cost', $e->getMessage());
			$cost = $ref['bcrypt_cost'];
		}

		return $cost;
	}

	public function validateCookieLifetime($ttl, Configuration $ref) {
		$ttl = (int) $ttl;

		if ($ttl < 600) {
			$this->addError('cookie_lifetime', 'Values lower than 600 (10 minutes) will seriously affect usability.');
			$ttl = $ref['cookie_lifetime'];
		}

		return $ttl;
	}

	public function validateCsrfTokenName($name, Configuration $ref) {
		$name = trim($name);

		if (!preg_match('/^[a-z0-9_-]+$/i', $name)) {
			$this->addError('csrf_token_name', 'The token name contains invalid characters. Use only a-z, 0-9, dash and underscore.');
			$name = $ref['csrf_token_name'];
		}
		elseif (strlen($name) === 0) {
			$this->addError('csrf_token_name', 'The token name cannot be empty.');
			$name = $ref['csrf_token_name'];
		}

		return $name;
	}

	public function validateDefaultLanguage($language) {
		if (!is_string($language)) {
			$this->addError('default_language', 'Malformed language.');
			return $this->defaultLanguage;
		}

		$language = strtolower(trim($language));

		if (!in_array($language, $this->languages, true)) {
			$this->addError('default_language', 'Unknown language chosen.');
			return $this->defaultLanguage;
		}

		return $language;
	}

	public function validateDefaultEventTheme($theme) {
		if (!is_string($theme)) {
			$this->addError('default_theme', 'Malformed theme.');
			return $this->defaultTheme;
		}

		$theme = strtolower(trim($theme));

		if (!in_array($theme, $this->themes, true)) {
			$this->addError('default_theme', 'Unknown theme chosen.');
			return $this->defaultTheme;
		}

		return $theme;
	}

	public function validateMaxEvents($maxEvents, Configuration $ref) {
		$maxEvents = (int) $maxEvents;

		if ($maxEvents < 1) {
			$this->addError('max_events', 'Setting this to zero effectively disables all accounts.');
			$maxEvents = $ref['max_events'];
		}
		elseif ($maxEvents > 999) {
			$this->addError('max_events', 'More than 999 seems a bit excessive, don\'t you think?');
			$maxEvents = $ref['max_events'];
		}

		return $maxEvents;
	}

	public function validateMaxSchedules($maxSchedules, Configuration $ref) {
		$maxSchedules = (int) $maxSchedules;

		if ($maxSchedules < 1) {
			$this->addError('max_schedules', 'Setting this to zero makes having events pointless.');
			$maxSchedules = $ref['max_schedules'];
		}
		elseif ($maxSchedules > 999) {
			$this->addError('max_schedules', 'More than 999 seems a bit excessive, don\'t you think?');
			$maxSchedules = $ref['max_schedules'];
		}

		return $maxSchedules;
	}

	public function validateMaxScheduleItems($maxItems, Configuration $ref) {
		$maxItems = (int) $maxItems;

		if ($maxItems < 1) {
			$this->addError('max_schedule_items', 'Setting this to zero makes this whole operation pointless.');
			return $ref['max_schedule_items'];
		}

		if ($maxItems > 999) {
			$this->addError('max_schedule_items', 'More than 999 seems a bit excessive, don\'t you think?');
			return $ref['max_schedule_items'];
		}

		return $maxItems;
	}

	public function validateMaxUsers($maxUsers, Configuration $ref) {
		$maxUsers = (int) $maxUsers;

		if ($maxUsers < 1) {
			$this->addError('max_users', 'Your very existence makes setting this to zero a contradiction.');
			return $ref['max_users'];
		}

		return $maxUsers;
	}

	public function validateSentryDSN($dsn, Configuration $ref) {
		if (!$dsn) return $dsn;

		$parts = @parse_url($dsn);

		if (!isset($parts['scheme']) || $parts['scheme'] !== 'https') {
			$this->addError('sentry_dsn', 'The DSN must be a full URI using HTTPS.');
			return $ref['sentry_dsn'];
		}

		return $dsn;
	}
}
