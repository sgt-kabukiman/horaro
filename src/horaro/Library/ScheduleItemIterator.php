<?php
/*
 * Copyright (c) 2019, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\Library;

use horaro\Library\Entity\Schedule;

class ScheduleItemIterator implements \Iterator {
	protected $schedule;
	protected $items;
	protected $time;
	protected $setup;
	protected $optionsCol;
	protected $current;
	protected $position;

	public function __construct(Schedule $schedule) {
		$this->schedule   = $schedule;
		$this->items      = $schedule->getItems();
		$this->setup      = $schedule->getSetupTimeDateInterval();
		$this->optionsCol = $schedule->getOptionsColumn();

		$this->rewind();
	}

	public function rewind() {
		$this->time     = $this->schedule->getLocalStart();
		$this->position = 0;

		$this->updateCurrentItem();
	}

	public function current() {
		return $this->current;
	}

	public function key() {
		return $this->position;
	}

	public function next() {
		$this->position++;

		$setupTime = $this->setup;

		if ($this->optionsCol) {
			$customSetup = $this->current->getSetupTimeDateInterval($this->optionsCol);

			if ($customSetup) {
				$setupTime = $customSetup;
			}
		}

		$this->time->add($this->current->getDateInterval());
		$this->time->add($setupTime);

		$this->updateCurrentItem();
	}

	public function valid() {
		return isset($this->items[$this->position]);
	}

	protected function updateCurrentItem() {
		$item = null;

		if ($this->valid()) {
			$item = $this->items[$this->position];
			$item->setScheduled($this->time);
		}

		$this->current = $item;
	}
}
