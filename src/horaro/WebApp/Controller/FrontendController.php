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

class FrontendController extends BaseController {
	public function scheduleAction(Request $request) {
		list($schedule, $event) = $this->resolveSchedule($request);
		if ($schedule instanceof Response) return $schedule;

		$key = $request->query->get('key');

		$result = $this->handleScheduleAccess($event, $schedule, $key);
		if ($result instanceof Response) return $result;

		$content = $this->render('frontend/schedule/schedule.twig', [
			'event'     => $event,
			'schedule'  => $schedule,
			'key'       => $key,
			'schedules' => $this->getAllowedSchedules($event, $key),
			'isPrivate' => $this->isPrivatePage($event)
		]);

		$response = new Response($content, 200, ['content-type' => 'text/html; charset=UTF-8']);

		return $this->setCachingHeader($schedule, $response);
	}

	public function scheduleExportAction(Request $request) {
		list($schedule, $event) = $this->resolveSchedule($request);
		if ($schedule instanceof Response) return $schedule;

		$format  = strtolower($request->attributes->get('format'));
		$formats = ['json', 'jsonp', 'xml', 'csv', 'ical'];

		if (!in_array($format, $formats, true)) {
			throw new Ex\BadRequestException('Invalid format "'.$format.'" given.');
		}

		$key = $request->query->get('key');

		$result = $this->handleScheduleAccess($event, $schedule, $key);
		if ($result instanceof Response) return $result;

		// auto-switch to JSONP if there is a callback parameter
		if ($format === 'json' && $request->query->has('callback')) {
			$format = 'jsonp';
		}

		$id          = 'schedule-transformer-'.$format;
		$transformer = $this->app[$id];

		try {
			$data = $transformer->transform($schedule, true);
		}
		catch (\InvalidArgumentException $e) {
			throw new Ex\BadRequestException($e->getMessage());
		}

		$filename = sprintf('%s-%s.%s', $event->getSlug(), $schedule->getSlug(), $transformer->getFileExtension());
		$headers  = ['Content-Type' => $transformer->getContentType()];

		if ($request->query->get('named')) {
			$headers['Content-Disposition'] = 'filename="'.$filename.'"';
		}

		$response = new Response($data, 200, $headers);

		return $this->setCachingHeader($schedule, $response);
	}

	public function icalFaqAction(Request $request) {
		list($schedule, $event) = $this->resolveSchedule($request);
		if ($schedule instanceof Response) return $schedule;

		$key = $request->query->get('key');

		$result = $this->handleScheduleAccess($event, $schedule, $key);
		if ($result instanceof Response) return $result;

		$content = $this->render('frontend/schedule/ical.twig', [
			'event'     => $event,
			'schedule'  => $schedule,
			'key'       => $key,
			'schedules' => $this->getAllowedSchedules($event, $key),
			'isPrivate' => $this->isPrivatePage($event)
		]);

		$response = new Response($content, 200, ['Content-Type' => 'text/html; charset=UTF-8']);

		return $this->setCachingHeader($schedule, $response);
	}

	public function eventAction(Request $request) {
		$event = $this->resolveEvent($request);
		if ($event instanceof Response) return $event;

		$key = $request->query->get('key');

		// the event page is accessible if you have the event key or a key for one of the schedules
		if (!$this->hasGoodEventKey($event, $key) && !$this->hasGoodSchedulesKey($event, $key)) {
			throw new Ex\ForbiddenException('This event is private.');
		}

		$content = $this->render('frontend/event/event.twig', [
			'event'     => $event,
			'key'       => $key,
			'schedules' => $this->getAllowedSchedules($event, $key),
			'isPrivate' => $this->isPrivatePage($event)
		]);

		$response = new Response($content, 200, ['content-type' => 'text/html; charset=UTF-8']);

		return $this->setCachingHeader(null, $response);
	}

	protected function resolveEvent(Request $request) {
		$eventSlug = mb_strtolower($request->attributes->get('eventslug'));

		// quickly fail if this is just a broken link somewhere in the backend or a missing asset
		if (in_array($eventSlug, ['-', 'assets'], true)) {
			return new Response('Not Found.', 404, ['content-type' => 'text/plain']);
		}

		// resolve event
		$eventRepo = $this->getRepository('Event');
		$event     = $eventRepo->findOneBySlug($eventSlug);

		if (!$event) {
			throw new Ex\NotFoundException('There is no event named "'.$eventSlug.'".');
		}

		return $event;
	}

