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

use horaro\Library\Entity\Schedule;
use horaro\WebApp\Exception as Ex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FrontendController extends BaseController {
	public function scheduleAction(Request $request) {
		list($schedule, $event) = $this->resolveSchedule($request);
		if ($schedule instanceof Response) return $schedule;

		$content = $this->render('frontend/schedule/schedule.twig', [
			'event'    => $event,
			'schedule' => $schedule
		]);

		$response = new Response($content, 200, ['content-type' => 'text/html; charset=UTF-8']);

		return $this->setCachingHeader($schedule, $response);
	}

	public function scheduleExportAction(Request $request) {
		list($schedule, $event) = $this->resolveSchedule($request);
		if ($schedule instanceof Response) return $schedule;

		$format  = strtolower($request->attributes->get('format'));
		$formats = ['json', 'xml', 'csv', 'ical'];

		if (!in_array($format, $formats, true)) {
			throw new Ex\BadRequestException('Invalid format "'.$format.'" given.');
		}

		$id          = 'schedule-transformer-'.$format;
		$transformer = $this->app[$id];
		$data        = $transformer->transform($schedule, true);
		$filename    = sprintf('%s-%s.%s', $event->getSlug(), $schedule->getSlug(), $transformer->getFileExtension());
		$headers     = ['Content-Type' => $transformer->getContentType()];

		if ($request->query->get('named')) {
			$headers['Content-Disposition'] = 'filename="'.$filename.'"';
		}

		$response = new Response($data, 200, $headers);

		return $this->setCachingHeader($schedule, $response);
	}

	public function icalFaqAction(Request $request) {
		list($schedule, $event) = $this->resolveSchedule($request);
		if ($schedule instanceof Response) return $schedule;

		$content = $this->render('frontend/schedule/ical.twig', [
			'event'    => $event,
			'schedule' => $schedule,
		]);

		$response = new Response($content, 200, ['Content-Type' => 'text/html; charset=UTF-8']);

		return $this->setCachingHeader($schedule, $response);
	}

	public function eventAction(Request $request) {
		$event = $this->resolveEvent($request);
		if ($event instanceof Response) return $event;

		$content  = $this->render('frontend/event/event.twig', ['event' => $event]);
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

		// resolve schedule
		$scheduleSlug = mb_strtolower($request->attributes->get('scheduleslug'));
		$scheduleRepo = $this->getRepository('Schedule');
		$schedule     = $scheduleRepo->findOneBy(['event' => $event, 'slug' => $scheduleSlug]);

		if (!$schedule) {
			$content = $this->render('frontend/schedule/not_found.twig', ['event' => $event]);

			return [new Response($content, 404), $event];
		}

		return [$schedule, $event];
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
