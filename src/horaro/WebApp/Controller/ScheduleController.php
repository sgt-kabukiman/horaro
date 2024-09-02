<?php
/*
 * Copyright (c) 2019, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Controller;

use horaro\Library\Entity\Event;
use horaro\Library\Entity\Schedule;
use horaro\Library\Entity\ScheduleColumn;
use horaro\Library\ReadableTime;
use horaro\WebApp\Exception as Ex;
use horaro\WebApp\Validator\ScheduleValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ScheduleController extends BaseController {
	public function detailAction(Request $request) {
		$schedule  = $this->getRequestedSchedule($request);
		$optsCol   = $schedule->getOptionsColumn();
		$setup     = $schedule->getSetupTime();
		$items     = [];
		$columnIDs = [];

		foreach ($schedule->getItems() as $item) {
			$extra = [];
			$itemSetup = ($optsCol !== null) ? $item->getSetupTime($optsCol) : null;
			$itemSetup = ($itemSetup === null) ? $setup : $itemSetup;
			$itemSetup = ReadableTime::dateTimeToSeconds($itemSetup);

			foreach ($item->getExtra() as $colID => $value) {
				$extra[$this->encodeID($colID, 'schedule.column')] = $value;
			}

			$items[] = [
				$this->encodeID($item->getId(), 'schedule.item'),
				$item->getLengthInSeconds(),
				$extra,
				$itemSetup
			];
		}

		foreach ($schedule->getColumns() as $column) {
			$columnIDs[] = $this->encodeID($column->getId(), 'schedule.column');
		}

		// we need to manually calculate table widths during runtime, so we need inline styles.
		$this->app['csp']->addStyleSource('unsafe-inline');

		return $this->render('schedule/detail.twig', [
			'schedule' => $schedule,
			'items'    => $items ?: null,
			'columns'  => $columnIDs,
			'maxItems' => $this->app['config']['max_schedule_items']
		]);
	}

	public function newAction(Request $request) {
		$event = $this->getRequestedEvent($request);

		if ($this->exceedsMaxSchedules($event)) {
			return $this->redirect('/-/events/'.$event->getId());
		}

		return $this->renderForm($event);
	}

	public function createAction(Request $request) {
		$event = $this->getRequestedEvent($request);

		if ($this->exceedsMaxSchedules($event)) {
			return $this->redirect('/-/events/'.$event->getId());
		}

		$validator = $this->getValidator();
		$result    = $validator->validate([
			'name'          => $request->request->get('name'),
			'slug'          => $request->request->get('slug'),
			'timezone'      => $request->request->get('timezone'),
			'start_date'    => $request->request->get('start_date'),
			'start_time'    => $request->request->get('start_time'),
			'website'       => $request->request->get('website'),
			'twitter'       => $request->request->get('twitter'),
			'twitch'        => $request->request->get('twitch'),
			'theme'         => $request->request->get('theme'),
			'secret'        => $request->request->get('secret'),
			'hidden_secret' => $request->request->get('hidden_secret'),
			'setup_time'    => $request->request->get('setup_time')
		], $event);

		if ($result['_errors']) {
			return $this->renderForm($event, null, $result);
		}

		// create schedule

		$config   = $this->app['config'];
		$user     = $this->getCurrentUser();
		$schedule = new Schedule();

		$schedule
			->setEvent($event)
			->setName($result['name']['filtered'])
			->setSlug($result['slug']['filtered'])
			->setTimezone($result['timezone']['filtered'])
			->setStart($result['start']['filtered'])
			->setWebsite($result['website']['filtered'])
			->setTwitter($result['twitter']['filtered'])
			->setTwitch($result['twitch']['filtered'])
			->setTheme($result['theme']['filtered'])
			->setSecret($result['secret']['filtered'])
			->setHiddenSecret($result['hidden_secret']['filtered'])
			->setSetupTime($result['setup_time']['filtered'])
			->setMaxItems($config['max_schedule_items'])
			->touch()
		;

		$column = new ScheduleColumn();
		$column
			->setSchedule($schedule)
			->setPosition(1)
			->setName('Description')
		;

		$em = $this->getEntityManager();
		$em->persist($schedule);
		$em->persist($column);
		$em->flush();

		// done

		$this->addSuccessMsg('Your new schedule has been created.');

		return $this->redirect('/-/schedules/'.$this->encodeID($schedule->getId(), 'schedule'));
	}

	public function editAction(Request $request) {
		$schedule = $this->getRequestedSchedule($request);

		return $this->renderForm($schedule->getEvent(), $schedule, null);
	}

	public function updateAction(Request $request) {
		$schedule  = $this->getRequestedSchedule($request);
		$event     = $schedule->getEvent();
		$validator = $this->getValidator();
		$result    = $validator->validate([
			'name'          => $request->request->get('name'),
			'slug'          => $request->request->get('slug'),
			'timezone'      => $request->request->get('timezone'),
			'start_date'    => $request->request->get('start_date'),
			'start_time'    => $request->request->get('start_time'),
			'website'       => $request->request->get('website'),
			'twitter'       => $request->request->get('twitter'),
			'twitch'        => $request->request->get('twitch'),
			'theme'         => $request->request->get('theme'),
			'secret'        => $request->request->get('secret'),
			'hidden_secret' => $request->request->get('hidden_secret'),
			'setup_time'    => $request->request->get('setup_time')
		], $event, $schedule);

		if ($result['_errors']) {
			return $this->renderForm($event, $schedule, $result);
		}

		// update

		$schedule
			->setName($result['name']['filtered'])
			->setSlug($result['slug']['filtered'])
			->setTimezone($result['timezone']['filtered'])
			->setStart($result['start']['filtered'])
			->setWebsite($result['website']['filtered'])
			->setTwitter($result['twitter']['filtered'])
			->setTwitch($result['twitch']['filtered'])
			->setTheme($result['theme']['filtered'])
			->setSecret($result['secret']['filtered'])
			->setHiddenSecret($result['hidden_secret']['filtered'])
			->setSetupTime($result['setup_time']['filtered'])
			->touch()
		;

		$this->getEntityManager()->flush();

		// done

		$this->addSuccessMsg('Your schedule has been updated.');

		return $this->redirect('/-/schedules/'.$this->encodeID($schedule->getId(), 'schedule'));
	}

	public function updateDescriptionAction(Request $request) {
		$schedule  = $this->getRequestedSchedule($request);
		$validator = $this->getValidator();
		$result    = $validator->validateDescription($request->request->get('description'));

		if ($result['_errors']) {
			return $this->renderForm($schedule->getEvent(), $schedule, $result);
		}

		// update

		$schedule
			->setDescription($result['description']['filtered'])
			->touch()
		;

		$this->getEntityManager()->flush();

		// done

		$this->addSuccessMsg('Your schedule description has been updated.');

		return $this->redirect('/-/schedules/'.$this->encodeID($schedule->getId(), 'schedule'));
	}

	public function confirmationAction(Request $request) {
		$schedule = $this->getRequestedSchedule($request);

		return $this->render('schedule/confirmation.twig', ['schedule' => $schedule]);
	}

	public function deleteAction(Request $request) {
		$schedule = $this->getRequestedSchedule($request);
		$eventID  = $schedule->getEvent()->getId();
		$em       = $this->getEntityManager();

		$em->remove($schedule);
		$em->flush();

		$this->addSuccessMsg('The requested schedule has been deleted.');

		return $this->redirect('/-/events/'.$this->encodeID($eventID, 'event'));
	}

	public function exportAction(Request $request) {
		$schedule = $this->getRequestedSchedule($request);
		$format   = strtolower($request->query->get('format'));
		$formats  = ['json', 'xml', 'csv', 'ical'];

		if (!in_array($format, $formats, true)) {
			throw new Ex\BadRequestException('Invalid format "'.$format.'" given.');
		}

		$id          = 'schedule-transformer-'.$format;
		$transformer = $this->app[$id];
		$data        = $transformer->transform($schedule, false, true);
		$filename    = sprintf('%s-%s.%s', $schedule->getEvent()->getSlug(), $schedule->getSlug(), $transformer->getFileExtension());

		return new Response($data, 200, [
			'Content-Type'        => $transformer->getContentType(),
			'Content-Disposition' => 'filename="'.$filename.'"'
		]);
	}

	protected function getValidator() {
		return $this->app['validator.schedule'];
	}

	protected function renderForm(Event $event, Schedule $schedule = null, $result = null) {
		$timezones = \DateTimeZone::listIdentifiers();
		$config    = $this->app['config'];

		return $this->render('schedule/form.twig', [
			'event'        => $event,
			'timezones'    => $timezones,
			'schedule'     => $schedule,
			'result'       => $result,
			'themes'       => $config['themes'],
			'defaultTheme' => $event->getTheme()
		]);
	}
}
