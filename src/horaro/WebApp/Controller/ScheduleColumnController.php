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

use horaro\Library\Entity\ScheduleColumn;
use horaro\WebApp\Exception as Ex;
use horaro\WebApp\Validator\ScheduleColumnValidator;
use Symfony\Component\HttpFoundation\Request;

class ScheduleColumnController extends BaseController {
	public function editAction(Request $request) {
		$schedule = $this->getRequestedSchedule($request);
		$columns  = [];

		foreach ($schedule->getColumns() as $col) {
			$columns[] = [$col->getID(), $col->getName()];
		}

		return $this->render('schedule/columns.twig', ['schedule' => $schedule, 'columns' => $columns]);
	}

	public function createAction(Request $request) {
		// do not leak information, check CSRF token before checking for max columns
		$this->checkCsrfToken($request);

		$schedule = $this->getRequestedSchedule($request);

		if ($this->exceedsMaxScheduleColumns($schedule)) {
			throw new Ex\BadRequestException('You cannot create more columns for this schedule.');
		}

		$payload   = $this->getPayload($request);
		$validator = new ScheduleColumnValidator();
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

		$repo = $this->getRepository('ScheduleColumn');
		$last = $repo->findOneBySchedule($schedule, ['position' => 'DESC']);
		$max  = $last ? $last->getPosition() : 0;

		// prepare new column

		$col = new ScheduleColumn();
		$col->setSchedule($schedule);
		$col->setPosition($max + 1);
		$col->setName($result['name']['filtered']);

		// store it

		$em = $this->getEntityManager();
		$em->persist($col);
		$em->flush();

		// respond

		return $this->respondWithArray([
			'data' => [
				'id'   => $this->encodeID($col->getId(), 'schedule.column'),
				'pos'  => $col->getPosition(),
				'name' => $col->getName()
			]
		], 201);
	}

	public function updateAction(Request $request) {
		$this->checkCsrfToken($request);

		$schedule  = $this->getRequestedSchedule($request);
		$column    = $this->getRequestedScheduleColumn($request, $schedule);
		$payload   = $this->getPayload($request);
		$validator = new ScheduleColumnValidator();
		$result    = $validator->validateUpdate($payload, $column, $schedule);

		if ($result['_errors']) {
			$response = [];

			foreach ($result as $field => $state) {
				if ($field === '_errors') continue;
				if (!$state['errors']) continue;

				$response[$field] = $state['messages'];
			}

			return $this->respondWithArray(['errors' => $response], 400);
		}

		// update column

		$column->setName($result['name']['filtered']);

		// store it

		$em = $this->getEntityManager();
		$em->persist($column);
		$em->flush();

		// respond

		return $this->respondWithArray([
			'data' => [
				'id'   => $this->encodeID($column->getId(), 'schedule.column'),
				'pos'  => $column->getPosition(),
				'name' => $column->getName(),
			]
		], 200);
	}

	public function deleteAction(Request $request) {
		$this->checkCsrfToken($request);

		$schedule = $this->getRequestedSchedule($request);
		$column   = $this->getRequestedScheduleColumn($request, $schedule);

		// do not allow to delete the only column

		if ($schedule->getColumns()->count() === 1) {
			throw new Ex\ConflictException('The last column cannot be deleted.');
		}

		// delete column and move followers one position up

		$em = $this->getEntityManager();
		$em->transactional(function($em) use ($column) {
			$qb    = $em->createQueryBuilder();
			$query = $qb
				->update('horaro\Library\Entity\ScheduleColumn', 'c')
				->set('c.position', 'c.position - 1')
				->where('c.position > '.$column->getPosition())
				->getQuery();

			$query->getResult();

			$em->remove($column);
			$em->flush();
		});

		// TODO: Cleanup schedule item data

		// respond

		return $this->respondWithArray(['data' => true], 200);
	}

	public function moveAction(Request $request) {
		$this->checkCsrfToken($request);

		$schedule = $this->getRequestedSchedule($request);
		$payload  = $this->getPayload($request);

		// get the column

		if (!isset($payload['column']) || !is_scalar($payload['column'])) {
			throw new Ex\BadRequestException('No column ID given.');
		}

		$colID  = $payload['column'];
		$col    = $this->resolveScheduleColumnID($colID, $schedule);
		$curPos = $col->getPosition();

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

		$repo = $this->getRepository('ScheduleColumn');
		$last = $repo->findOneBySchedule($schedule, ['position' => 'DESC']);
		$max  = $last->getPosition();

		if ($target > $max) {
			throw new Ex\BadRequestException('Target position ('.$target.') is greater than the last position ('.$max.').');
		}

		// prepare chunk move

		$up          = $target < $curPos;
		$relation    = $up ? '+' : '-';
		list($a, $b) = $up ? array($target, $curPos) : array($curPos, $target);

		// move columns between old and new position

		$em = $this->getEntityManager();
		$em->transactional(function($em) use ($relation, $col, $schedule, $a, $b, $target) {
			$qb    = $em->createQueryBuilder();
			$query = $qb
				->update('horaro\Library\Entity\ScheduleColumn', 'c')
				->set('c.position', sprintf('c.position %s 1', $relation))
				->where($qb->expr()->andX(
					$qb->expr()->eq('c.schedule', $schedule->getId()),
					$qb->expr()->between('c.position', $a, $b)
				))
				->getQuery();

			$query->getResult();

			$col->setPosition($target);
			$em->persist($col);
			$em->flush();
		});

		// respond

		return $this->respondWithArray([
			'data' => [
				'id'  => $this->encodeID($col->getId(), 'schedule.column'),
				'pos' => $col->getPosition()
			]
		], 200);
	}
}
