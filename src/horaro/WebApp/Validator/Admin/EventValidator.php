<?php
/*
 * Copyright (c) 2015, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Validator\Admin;

use horaro\Library\Entity\Event;
use horaro\WebApp\Validator\EventValidator as BaseEventValidator;

class EventValidator extends BaseEventValidator {
	public function validate(array $event, Event $ref = null) {
		parent::validate($event, $ref);

		$this->setFilteredValue('max_schedules', $this->validateMaxSchedules($event['max_schedules'], $ref));

		return $this->result;
	}

	public function validateMaxSchedules($maxSchedules, Event $event) {
		$maxSchedules = (int) $maxSchedules;
		$schedules    = $event->getSchedules()->count();

		if ($maxSchedules < $schedules) {
			$this->addError('max_schedules', 'Cannot set the limit lower than the current value.');
			return $event->getMaxSchedules();
		}

		if ($maxSchedules > 999) {
			$this->addError('max_schedules', 'More than 999 seems a bit excessive, don\'t you think?');
			return $event->getMaxSchedules();
		}

		return $maxSchedules;
	}
}
