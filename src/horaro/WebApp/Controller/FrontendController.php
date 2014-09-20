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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FrontendController extends BaseController {
	public function scheduleAction(Request $request) {
		$eventSlug    = mb_strtolower($request->attributes->get('event'));
		$scheduleSlug = mb_strtolower($request->attributes->get('schedule'));

		// quickly fail if this is just a broken link somewhere in the backend or a missing asset
		if (in_array($eventSlug, ['-', 'assets'], true)) {
			return new Response('Not Found.', 404, ['content-type' => 'text/plain']);
		}

		// resolve event
		$eventRepo = $this->getRepository('Event');
		$event     = $eventRepo->findOneBySlug($eventSlug);

		if (!$event) {
			return $this->render('frontend/event_not_found.twig', [
				'event'        => null,
				'schedule'     => null,
				'eventSlug'    => $eventSlug,
				'scheduleSlug' => $scheduleSlug
			]);
		}

		// resolve schedule
		$scheduleRepo = $this->getRepository('Schedule');
		$schedule     = $scheduleRepo->findOneBy(['event' => $event, 'slug' => $scheduleSlug]);

		if (!$schedule) {
			return $this->render('frontend/schedule_not_found.twig', [
				'event'        => $event,
				'schedule'     => null,
				'eventSlug'    => $eventSlug,
				'scheduleSlug' => $scheduleSlug
			]);
		}

		$content = $this->render('frontend/schedule.twig', [
			'event'        => $event,
			'schedule'     => $schedule,
			'eventSlug'    => $eventSlug,
			'scheduleSlug' => $scheduleSlug
		]);

		$response = new Response($content, 200, ['content-type' => 'text/html; charset=UTF-8']);
		$response->setLastModified($schedule->getUpdatedAt());
		$response->setTtl(5*60);       // 5 minutes
		$response->setClientTtl(5*60);

		return $response;
	}
}
