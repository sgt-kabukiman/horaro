<?php
/*
 * Copyright (c) 2019, Sgt. Kabukiman, https://github.com/sgt-kabukiman
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

	public function transform(Schedule $schedule, $public = false, $withHiddenColumns = false) {
		$cols  = $this->getEffectiveColumns($schedule, $withHiddenColumns);
		$event = $schedule->getEvent();
		$start = $schedule->getLocalStart();

		// make it possible to hide the options by specifying the ?hiddenkey secret
		$optionsCol = $withHiddenColumns ? $schedule->getOptionsColumn() : null;

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

				$xml->writeElement('description', $schedule->getDescription());

				if (!$public) {
					$xml->writeElement('theme', $schedule->getTheme());
					$xml->writeElement('secret', $schedule->getSecret());
				}

				$xml->startElement('setup');
					$xml->writeAttribute('timestamp', $schedule->getSetupTimeInSeconds());
					$xml->text($schedule->getSetupTimeISODuration());
				$xml->endElement();

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
					foreach ($cols as $col) {
						$xml->startElement('column');
							if ($withHiddenColumns) {
								$xml->writeAttribute('hidden', $col->isHidden() ? 'true' : 'false');
							}
							$xml->text($col->getName());
						$xml->endElement();
					}
				$xml->endElement();

				$xml->startElement('items');
					foreach ($schedule->getScheduledItems() as $item) {
						$extra = $item->getExtra();

						$xml->startElement('item');
							$xml->startElement('length');
								$xml->writeAttribute('seconds', $item->getLengthInSeconds());
								$xml->text($item->getISODuration());
							$xml->endElement();
							$xml->startElement('scheduled');
								$xml->writeAttribute('timestamp', $item->getScheduled()->format('U'));
								$xml->text($item->getScheduled()->format(self::DATE_FORMAT_TZ));
							$xml->endElement();
							$xml->startElement('data');
								foreach ($cols as $col) {
									$colID = $col->getId();

									$xml->writeElement('value', isset($extra[$colID]) ? $extra[$colID] : null);
								}
							$xml->endElement();

							if ($optionsCol) {
								$options = $item->getOptions();

								if ($options) {
									$xml->startElement('options');
									foreach ($options as $key => $value) {
										$xml->startElement('option');
										$xml->writeAttribute('key', $key);
										$xml->text($value);
										$xml->endElement();
									}
									$xml->endElement();
								}
							}
						$xml->endElement();
					}
				$xml->endElement();

			$xml->endElement();
		$xml->endElement();

		return $xml->flush();
	}
}
