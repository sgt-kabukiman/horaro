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

use horaro\Library\Entity\ScheduleItem;
use horaro\WebApp\Exception as Ex;
use horaro\WebApp\Validator\ScheduleItemValidator;
use Symfony\Component\HttpFoundation\Request;

class ScheduleItemController extends BaseController {
	public function createAction(Request $request) {
		$this->checkCsrfToken($request);

		$schedule  = $this->getRequestedSchedule($request);
		$payload   = $this->getPayload($request);
		$validator = new ScheduleItemValidator();
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

		// store it

		$em = $this->getEntityManager();
		$em->persist($item);
		$em->flush();

		// respond

		return $this->respondWithArray([
			'data' => [
				'id'      => $this->encodeID($item->getId(), 'schedule.item'),
				'pos'     => $item->getPosition(),
				'length'  => $item->getLengthInSeconds(),
				'columns' => $item->getExtra()
			]
		], 201);
	}

	public function patchAction(Request $request) {
		$this->checkCsrfToken($request);

		$schedule  = $this->getRequestedSchedule($request);
		$item      = $this->getRequestedScheduleItem($request, $schedule);
		$payload   = $this->getPayload($request);
		$validator = new ScheduleItemValidator();
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

		// store it

		$em = $this->getEntityManager();
		$em->persist($item);
		$em->flush();

		// respond

		return $this->respondWithArray([
			'data' => [
				'id'      => $this->encodeID($item->getId(), 'schedule.item'),
				'pos'     => $item->getPosition(),
				'length'  => $item->getLengthInSeconds(),
				'columns' => $item->getExtra()
			]
		], 200);
	}

	public function deleteAction(Request $request) {
		$this->checkCsrfToken($request);

		$schedule = $this->getRequestedSchedule($request);
		$item     = $this->getRequestedScheduleItem($request, $schedule);

		// delete item and move followers one position up

		$em = $this->getEntityManager();
		$em->transactional(function($em) use ($item) {
			$qb    = $em->createQueryBuilder();
			$query = $qb
				->update('horaro\Library\Entity\ScheduleItem', 'i')
				->set('i.position', 'i.position - 1')
				->where('i.position > '.$item->getPosition())
				->getQuery();

			$query->getResult();

			$em->remove($item);
			$em->flush();
		});

		// respond

		return $this->respondWithArray(['data' => true], 200);
	}

	public function moveAction(Request $request) {
		$this->checkCsrfToken($request);

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
}
