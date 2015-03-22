<?php
/*
 * Copyright (c) 2015, Sgt. Kabukiman, https://github.com/sgt-kabukiman
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
	protected $current;
	protected $position;

	public function __construct(Schedule $schedule) {
		$this->schedule = $schedule;
		$this->items    = $schedule->getItems();
		$this->setup    = $schedule->getSetupTimeDateInterval();

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

		$this->time->add($this->current->getDateInterval());
		$this->time->add($this->setup);

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