	protected function resolveSchedule(Request $request) {
		// resolve event
		$event = $this->resolveEvent($request);
		if ($event instanceof Response) return [$event, null];

		// check right now whether this even is private and we have passable credentials; otherwise,
		// stop right here with a 403. Otherwise, when accessing a non-existing schedule on a private
		// event will return a "schedule not found" page, leaking some event information on it.

		$key           = $request->query->get('key');
		$needsEventKey = strlen($event->getSecret()) > 0;
		$validEventKey = $needsEventKey && $this->hasGoodEventKey($event, $key);

		if ($needsEventKey && !$validEventKey && !$this->hasGoodSchedulesKey($event, $key)) {
			throw new Ex\ForbiddenException('This event is private.');
		}

		// resolve schedule
		$scheduleSlug = mb_strtolower($request->attributes->get('scheduleslug'));
		$scheduleRepo = $this->getRepository('Schedule');
		$schedule     = $scheduleRepo->findOneBy(['event' => $event, 'slug' => $scheduleSlug]);

		if (!$schedule) {
			$key = $request->query->get('key');

			return [new Response($this->renderScheduleNotFound($event, $key), 404), $event];
		}

		return [$schedule, $event];
	}

	protected function hasGoodEventKey(Event $event, $key) {
		return $this->hasGoodKey($event->getSecret(), $key);
	}

	protected function hasGoodSchedulesKey(Event $event, $key) {
		foreach ($event->getSchedules() as $schedule) {
			if ($this->hasGoodScheduleKey($schedule, $key)) {
				return true;
			}
		}

		return false;
	}

	protected function hasGoodScheduleKey(Schedule $schedule, $key) {
		return $this->hasGoodKey($schedule->getSecret(), $key);
	}

	private function hasGoodKey($secret, $key) {
		return !$secret || $key === $secret;
	}

	protected function getAllowedSchedules(Event $event, $key) {
		$schedules     = [];
		$validEventKey = strlen($event->getSecret()) > 0 && $this->hasGoodEventKey($event, $key);

		foreach ($event->getSchedules() as $s) {
			if ($validEventKey || $this->hasGoodScheduleKey($s, $key)) {
				$schedules[] = $s;
			}
		}

		return $schedules;
	}

	/**
	 * Check if the current page is something private.
	 *
	 * Basically everytime a key is involved, a page is private. This means that
	 * a public schedule in a public event can be private, if a key for another,
	 * private schedule is given (because in this case the dropdown menu in the
	 * navigation is different).
	 *
	 * @param  Event   $event
	 * @return boolean
	 */
	protected function isPrivatePage(Event $event) {
		$isPrivate = strlen($event->getSecret()) > 0;

		foreach ($event->getSchedules() as $schedule) {
			$isPrivate |= strlen($schedule->getSecret()) > 0;
		}

		return $isPrivate;
	}

	/**
	 * Handle access of schedules
	 *
	 * Schedules are special in the way they handle the not-found status. Since event
	 * slugs can be enumerated by simply registering and trying if a slug is still
	 * available, the same does not apply to schedules. This is why we respond with a
	 * 404 to not leak schedule names if the client had at least access to the event.
	 * This situation basically can only happen if an event is public and [one of] the
	 * schedule[s] is private and the client is trying to view a private schedule
	 * without the proper key.
	 *
	 * @param  Event    $event
	 * @param  Schedule $schedule
	 * @param  string   $key
	 * @return mixed
	 */
	protected function handleScheduleAccess(Event $event, Schedule $schedule, $key) {
		$needsEventKey    = strlen($event->getSecret()) > 0;
		$needsScheduleKey = strlen($schedule->getSecret()) > 0;
		$validEventKey    = $needsEventKey    && $this->hasGoodEventKey   ($event,    $key);
		$validScheduleKey = $needsScheduleKey && $this->hasGoodScheduleKey($schedule, $key);

		$eventAccess    = !$needsEventKey || $validEventKey;
		$scheduleAccess = !$needsScheduleKey || $validScheduleKey || $validEventKey;

		if (!$scheduleAccess) {
			if ($eventAccess) {
				return new Response($this->renderScheduleNotFound($event, $key), 404);
			}
			else {
				throw new Ex\ForbiddenException('This event is private.');
			}
		}

		return true;
	}

	protected function renderScheduleNotFound(Event $event, $key) {
		return $this->render('frontend/schedule/not_found.twig', [
			'event'     => $event,
			'key'       => $key,
			'schedules' => $this->getAllowedSchedules($event, $key),
			'isPrivate' => $this->isPrivatePage($event)
		]);
	}

	protected function setCachingHeader(Schedule $schedule = null, Response $response) {
		if ($schedule) {
			$response->setLastModified($schedule->getUpdatedAt());
		}

		$response->setTtl(5*60);       // 5 minutes
		$response->setClientTtl(5*60);

		return $response;
	}
}
