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
	const DATE_FORMAT_JSON = 'Y-m-d\TH:i:s';
	const DATE_FORMAT_XML  = 'Y-m-d\TH:i:s';
	const DATE_FORMAT_CSV  = 'r';

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
				'scheduled'   => $scheduled->format(self::DATE_FORMAT_JSON.'P'),
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
				'start'    => $start->format(self::DATE_FORMAT_JSON.'P'),
				'start_t'  => (int) $start->format('U'),
				'updated'  => $schedule->getUpdatedAt()->format(self::DATE_FORMAT_JSON.'\Z'), // updated is stored as UTC, so it's okay to disregard the sys timezone here and force UTC
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
				'exported' => gmdate(self::DATE_FORMAT_JSON.'\Z')
			]
		];

		return new JsonResponse($data, 200, [
			'content-type' => 'application/json; charset=UTF-8' // add UTF-8 charset declaration
		]);
	}

	protected function csvExport(Schedule $schedule, Event $event) {
		$rows      = [];
		$cols      = $schedule->getColumns();
		$scheduled = $schedule->getLocalStart();
		$toCSV     = function($val) {
			return '"'.addcslashes($val, '"').'"';
		};

		$header = [$toCSV('Scheduled'), $toCSV('Length')];

		foreach ($cols as $col) {
			$header[] = $toCSV($col->getName());
		}

		$rows[] = implode(';', $header);

		foreach ($schedule->getItems() as $item) {
			$extra = $item->getExtra();
			$row   = [
				'scheduled' => $toCSV($scheduled->format(self::DATE_FORMAT_CSV)),
				'length'    => $toCSV($item->getLength()->format('H:i:s'))
			];

			foreach ($cols as $col) {
				$colID = $col->getId();
				$row[] = isset($extra[$colID]) ? $toCSV($extra[$colID]) : '';
			}

			$rows[] = implode(';', $row);
			$scheduled->add($item->getDateInterval());
		}

		return new Response(implode("\n", $rows), 200, [
			'content-type' => 'text/csv; charset=UTF-8'
		]);
	}

	protected function xmlExport(Schedule $schedule, Event $event) {
		$start     = $schedule->getLocalStart();
		$cols      = $schedule->getColumns();
		$scheduled = clone $start;

		$xml = new \XMLWriter();
		$xml->openMemory();
		$xml->startDocument('1.0', 'UTF-8', 'yes');

		$xml->startElement('export');
			$xml->startElement('schedule');
				$xml->writeAttribute('id', $this->encodeID($schedule->getId(), 'schedule'));
				$xml->writeElement('name', $schedule->getName());
				$xml->writeElement('slug', $schedule->getSlug());
				$xml->writeElement('timezone', $schedule->getTimezone());

				$xml->startElement('start');
					$xml->writeAttribute('timestamp', $start->format('U'));
					$xml->text($start->format(self::DATE_FORMAT_XML.'P'));
				$xml->endElement();

				$xml->writeElement('updated', $schedule->getUpdatedAt()->format(self::DATE_FORMAT_XML.'\Z')); // updated is stored as UTC, so it's okay to disregard the sys timezone here and force UTC
				$xml->writeElement('url', sprintf('/%s/%s', $event->getSlug(), $schedule->getSlug()));

				$xml->startElement('event');
					$xml->writeAttribute('id', $this->encodeID($event->getId(), 'event'));
					$xml->writeElement('name', $event->getName());
					$xml->writeElement('slug', $event->getSlug());
				$xml->endElement();

				$xml->startElement('columns');
					foreach ($schedule->getColumns() as $col) {
						$xml->writeElement('column', $col->getName());
					}
				$xml->endElement();

				$xml->startElement('items');
					foreach ($schedule->getItems() as $item) {
						$extra = $item->getExtra();

						$xml->startElement('item');
							$xml->startElement('length');
								$xml->writeAttribute('seconds', $item->getLengthInSeconds());
								$xml->text($item->getLength()->format('H:i:s'));
							$xml->endElement();
							$xml->startElement('scheduled');
								$xml->writeAttribute('timestamp', $scheduled->format('U'));
								$xml->text($scheduled->format(self::DATE_FORMAT_JSON.'P'));
							$xml->endElement();
							$xml->startElement('data');
								foreach ($cols as $col) {
									$colID = $col->getId();

									$xml->writeElement('value', isset($extra[$colID]) ? $extra[$colID] : null);
								}
							$xml->endElement();
						$xml->endElement();

						$scheduled->add($item->getDateInterval());
					}
				$xml->endElement();

			$xml->endElement();
			$xml->startElement('meta');
				$xml->writeElement('exported', gmdate(self::DATE_FORMAT_JSON.'\Z'));
			$xml->endElement();
		$xml->endElement();

		return new Response($xml->flush(), 200, [
			'content-type' => 'text/xml; charset=UTF-8'
		]);
	}
}
