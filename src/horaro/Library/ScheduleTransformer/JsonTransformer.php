<?php
/*
 * Copyright (c) 2014, Sgt. Kabukiman, https://bitbucket.org/sgt-kabukiman/
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\Library\ScheduleTransformer;

use horaro\Library\Entity\Schedule;

class JsonTransformer extends BaseTransformer {
	const DATE_FORMAT_TZ  = 'Y-m-d\TH:i:sP';
	const DATE_FORMAT_UTC = 'Y-m-d\TH:i:s\Z';

	public function getContentType() {
		return 'application/json; charset=UTF-8';
	}

	public function transform(Schedule $schedule) {
		$event     = $schedule->getEvent();
		$cols      = $schedule->getColumns();
		$columns   = [];
		$items     = [];
		$start     = $schedule->getLocalStart();
		$scheduled = clone $start;

		foreach ($cols as $col) {
			$columns[] = $col->getName();
		}

		foreach ($schedule->getItems() as $item) {
			$extra = $item->getExtra();
			$node  = [
				'length'      => $item->getLength()->format('H:i:s'),
				'length_t'    => $item->getLengthInSeconds(),
				'scheduled'   => $scheduled->format(self::DATE_FORMAT_TZ),
				'scheduled_t' => (int) $scheduled->format('U'),
				'data'        => []
			];

			foreach ($cols as $col) {
				$colID = $col->getId();

				$node['data'][] = isset($extra[$colID]) ? $extra[$colID] : null;
			}

			$items[] = $node;
			$scheduled->add($item->getDateInterval());
		}

		$data = [
			'schedule' => [
				'id'       => $this->encodeID($schedule->getId(), 'schedule'),
				'name'     => $schedule->getName(),
				'slug'     => $schedule->getSlug(),
				'timezone' => $schedule->getTimezone(),
				'start'    => $start->format(self::DATE_FORMAT_TZ),
				'start_t'  => (int) $start->format('U'),
				'updated'  => $schedule->getUpdatedAt()->format(self::DATE_FORMAT_UTC), // updated is stored as UTC, so it's okay to disregard the sys timezone here and force UTC
				'url'      => sprintf('/%s/%s', $event->getSlug(), $schedule->getSlug()),
				'event'    => [
					'id'   => $this->encodeID($event->getId(), 'event'),
					'name' => $event->getName(),
					'slug' => $event->getSlug()
				],
				'columns'  => $columns,
				'items'    => $items
			],
			'meta' => [
				'exported' => gmdate(self::DATE_FORMAT_UTC)
			]
		];

		return json_encode($data, JSON_UNESCAPED_SLASHES);
	}
}
