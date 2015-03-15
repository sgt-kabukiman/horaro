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
use horaro\Library\Entity\Schedule;
use horaro\WebApp\Validator\ScheduleValidator as BaseScheduleValidator;

class ScheduleValidator extends BaseScheduleValidator {
	public function validate(array $schedule, Event $event, Schedule $ref = null) {
		parent::validate($schedule, $event, $ref);

		$this->setFilteredValue('max_items', $this->validateMaxItems($schedule['max_items'], $ref));

		return $this->result;
	}

	public function validateMaxItems($maxItems, Schedule $schedule) {
		$maxItems = (int) $maxItems;
		$items    = $schedule->getItems()->count();

		if ($maxItems < $items) {
			$this->addError('max_items', 'Cannot set the limit lower than the current value.');
			return $schedule->getMaxItems();
		}

		if ($maxItems > 999) {
			$this->addError('max_items', 'More than 999 seems a bit excessive, don\'t you think?');
			return $schedule->getMaxItems();
		}

		return $maxItems;
	}
}
