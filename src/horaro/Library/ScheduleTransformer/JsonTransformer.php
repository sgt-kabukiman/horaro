<?php
/*
 * Copyright (c) 2015, Sgt. Kabukiman, https://github.com/sgt-kabukiman
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

	protected $hint = true;

	public function getContentType() {
		return 'application/json; charset=UTF-8';
	}

	public function getFileExtension() {
		return 'json';
	}

	public function transform(Schedule $schedule, $public = false) {
		$event     = $schedule->getEvent();
		$cols      = $schedule->getColumns();
		$columns   = [];
		$items     = [];
		$start     = $schedule->getLocalStart();

		foreach ($cols as $col) {
			$columns[] = $col->getName();
		}

		foreach ($schedule->getScheduledItems() as $item) {
			$extra = $item->getExtra();
			$node  = [
				'length'      => $item->getISODuration(),
				'length_t'    => $item->getLengthInSeconds(),
				'scheduled'   => $item->getScheduled()->format(self::DATE_FORMAT_TZ),
				'scheduled_t' => (int) $item->getScheduled()->format('U'),
				'data'        => []
			];

			foreach ($cols as $col) {
				$colID = $col->getId();

				$node['data'][] = isset($extra[$colID]) ? $extra[$colID] : null;
			}

			$items[] = $node;
		}

		$data = [
			'meta' => [
				'exported' => gmdate(self::DATE_FORMAT_UTC),
				'hint'     => 'Use ?callback=yourcallback to use this document via JSONP.'
			],
			'schedule' => [
				'id'          => $this->encodeID($schedule->getId(), 'schedule'),
				'name'        => $schedule->getName(),
				'slug'        => $schedule->getSlug(),
				'timezone'    => $schedule->getTimezone(),
				'start'       => $start->format(self::DATE_FORMAT_TZ),
				'start_t'     => (int) $start->format('U'),
				'website'     => $schedule->getWebsite() ?: $event->getWebsite(),
				'twitter'     => $schedule->getTwitter() ?: $event->getTwitter(),
				'twitch'      => $schedule->getTwitch() ?: $event->getTwitch(),
				'description' => $schedule->getDescription(),
				'theme'       => $schedule->getTheme(),
				'secret'      => $schedule->getSecret(),
				'updated'     => $schedule->getUpdatedAt()->format(self::DATE_FORMAT_UTC), // updated is stored as UTC, so it's okay to disregard the sys timezone here and force UTC
				'url'         => sprintf('/%s/%s', $event->getSlug(), $schedule->getSlug()),
				'event'       => [
					'id'     => $this->encodeID($event->getId(), 'event'),
					'name'   => $event->getName(),
					'slug'   => $event->getSlug(),
					'theme'  => $event->getTheme(),
					'secret' => $event->getSecret()
				],
				'columns'     => $columns,
				'items'       => $items
			]
		];

		if ($public) {
			unset($data['schedule']['id']);
			unset($data['schedule']['theme']);
			unset($data['schedule']['secret']);
			unset($data['schedule']['event']['id']);
			unset($data['schedule']['event']['theme']);
			unset($data['schedule']['event']['secret']);
		}

		if (!$this->hint || !$public) {
			unset($data['meta']['hint']);
		}

		return json_encode($data, JSON_UNESCAPED_SLASHES);
	}
}
