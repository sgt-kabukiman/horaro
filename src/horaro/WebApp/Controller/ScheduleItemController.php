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

use horaro\Library\Entity\Schedule;
use horaro\Library\Entity\ScheduleItem;
use horaro\Library\ReadableTime;
use horaro\WebApp\Exception as Ex;
use horaro\WebApp\Validator\ScheduleItemValidator;
use Symfony\Component\HttpFoundation\Request;

class ScheduleItemController extends BaseController {
	public function createAction(Request $request) {
		$schedule = $this->getRequestedSchedule($request);

		if ($this->exceedsMaxScheduleItems($schedule)) {
			throw new Ex\BadRequestException('You cannot create more rows in this schedule.');
		}

		$payload   = $this->getPayload($request);
		$validator = $this->app['validator.schedule.item'];
		$result    = $validator->validateNew($payload, $schedule);

		if ($result['_errors']) {
			$response = [];

			foreach ($result as $field => $state) {
				if ($field === '_errors') continue;
				if (!$state['errors']) continue;

				$response[$field] = $state['messages'];
			}

			return $this->respondWithArray(['errors' => $response], 400);
		}

		$em   = $this->getEntityManager();
		$item = $em->transactional(function($em) use ($schedule, $result) {
			$this->lockSchedule($schedule);

			// find max position

			$repo = $this->getRepository('ScheduleItem');
			$last = $repo->findOneBySchedule($schedule, ['position' => 'DESC']);
			$max  = $last ? $last->getPosition() : 0;

			// prepare new item

			$item = new ScheduleItem();
			$item->setSchedule($schedule);
			$item->setLengthInSeconds($result['length']['filtered']);
			$item->setPosition($max + 1);
			$item->setExtra($result['columns']['filtered']);

			$schedule->touch();

			// store it

			$em->persist($schedule);
			$em->persist($item);
			$em->flush();

			return $item;
		});

		// respond

		return $this->respondWithItem($item, 201);
	}

	public function patchAction(Request $request) {
		$item      = $this->getRequestedScheduleItem($request);
		$schedule  = $item->getSchedule();
		$payload   = $this->getPayload($request);
		$validator = $this->app['validator.schedule.item'];
		$result    = $validator->validateUpdate($payload, $item, $schedule);

		if ($result['_errors']) {
			$response = [];

			foreach ($result as $field => $state) {
				if ($field === '_errors') continue;
				if (!$state['errors']) continue;

				$response[$field] = $state['messages'];
			}

			return $this->respondWithArray(['errors' => $response], 400);
		}

		// update item

		if ($result['length']['filtered'] !== null) {
			$item->setLengthInSeconds($result['length']['filtered']);
		}

		$extra = $item->getExtra();

		foreach ($result['columns']['filtered'] as $colID => $newValue) {
			$extra[$colID] = $newValue;
		}

		$item->setExtra($extra);
		$schedule->touch();

		// store it

		$this->getEntityManager()->flush();

		// respond

		return $this->respondWithItem($item, 200);
	}

	public function deleteAction(Request $request) {
		$schedule = $this->getRequestedSchedule($request);
		$item     = $this->getRequestedScheduleItem($request, $schedule);

		// delete item and move followers one position up

		$em = $this->getEntityManager();
		$em->transactional(function($em) use ($item, $schedule) {
			$this->lockSchedule($schedule);

			// re-fetch the item to get its actual current position
			$item = $this->getRepository('ScheduleItem')->findOneById($item->getId());

			$qb    = $em->createQueryBuilder();
			$query = $qb
				->update('horaro\Library\Entity\ScheduleItem', 'i')
				->set('i.position', 'i.position - 1')
				->where($qb->expr()->andX(
					$qb->expr()->eq('i.schedule', $schedule->getId()),
					$qb->expr()->gt('i.position', $item->getPosition())
				))
				->getQuery();

			$query->getResult();

			$schedule->touch();

			$em->remove($item);
			$em->flush();
		});

		// respond

		return $this->respondWithArray(['data' => true], 200);
	}

	public function moveAction(Request $request) {
		$schedule = $this->getRequestedSchedule($request);
		$payload  = $this->getPayload($request);

		// get the item

		if (!isset($payload['item']) || !is_scalar($payload['item'])) {
			throw new Ex\BadRequestException('No item ID given.');
		}

		$em   = $this->getEntityManager();
		$item = $em->transactional(function($em) use ($schedule, $payload) {
			$this->lockSchedule($schedule);

			$itemID   = $payload['item'];
			$resolver = $this->app['resource-resolver'];
			$item     = $resolver->resolveScheduleItemID($itemID, true);

			if ($schedule->getId() !== $item->getSchedule()->getId()) {
				throw new Ex\NotFoundException('Schedule item '.$itemID.' could not be found.');
			}

			$curPos = $item->getPosition();

			if ($curPos < 1) {
				throw new Ex\BadRequestException('This item is already at position 0. This sould never happen.');
			}

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

			$schedule->touch();
			$item->setPosition($target);

			$em->flush();

			return $item;
		});

		// respond

		return $this->respondWithArray([
			'data' => [
				'id'  => $this->encodeID($item->getId(), 'schedule.item'),
				'pos' => $item->getPosition()
			]
		], 200);
	}

	protected function respondWithItem(ScheduleItem $item, $status) {
		$extraData = [];

		foreach ($item->getExtra() as $colID => $value) {
			$extraData[$this->encodeID($colID, 'schedule.column')] = $value;
		}

		$setupTime = $item->getSetupTime();
		if ($setupTime === null)
			$setupTime = $item->getSchedule()->getSetupTime();
		$setupTime = ReadableTime::dateTimeToSeconds($setupTime);

		return $this->respondWithArray([
			'data' => [
				'id'      => $this->encodeID($item->getId(), 'schedule.item'),
				'pos'     => $item->getPosition(),
				'length'  => $item->getLengthInSeconds(),
				'columns' => $extraData,
				'setup'   => $setupTime
			]
		], $status);
	}

	protected function lockSchedule(Schedule $schedule) {
		$this->getRepository('Schedule')->transientLock($schedule);
	}
}
