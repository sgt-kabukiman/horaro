<?php
/*
 * Copyright (c) 2014, Sgt. Kabukiman, https://bitbucket.org/sgt-kabukiman/
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\Library\ScheduleImporter;

use horaro\Library\Entity\Schedule;
use horaro\Library\Entity\ScheduleColumn;
use horaro\Library\Entity\ScheduleItem;

class JsonImporter extends BaseImporter {
	public function import($file, Schedule $schedule, $ignoreErrors, $updateMetadata) {
		$data = @json_decode(file_get_contents($file), false, 20);

		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new \Exception('The given file did not contain valid JSON.');
		}

		// check JSON schema
		$retriever = new \JsonSchema\Uri\UriRetriever();
		$schema    = $retriever->retrieve('file://'.realpath(HORARO_ROOT.'/resources/schema/schedule.json'));

		$validator = new \JsonSchema\Validator();
		$validator->check($data, $schema);

		if (!$validator->isValid()) {
			$message = ['The uploaded JSON does not conform to the schema:'];

			foreach ($validator->getErrors() as $error) {
				$message[] = sprintf('[%s] %s', $error['property'], $error['message']);
			}

			throw new \Exception(implode("\n", $message));
		}

		// import columns
		$pos     = 1;
		$columns = [];

		foreach ($data->schedule->columns as $col) {
			if ($pos <= 10) {
				$column = new ScheduleColumn();
				$column->setName($col)->setPosition($pos);

				$columns[] = $column;
				$this->log('ok', 'Imported column #'.$pos.', "'.$col.'"');
			}
			else {
				$this->log('warn', 'Ignoring column #'.$pos.' ("'.$col.'").');
			}

			$pos++;
		}

		// and now we finally read through the items and import them
		$pos      = 1;
		$items    = [];
		$tmpDate  = new \DateTime('@0');
		$maxItems = $schedule->getMaxItems();

		foreach ($data->schedule->items as $idx => $it) {
			$seconds = 0;

			// at the very least, we need a valid length for this item
			if (isset($it->length)) {
				try {
					$length = new \DateInterval($it->length);

					// convert DateInterval into number of seconds
					$tmp = clone $tmpDate;
					$tmp->add($length);

					$seconds = (int) $tmp->format('U');
				}
				catch (\Exception $e) {
					$this->log('error', 'Malformed length for row #'.($idx+1).' found. Cannot import row.');
					if ($ignoreErrors) continue;
					return $this->returnLog();
				}
			}
			elseif (isset($it->length_t)) {
				$seconds = $it->length_t;
			}

			if ($seconds < 1 || $seconds > 7*24*3600) {
				$this->log('error', 'Length of row #'.($idx+1).' is invalid (must be between 1 second and 7 days). Cannot import row.');
				if ($ignoreErrors) continue;
				return $this->returnLog();
			}

			// now we can create the item. Since we don't have the column IDs yet, we insert a plain
			// array and take care of fixing that later.
			$item = new ScheduleItem();
			$item->setPosition($pos)->setLengthInSeconds($seconds);

			// avoid the overhead of setExtra()'s json_encoding
			$item->tmpExtra = array_values($it->data);

			$items[] = $item;
			$this->log('ok', 'Imported row #'.($idx+1).'.');

			$pos++;

			if ($pos > $maxItems) {
				$this->log('warn', 'Ignoring any further rows.');
				break;
			}
		}

		// Now we have the columns and items, but nothing is persisted yet. We will now replace the
		// columns with the new ones, so they get their ID assigned.
		$columnIDs = $this->replaceColumns($schedule, $columns);

		// Now we can fix the extra data on the items and insert the column IDs.
		$this->replaceItems($schedule, $items, $columnIDs);

		if ($updateMetadata) {
			$this->updateMetadata($schedule, $data->schedule);
		}

		$this->flush();

		return $this->returnLog();
	}

	protected function updateMetadata(Schedule $schedule, \stdClass $data) {
		if (isset($data->name)) {
			$schedule->setName($data->name);
			$this->log('ok', 'Updated schedule name with "'.$data->name.'"');
		}

		if (isset($data->slug)) {
			try {
				$slug = $this->validator->validateSlug($data->slug, $schedule->getEvent(), $schedule, true);

				$schedule->setSlug($slug);
				$this->log('ok', 'Updated schedule slug with "'.$slug.'"');
			}
			catch (\Exception $e) {
				$this->log('warn', 'Bad slug: '.$e->getMessage());
			}
		}

		if (isset($data->timezone)) {
			try {
				$timezone = $this->validator->validateTimezone($data->timezone, $schedule->getEvent(), $schedule, true);

				$schedule->setTimezone($timezone);
				$this->log('ok', 'Updated schedule timezone with "'.$timezone.'"');
			}
			catch (\Exception $e) {
				$this->log('warn', 'Bad timezone: '.$e->getMessage());
			}
		}

		if (isset($data->start) || isset($data->start_t)) {
			try {
				$tz = new \DateTimezone($schedule->getTimezone());

				if (isset($data->start)) {
					$start = new \DateTime($data->start, $tz);
				}
				else {
					$start = new \DateTime('@'.$data->start);
				}

				// if needed, convert the time to the correct timezone, because start time is always
				// stored in the schedule timezone
				$start->setTimezone($tz);

				$this->validator->validateStart($start->format('Y-m-d'), $start->format('H:i'), $schedule->getEvent(), $schedule, true);

				$schedule->setStart($start);
				$this->log('ok', 'Updated schedule start with "'.$start->format('r').'"');
			}
			catch (\Exception $e) {
				$this->log('warn', 'Bad start date/time: '.$e->getMessage());
			}
		}

		if (isset($data->website)) {
			try {
				$website = $this->validator->validateWebsite($data->website, $schedule->getEvent(), $schedule, true);

				$schedule->setWebsite($website);
				$this->log('ok', 'Updated schedule website with "'.$website.'"');
			}
			catch (\Exception $e) {
				$this->log('warn', 'Bad website: '.$e->getMessage());
			}
		}

		if (isset($data->twitch)) {
			try {
				$twitch = $this->validator->validateTwitchAccount($data->twitch, $schedule->getEvent(), $schedule, true);

				$schedule->setTwitch($twitch);
				$this->log('ok', 'Updated schedule twitch account with "'.$twitch.'"');
			}
			catch (\Exception $e) {
				$this->log('warn', 'Bad twitch account: '.$e->getMessage());
			}
		}

		if (isset($data->twitter)) {
			try {
				$twitter = $this->validator->validateTwitterAccount($data->twitter, $schedule->getEvent(), $schedule, true);

				$schedule->setTwitter($twitter);
				$this->log('ok', 'Updated schedule twitter account with "'.$twitter.'"');
			}
			catch (\Exception $e) {
				$this->log('warn', 'Bad twitter account: '.$e->getMessage());
			}
		}

		if (isset($data->theme)) {
			try {
				$theme = $this->validator->validateTheme($data->theme, $schedule->getEvent(), $schedule, true);

				$schedule->setTheme($theme);
				$this->log('ok', 'Updated schedule theme with "'.$theme.'"');
			}
			catch (\Exception $e) {
				$this->log('warn', 'Bad theme: '.$e->getMessage());
			}
		}

		if (isset($data->secret)) {
			try {
				$secret = $this->validator->validateSecret($data->secret, true);

				$schedule->setSecret($secret);
				$this->log('ok', 'Updated schedule secret with "'.$secret.'"');
			}
			catch (\Exception $e) {
				$this->log('warn', 'Bad secret: '.$e->getMessage());
			}
		}
	}
}
