<?php
/*
 * Copyright (c) 2019, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Validator;

use horaro\Library\Entity\Schedule;
use horaro\Library\Entity\ScheduleColumn;

class ScheduleColumnValidator extends BaseValidator {
	public function validateNew(array $col, Schedule $schedule) {
		$this->result = ['_errors' => false];

		$this->setFilteredValue('name',   $this->validateName($col, $schedule, null));
		$this->setFilteredValue('hidden', $this->validateHidden($col, $schedule, null));

		return $this->result;
	}

	public function validateUpdate(array $col, ScheduleColumn $ref, Schedule $schedule) {
		$this->result = ['_errors' => false];

		$this->setFilteredValue('name',   $this->validateName($col, $schedule, $ref));
		$this->setFilteredValue('hidden', $this->validateHidden($col, $schedule, $ref));

		return $this->result;
	}

	public function validateName(array $col, Schedule $schedule, ScheduleColumn $ref = null) {
		$name = '';

		if (!isset($col['name']) || !is_string($col['name'])) {
			$this->addError('name', 'No valid name given.');
		}
		else {
			$name = trim($col['name']);

			if (mb_strlen($name) === 0) {
				$this->addError('name', 'A column name cannot be empty.');
			}
		}

		return $name;
	}

	public function validateHidden(array $col, Schedule $schedule, ScheduleColumn $ref = null) {
		$hidden = false;

		if (!isset($col['hidden']) || !is_bool($col['hidden'])) {
			$this->addError('hidden', 'No valid hidden flag given.');
		}
		else {
			$hidden = $col['hidden'];
		}

		return $hidden;
	}
}
