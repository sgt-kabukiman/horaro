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

use horaro\Library\Entity\User;
use horaro\Library\Entity\Event;
use horaro\Library\Entity\Schedule;
use horaro\Library\Entity\ScheduleItem;
use horaro\WebApp\Application;
use horaro\WebApp\Exception as Ex;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class BaseController {
	protected $app;

	public function __construct(Application $app) {
		$this->app = $app;
	}

	protected function redirect($uri, $status = 302) {
		return new RedirectResponse($uri, $status);
	}

	protected function render($template, array $params = []) {
		return $this->app['twig']->render($template, $params);
	}

	protected function getEntityManager() {
		return $this->app['entitymanager'];
	}

	protected function getRepository($className) {
		return $this->getEntityManager()->getRepository('horaro\Library\Entity\\'.$className);
	}

	public function getCurrentUser() {
		return $this->app['user'];
	}

	protected function encodeID($id, $entityType = null) {
		return $id;
	}

	protected function decodeID($hash, $entityType = null) {
		if (!ctype_digit($hash)) {
			return null;
		}

		$id = (int) $hash;

		return $id ?: null;
	}

	protected function getPayload(Request $request, $asArray = true) {
		$content = $request->getContent();
		$payload = @json_decode($content, $asArray);
		$error   = json_last_error();

		if ($error !== JSON_ERROR_NONE) {
			throw new Ex\BadRequestException('Request does not contain valid JSON.', 900);
		}

		return $payload;
	}

	protected function respondWithArray($content = [], $status = 200, array $headers = []) {
		$response = new JsonResponse($content, $status, $headers);

		$response->setExpires(new \DateTime('1924-10-10 12:00:00 UTC'));
		$response->headers->addCacheControlDirective('no-cache', true);
		$response->headers->addCacheControlDirective('private', true);

		return $response;
	}

	protected function checkCsrfToken(Request $request) {
		$this->app['csrf']->checkToken($request, $this->app['session']);
	}

	protected function getRequestedEvent(Request $request) {
		return $this->resolveEventID($request->attributes->get('event'));
	}

	protected function resolveEventID($eventID) {
		$id = $this->decodeID((string) $eventID, 'event');

		if ($id === null) {
			throw new Ex\NotFoundException('The event could not be found.');
		}

		$repo  = $this->getRepository('Event');
		$event = $repo->findOneById($id);

		if (!$event) {
			throw new Ex\NotFoundException('Event '.$eventID.' could not be found.');
		}

		$user  = $this->getCurrentUser();
		$owner = $event->getUser();

		if (!$owner || $user->getId() !== $owner->getId()) {
			throw new Ex\NotFoundException('Event '.$eventID.' could not be found.');
		}

		return $event;
	}

	protected function getRequestedSchedule(Request $request) {
		return $this->resolveScheduleID($request->attributes->get('schedule'));
	}

	protected function resolveScheduleID($scheduleID) {
		$id = $this->decodeID((string) $scheduleID, 'schedule');

		if ($id === null) {
			throw new Ex\NotFoundException('The schedule could not be found.');
		}

		$repo     = $this->getRepository('Schedule');
		$schedule = $repo->findOneById($id);

		if (!$schedule) {
			throw new Ex\NotFoundException('Schedule '.$scheduleID.' could not be found.');
		}

		$user  = $this->getCurrentUser();
		$owner = $schedule->getEvent()->getUser();

		if (!$owner || $user->getId() !== $owner->getId()) {
			throw new Ex\NotFoundException('Schedule '.$scheduleID.' could not be found.');
		}

		return $schedule;
	}

	protected function getRequestedScheduleItem(Request $request, Schedule $schedule) {
		return $this->resolveScheduleItemID($request->attributes->get('item'), $schedule);
	}

	protected function resolveScheduleItemID($itemID, Schedule $schedule) {
		$id = $this->decodeID((string) $itemID, 'schedule.item');

		if ($id === null) {
			throw new Ex\NotFoundException('The item could not be found.');
		}

		$repo = $this->getRepository('ScheduleItem');
		$item = $repo->findOneById($id);

		if (!$item) {
			throw new Ex\NotFoundException('Schedule item '.$itemID.' could not be found.');
		}

		$user  = $this->getCurrentUser();
		$owner = $schedule->getEvent()->getUser();

		if ($item->getSchedule()->getId() != $schedule->getId()) {
			throw new Ex\NotFoundException('Schedule item '.$itemID.' could not be found.');
		}

		return $item;
	}

	protected function getRequestedScheduleColumn(Request $request, Schedule $schedule) {
		return $this->resolveScheduleColumnID($request->attributes->get('column'), $schedule);
	}

	protected function resolveScheduleColumnID($columnID, Schedule $schedule) {
		$id = $this->decodeID((string) $columnID, 'schedule.column');

		if ($id === null) {
			throw new Ex\NotFoundException('The column could not be found.');
		}

		$repo   = $this->getRepository('ScheduleColumn');
		$column = $repo->findOneById($id);

		if (!$column) {
			throw new Ex\NotFoundException('Schedule column '.$columnID.' could not be found.');
		}

		$user  = $this->getCurrentUser();
		$owner = $schedule->getEvent()->getUser();

		if ($column->getSchedule()->getId() != $schedule->getId()) {
			throw new Ex\NotFoundException('Schedule column '.$columnID.' could not be found.');
		}

		return $column;
	}

	protected function getRequestedUser(Request $request) {
		$id = $request->attributes->get('user');

		if ($id === null) {
			throw new Ex\NotFoundException('The user could not be found.');
		}

		$repo = $this->getRepository('User');
		$user = $repo->findOneById($id);

		if (!$user) {
			throw new Ex\NotFoundException('User '.$id.' could not be found.');
		}

		return $user;
	}

	protected function getLanguages() {
		return ['de_de' => 'Deutsch', 'en_us' => 'English (US)'];
	}

	protected function getDefaultLanguage() {
		return 'en_us';
	}

	protected function addFlashMsg($type, $message) {
		$this->app['session']->getFlashBag()->add($type, $message);
	}

	protected function addSuccessMsg($message) {
		$this->addFlashMsg('success', $message);
	}

	protected function addErrorMsg($message) {
		$this->addFlashMsg('error', $message);
	}

	protected function exceedsMaxUsers() {
		$maxUsers = $this->app['config']['max_users'];
		$total    = $this->getEntityManager()
			->createQuery('SELECT COUNT(u.id) FROM horaro\Library\Entity\User u')
			->getSingleScalarResult();

		return $total >= $maxUsers;
	}

	protected function exceedsMaxEvents(User $u) {
		$total = $this->getEntityManager()
			->createQuery('SELECT COUNT(e.id) FROM horaro\Library\Entity\Event e WHERE e.user = :user')
			->setParameter('user', $u)
			->getSingleScalarResult();

		return $total >= $u->getMaxEvents();
	}

	protected function exceedsMaxSchedules(Event $e) {
		$total = $this->getEntityManager()
			->createQuery('SELECT COUNT(s.id) FROM horaro\Library\Entity\Schedule s WHERE s.event = :event')
			->setParameter('event', $e)
			->getSingleScalarResult();

		return $total >= $e->getMaxSchedules();
	}

	protected function exceedsMaxScheduleItems(Schedule $s) {
		$total = $this->getEntityManager()
			->createQuery('SELECT COUNT(i.id) FROM horaro\Library\Entity\ScheduleItem i WHERE i.schedule = :schedule')
			->setParameter('schedule', $s)
			->getSingleScalarResult();

		return $total >= $s->getMaxItems();
	}

	protected function exceedsMaxScheduleColumns(Schedule $s) {
		$total = $this->getEntityManager()
			->createQuery('SELECT COUNT(c.id) FROM horaro\Library\Entity\ScheduleColumn c WHERE c.schedule = :schedule')
			->setParameter('schedule', $s)
			->getSingleScalarResult();

		return $total >= 10;
	}
}
