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

class XmlTransformer extends BaseTransformer {
	const DATE_FORMAT_TZ  = 'Y-m-d\TH:i:sP';
	const DATE_FORMAT_UTC = 'Y-m-d\TH:i:s\Z';

	public function getContentType() {
		return 'text/xml; charset=UTF-8';
	}

	public function getFileExtension() {
		return 'xml';
	}

	public function transform(Schedule $schedule, $public = false) {
		$event     = $schedule->getEvent();
		$start     = $schedule->getLocalStart();
		$cols      = $schedule->getColumns();
		$scheduled = clone $start;

		$xml = new \XMLWriter();
		$xml->openMemory();
		$xml->startDocument('1.0', 'UTF-8', 'yes');

		$xml->startElement('export');
			$xml->startElement('meta');
				$xml->writeElement('exported', gmdate(self::DATE_FORMAT_UTC));
			$xml->endElement();
			$xml->startElement('schedule');
				if (!$public) {
					$xml->writeAttribute('id', $this->encodeID($schedule->getId(), 'schedule'));
				}
				$xml->writeElement('name', $schedule->getName());
				$xml->writeElement('slug', $schedule->getSlug());
				$xml->writeElement('timezone', $schedule->getTimezone());

				$xml->startElement('start');
					$xml->writeAttribute('timestamp', $start->format('U'));
					$xml->text($start->format(self::DATE_FORMAT_TZ));
				$xml->endElement();

				if (!$public) {
					$xml->writeElement('theme', $schedule->getTheme());
					$xml->writeElement('secret', $schedule->getSecret());
				}

				$xml->writeElement('website', $schedule->getWebsite() ?: $event->getWebsite());
				$xml->writeElement('twitter', $schedule->getTwitter() ?: $event->getTwitter());
				$xml->writeElement('twitch', $schedule->getTwitch() ?: $event->getTwitch());
				$xml->writeElement('updated', $schedule->getUpdatedAt()->format(self::DATE_FORMAT_UTC)); // updated is stored as UTC, so it's okay to disregard the sys timezone here and force UTC
				$xml->writeElement('url', sprintf('/%s/%s', $event->getSlug(), $schedule->getSlug()));

				$xml->startElement('event');
					if (!$public) {
						$xml->writeAttribute('id', $this->encodeID($event->getId(), 'event'));
					}
					$xml->writeElement('name', $event->getName());
					$xml->writeElement('slug', $event->getSlug());

					if (!$public) {
						$xml->writeElement('theme', $event->getTheme());
						$xml->writeElement('secret', $event->getSecret());
					}
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
								$xml->text($item->getISODuration());
							$xml->endElement();
							$xml->startElement('scheduled');
								$xml->writeAttribute('timestamp', $scheduled->format('U'));
								$xml->text($scheduled->format(self::DATE_FORMAT_TZ));
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
		$xml->endElement();

		return $xml->flush();
	}
}
