<?php
/*
 * Copyright (c) 2014, Sgt. Kabukiman, https://bitbucket.org/sgt-kabukiman/
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Controller;

use horaro\Library\Entity\Event;
use horaro\Library\Entity\Schedule;
use horaro\WebApp\Exception as Ex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class ScheduleExportController extends BaseController {
	public function jsonAction(Request $request) {
		$schedule  = $this->getRequestedSchedule($request);
		$event     = $schedule->getEvent();
		$format    = $request->query->get('format');
		$formats   = ['json', 'xml', 'csv'];

		if (!in_array($format, $formats, true)) {
			throw new Ex\BadRequestException('Invalid format "'.$format.'" given.');
		}

		$method = $format.'Export';

		return $this->$method($schedule, $event);
	}

	protected function jsonExport(Schedule $schedule, Event $event) {
		$cols    = $schedule->getColumns();
		$columns = [];
		$items   = [];

		foreach ($cols as $col) {
			$columns[] = $col->getName();
		}

		foreach ($schedule->getItems() as $item) {
			$extra = $item->getExtra();
			$node = [
				'length' => $item->getLength()->format('H:i:s'),
				'data'   => []
			];

			foreach ($cols as $col) {
				$colID = $col->getId();

				$node['data'][] = isset($extra[$colID]) ? $extra[$colID] : null;
			}

			$items[] = $node;
		}

		$data = [
			'schedule' => [
				'id'    => $this->encodeID($schedule->getId(), 'schedule'),
				'name'  => $schedule->getName(),
				'slug'  => $schedule->getSlug(),
				'url'   => sprintf('/%s/%s', $event->getSlug(), $schedule->getSlug()),
				'event' => [
					'id'   => $this->encodeID($event->getId(), 'event'),
					'name' => $event->getName(),
					'slug' => $event->getSlug()
				],
				'columns' => $columns,
				'items'   => $items
			],
			'meta' => [
				'exported' => gmdate('Y-m-d\TH:i:s\Z')
			]
		];

		return new JsonResponse($data);
	}
}
