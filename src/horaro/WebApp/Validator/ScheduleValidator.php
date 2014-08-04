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

use horaro\Library\Entity\Event;
use horaro\Library\Entity\Schedule;

class ScheduleValidator extends BaseValidator {
	protected $repo;

	public function __construct($scheduleRepo) {
		$this->repo = $scheduleRepo;
	}

	public function validate(array $schedule, Event $event, Schedule $ref = null) {
		$this->result = ['_errors' => false];

		$this->setFilteredValue('name',     $this->validateName($schedule['name'], $event, $ref));
		$this->setFilteredValue('slug',     $this->validateSlug($schedule['slug'], $event, $ref));
		$this->setFilteredValue('timezone', $this->validateTimezone($schedule['timezone'], $event, $ref));
		$this->setFilteredValue('twitch',   $this->validateTwitchAccount($schedule['twitch'], $event, $ref));
		$this->setFilteredValue('start',    $this->validateStart($schedule['start_date'], $schedule['start_time'], $event, $ref));

		return $this->result;
	}

	public function validateName($name, Event $event, Schedule $ref = null) {
		$name = trim($name);

		if (mb_strlen($name) === 0) {
			$this->addError('name', 'The name cannot be empty.');
		}

		return $name;
	}

	public function validateSlug($slug, Event $event, Schedule $ref = null) {
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
		else {
			$existing = $this->repo->findOneBy(['event' => $event, 'slug' => $slug]);

			if ($existing && (!$ref || $existing->getId() !== $ref->getId())) {
				$this->addError('slug', 'This slug is already in use, sorry.');
			}
		}

		return $slug;
	}

	public function validateTimezone($timezone, Event $event, Schedule $ref = null) {
		$timezone  = trim($timezone);
		$timezones = \DateTimeZone::listIdentifiers();

		if (!in_array($timezone, $timezones, true)) {
			$this->addError('timezone', 'Your selected timezone is invalid.');

			return 'UTC';
		}

		return $timezone;
	}

	public function validateTwitchAccount($account, Event $event, Schedule $ref = null) {
		$account = trim($account);

		if (mb_strlen($account) > 0 && !preg_match('/^[a-zA-Z0-9_-]+$/', $account)) {
			$this->addError('twitch', 'The Twitch stream contains invalid characters.');
		}

		return $account === '' ? null : $account;
	}

	public function validateStart($date, $time, Event $event, Schedule $ref = null) {
		$this->setFilteredValue('start_date', $date);
		$this->setFilteredValue('start_time', $time);

		$okay = true;

		if (strlen(trim($date)) === 0) {
			$this->addError('start', 'No start date given.');
			$okay = false;
		}
		else {
			$d = \DateTime::createFromFormat('Y-m-d', $date);

			if (!$d) {
				$this->addError('start', 'The given start date is malformed.');
				$okay = false;
			}
			else {
				$year = $d->format('Y');
				$now  = date('Y');

				if ($year < $now-2 || $year > $now+2) {
					$this->addError('start', 'The given start date is out of range.');
					$okay = false;
				}
			}
		}

		if (strlen(trim($time)) === 0) {
			$this->addError('start', 'No start time given.');
			$okay = false;
		}
		else {
			$t = \DateTime::createFromFormat('G:i', $time);

			if (!$t) {
				$this->addError('start', 'The given start time is malformed.');
				$okay = false;
			}
		}

		return $okay ? \DateTime::createFromFormat('Y-m-d G:i', "$date $time") : null;
	}
}
