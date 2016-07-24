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
use horaro\Library\Entity\ScheduleItem;

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

	public function transform(Schedule $schedule, $public = false, $withHiddenColumns = false) {
		$cols    = $withHiddenColumns ? $schedule->getColumns() : $schedule->getVisibleColumns();
		$columns = [];
		$hidden  = [];

		foreach ($cols as $col) {
			$columns[] = $col->getName();

			if ($col->isHidden()) {
				$hidden[] = $col->getName();
			}
		}

		$items = [];
		foreach ($schedule->getScheduledItems() as $item) {
			$items[] = $this->transformItem($item, $cols);
		}

		$event = $schedule->getEvent();
		$start = $schedule->getLocalStart();

		$data = [
			'meta' => [
				'exported' => gmdate(self::DATE_FORMAT_UTC),
				'hint'     => 'Use ?callback=yourcallback to use this document via JSONP.',
				'api'      => 'This is a living document and may change over time. For a stable, well-defined output, use the API instead.',
				'api-link' => '/-/api/v1/schedules/'.$this->encodeID($schedule->getId(), 'schedule')
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
				'setup'       => $schedule->getSetupTimeISODuration(),
				'setup_t'     => $schedule->getSetupTimeInSeconds(),
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
				'hidden_columns' => $hidden,
				'columns'        => $columns,
				'items'          => $items
			]
		];

		if (!$schedule->isPublic()) {
			unset($data['meta']['api']);
			unset($data['meta']['api-link']);
		}

		if (!$withHiddenColumns) {
			unset($data['schedule']['hidden_columns']);
		}

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

	// the following methods are helpers for the API and do not return JSON, but arrays

	public function transformTicker(Schedule $schedule, array $ticker, $public = false, $withHiddenColumns = false) {
		$cols    = $withHiddenColumns ? $schedule->getColumns() : $schedule->getVisibleColumns();
		$columns = [];
		$hidden  = [];

		foreach ($cols as $col) {
			$columns[] = $col->getName();

			if ($col->isHidden()) {
				$hidden[] = $col->getName();
			}
		}

		$event = $schedule->getEvent();
		$start = $schedule->getLocalStart();

		$data = [
			'schedule' => [
				'id'             => $this->encodeID($schedule->getId(), 'schedule'),
				'name'           => $schedule->getName(),
				'slug'           => $schedule->getSlug(),
				'timezone'       => $schedule->getTimezone(),
				'start'          => $start->format(self::DATE_FORMAT_TZ),
				'start_t'        => (int) $start->format('U'),
				'setup'          => $schedule->getSetupTimeISODuration(),
				'setup_t'        => $schedule->getSetupTimeInSeconds(),
				'updated'        => $schedule->getUpdatedAt()->format(self::DATE_FORMAT_UTC), // updated is stored as UTC, so it's okay to disregard the sys timezone here and force UTC
				'url'            => sprintf('/%s/%s', $event->getSlug(), $schedule->getSlug()),
				'hidden_columns' => $hidden,
				'columns'        => $columns,
			],
			'ticker' => [
				'previous' => $ticker['prev'] ? $this->transformItem($ticker['prev'], $cols) : null,
				'current' => $ticker['active'] ? $this->transformItem($ticker['active'], $cols) : null,
				'next' => $ticker['next'] ? $this->transformItem($ticker['next'], $cols) : null,
			]
		];

		if (!$withHiddenColumns) {
			unset($data['schedule']['hidden_columns']);
		}

		return $data;
	}

	public function transformItem(ScheduleItem $item, $columns) {
		$extra  = $item->getExtra();
		$result = [
			'length'      => $item->getISODuration(),
			'length_t'    => $item->getLengthInSeconds(),
			'scheduled'   => $item->getScheduled()->format(self::DATE_FORMAT_TZ),
			'scheduled_t' => (int) $item->getScheduled()->format('U'),
			'data'        => []
		];

		foreach ($columns as $col) {
			$colID = $col->getId();

			$result['data'][] = isset($extra[$colID]) ? $extra[$colID] : null;
		}

		return $result;
	}
}
