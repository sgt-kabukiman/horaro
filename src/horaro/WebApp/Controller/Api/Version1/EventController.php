<?php
/*
 * Copyright (c) 2015, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Controller\Api\Version1;

use horaro\Library\Entity\Event;
use horaro\WebApp\Controller\Api\BaseController;
use horaro\WebApp\Exception\NotFoundException;
use horaro\WebApp\Pager;
use horaro\WebApp\Transformer\Version1\EventTransformer;
use horaro\WebApp\Transformer\Version1\ScheduleTransformer;
use Symfony\Component\HttpFoundation\Request;

class EventController extends BaseController {
	public function indexAction(Request $request) {
		// determine current page
		$pager  = $this->app['api.v1.pager'];
		$offset = $pager->getOffset();
		$size   = $pager->getPageSize();

		// determine direction
		$allowed   = ['name' => 'e.name'];
		$orderBy   = $pager->getOrder(array_keys($allowed), 'name');
		$direction = $pager->getDirection('ASC');
		$orderBy   = $allowed[$orderBy];

		// prepare query builder
		$queryBuilder = $this->getRepository('Event')->createQueryBuilder('e')
			->where('e.secret IS NULL')
			->orderBy($orderBy, $direction)
			->setFirstResult($offset)
			->setMaxResults($size)
		;

		// filter by name
		$name = trim($request->query->get('name'));

		if (mb_strlen($name) > 0) {
			$queryBuilder
				->andWhere('e.name LIKE :name')
				->setParameter('name', '%'.addcslashes($name, '%_').'%')
			;
		}

		// find events
		$events = $queryBuilder->getQuery()->getResult();

		return $this->respondWithCollection($events, new EventTransformer($this->app), $pager);
	}

	public function viewAction(Request $request) {
		list ($event, $bySlug) = $this->resolveEvent($request);

		if ($bySlug) {
			$codec = $this->app['obscurity-codec'];
			$id    = $codec->encode($event->getID(), 'event');

			return $this->redirect('/-/api/v1/events/'.$id);
		}

		return $this->respondWithItem($event, new EventTransformer($this->app));
	}

	public function schedulesAction(Request $request) {
		list ($event, $bySlug) = $this->resolveEvent($request);

		if ($bySlug) {
			$codec = $this->app['obscurity-codec'];
			$id    = $codec->encode($event->getID(), 'event');

			return $this->redirect('/-/api/v1/events/'.$id.'/schedules');
		}

		$schedules = [];

		foreach ($event->getSchedules() as $schedule) {
			if ($schedule->isPublic()) {
				$schedules[] = $schedule;
			}
		}

		return $this->respondWithCollection($schedules, new ScheduleTransformer($this->app, false));
	}

	public function scheduleAction(Request $request) {
		list ($event)    = $this->resolveEvent($request);
		list ($schedule) = $this->resolveSchedule($event, $request);

		$codec      = $this->app['obscurity-codec'];
		$scheduleID = $codec->encode($schedule->getID(), 'schedule');

		return $this->redirect('/-/api/v1/schedules/'.$scheduleID);
	}

	public function scheduleTickerAction(Request $request) {
		list ($event)    = $this->resolveEvent($request);
		list ($schedule) = $this->resolveSchedule($event, $request);

		$codec      = $this->app['obscurity-codec'];
		$scheduleID = $codec->encode($schedule->getID(), 'schedule');

		return $this->redirect('/-/api/v1/schedules/'.$scheduleID.'/ticker');
	}

	private function resolveEvent(Request $request) {
		$event   = null;
		$bySlug  = false;
		$eventID = $request->attributes->get('eventid');
		$codec   = $this->app['obscurity-codec'];
		$repo    = $this->getRepository('Event');
		$id      = $codec->decode($eventID, 'event');

		if ($id === null) {
			$bySlug = true;
			$event  = $repo->findOneBySlug($eventID);
		}
		else {
			$event = $repo->findOneById($id);
		}

		if (!$event || !$event->isPublic()) {
			throw new NotFoundException('Event '.$eventID.' could not be found.');
		}

		return [$event, $bySlug];
	}

	private function resolveSchedule(Event $event, Request $request) {
		$schedule   = null;
		$bySlug     = false;
		$scheduleID = $request->attributes->get('scheduleid');
		$codec      = $this->app['obscurity-codec'];
		$repo       = $this->getRepository('Schedule');
		$id         = $codec->decode($scheduleID, 'schedule');

		if ($id === null) {
			$bySlug   = true;
			$schedule = $repo->findOneBy([
				'event' => $event,
				'slug'  => $scheduleID
			]);
		}
		else {
			$schedule = $repo->findOneBy([
				'event' => $event,
				'id'    => $id
			]);
		}

		if (!$schedule || !$schedule->isPublic()) {
			throw new NotFoundException('Schedule '.$scheduleID.' could not be found.');
		}

		return [$schedule, $bySlug];
	}
}
