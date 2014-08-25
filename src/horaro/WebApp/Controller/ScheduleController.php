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
use horaro\Library\Entity\ScheduleItem;
use horaro\WebApp\Exception as Ex;
use horaro\WebApp\Validator\ScheduleValidator;
use horaro\WebApp\Validator\ScheduleItemValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ScheduleController extends BaseController {
	public function detailAction(Request $request) {
		$schedule  = $this->getRequestedSchedule($request);
		$items     = [];
		$columnIDs = [];

		foreach ($schedule->getItems() as $item) {
			$items[] = [
				$item->getId(),
				$item->getLengthInSeconds(),
				$item->getExtra()
			];
		}

		foreach ($schedule->getColumns() as $column) {
			$columnIDs[] = $column->getId();
		}

		return $this->render('schedule/detail.twig', ['schedule' => $schedule, 'items' => $items ?: null, 'columns' => $columnIDs]);
	}

	public function newAction(Request $request) {
		$event = $this->getRequestedEvent($request);

		return $this->renderForm($event);
	}

	public function createAction(Request $request) {
		$event     = $this->getRequestedEvent($request);
		$validator = new ScheduleValidator($this->getRepository('Schedule'));
		$result    = $validator->validate([
			'name'       => $request->request->get('name'),
			'slug'       => $request->request->get('slug'),
			'timezone'   => $request->request->get('timezone'),
			'twitch'     => $request->request->get('twitch'),
			'start_date' => $request->request->get('start_date'),
			'start_time' => $request->request->get('start_time')
		], $event);

		if ($result['_errors']) {
			return $this->renderForm($event, null, $result);
		}

		// create schedule

		$user     = $this->getCurrentUser();
		$schedule = new Schedule();

		$schedule
			->setEvent($event)
			->setName($result['name']['filtered'])
			->setSlug($result['slug']['filtered'])
			->setTimezone($result['timezone']['filtered'])
			->setUpdatedAt(new \DateTime('now UTC'))
			->setStart($result['start']['filtered'])
//			->setTwitch($result['twitch']['filtered'])
		;

		$em = $this->getEntityManager();
		$em->persist($schedule);
		$em->flush();

		// done

		return $this->redirect('/-/schedules/'.$schedule->getId());
	}

	public function editAction(Request $request) {
		$schedule = $this->getRequestedSchedule($request);

		return $this->renderForm($schedule->getEvent(), $schedule, null);
	}

	public function updateAction(Request $request) {
		$schedule  = $this->getRequestedSchedule($request);
		$event     = $schedule->getEvent();
		$validator = new ScheduleValidator($this->getRepository('Schedule'));
		$result    = $validator->validate([
			'name'       => $request->request->get('name'),
			'slug'       => $request->request->get('slug'),
			'timezone'   => $request->request->get('timezone'),
			'twitch'     => $request->request->get('twitch'),
			'start_date' => $request->request->get('start_date'),
			'start_time' => $request->request->get('start_time')
		], $event, $schedule);

		if ($result['_errors']) {
			return $this->renderForm($event, $schedule, $result);
		}

		// update

		$schedule
			->setName($result['name']['filtered'])
			->setSlug($result['slug']['filtered'])
			->setTimezone($result['timezone']['filtered'])
			->setUpdatedAt(new \DateTime('now UTC'))
			->setStart($result['start']['filtered'])
//			->setTwitch($result['twitch']['filtered'])
		;

		$em = $this->getEntityManager();
		$em->persist($schedule);
		$em->flush();

		// done

		return $this->redirect('/-/schedules/'.$schedule->getId());
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

		return $this->redirect('/-/events/'.$eventID);
	}

	public function moveItemAction(Request $request) {
		$schedule = $this->getRequestedSchedule($request);
		$payload  = $this->getPayload($request);

		// get the item

		if (!isset($payload['item']) || !is_scalar($payload['item'])) {
			throw new Ex\BadRequestException('No item ID given.');
		}

		$itemID = $payload['item'];
		$item   = $this->resolveScheduleItemID($itemID, $schedule);
		$curPos = $item->getPosition();

		// get the target position

		if (!isset($payload['position']) || !is_int($payload['position'])) {
			throw new Ex\BadRequestException('No valid target position given.');
		}

		$target = (int) $payload['position'];

		// validate the target position

		if ($target < 1) {
			throw new Ex\BadRequestException('Positions are 1-based and therefore cannot be < 1.');
		}

		if ($target === $curPos) {
			throw new Ex\ConflictException('This would be a NOP.');
		}

		$repo = $this->getRepository('ScheduleItem');
		$last = $repo->findOneBySchedule($schedule, ['position' => 'DESC']);
		$max  = $last->getPosition();

		if ($target > $max) {
			throw new Ex\BadRequestException('Target position ('.$target.') is greater than the last position ('.$max.').');
		}

		// prepare chunk move

		$up          = $target < $curPos;
		$relation    = $up ? '+' : '-';
		list($a, $b) = $up ? array($target, $curPos) : array($curPos, $target);

		// move items between old and new position

		$em = $this->getEntityManager();
		$em->transactional(function($em) use ($relation, $item, $schedule, $a, $b, $target) {
			$qb    = $em->createQueryBuilder();
			$query = $qb
				->update('horaro\Library\Entity\ScheduleItem', 'i')
				->set('i.position', sprintf('i.position %s 1', $relation))
				->where($qb->expr()->andX(
					$qb->expr()->eq('i.schedule', $schedule->getId()),
					$qb->expr()->between('i.position', $a, $b)
				))
				->getQuery();

			$query->getResult();

			$item->setPosition($target);
			$em->persist($item);
			$em->flush();
		});

		// respond

		return $this->respondWithArray([
			'data' => [
				'id'  => $this->encodeID($item->getId(), 'schedule.item'),
				'pos' => $item->getPosition()
			]
		], 200);
	}

	protected function renderForm(Event $event, Schedule $schedule = null, $result = null) {
		$timezones = \DateTimeZone::listIdentifiers();

		return $this->render('schedule/form.twig', [
			'event'     => $event,
			'timezones' => $timezones,
			'schedule'  => $schedule,
			'result'    => $result
		]);
	}
}
