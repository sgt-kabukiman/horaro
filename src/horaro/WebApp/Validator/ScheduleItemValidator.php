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
use horaro\Library\Entity\ScheduleItem;
use horaro\Library\ObscurityCodec;

class ScheduleItemValidator extends BaseValidator {
	protected $codec;

	public function __construct(ObscurityCodec $codec) {
		$this->codec = $codec;
	}

	public function validateNew(array $item, Schedule $schedule) {
		$this->result = ['_errors' => false];

		$this->setFilteredValue('length',  $this->validateLength($item, $schedule, null));
		$this->setFilteredValue('columns', $this->validateColumns($item, $schedule, null));

		return $this->result;
	}

	public function validateUpdate(array $item, ScheduleItem $ref, Schedule $schedule) {
		$this->result = ['_errors' => false];

		$this->setFilteredValue('length',  $this->validateLength($item, $schedule, $ref));
		$this->setFilteredValue('columns', $this->validateColumns($item, $schedule, $ref));

		return $this->result;
	}

	public function validateLength(array $item, Schedule $schedule, ScheduleItem $ref = null) {
		if (!$ref && (!isset($item['length']) || !is_int($item['length']))) {
			$this->addError('length', 'No valid length given.');
		}

		if (!isset($item['length'])) {
			return null;
		}

		$len = $item['length'];

		if ($len < 1) {
			$this->addError('length', 'Schedule items must at least last for one second.');
		}
		elseif ($len > 7*24*3600) {
			$this->addError('length', 'Schedule items cannot last for more than 7 days.');
		}

		return $len;
	}

	public function validateColumns(array $item, Schedule $schedule) {
		$columns = $schedule->getColumns();
		$data    = isset($item['columns']) ? $item['columns'] : [];
		$result  = [];

		foreach ($columns as $column) {
			$colID     = $column->getId();
			$encodedID = $this->codec->encode($colID, 'schedule.column');

			if (isset($data[$encodedID]) && is_string($data[$encodedID])) {
				$val = trim(mb_substr(trim($data[$encodedID]), 0, 512));

				$result[$colID] = $val;
			}
		}

		return $result;
	}
}
