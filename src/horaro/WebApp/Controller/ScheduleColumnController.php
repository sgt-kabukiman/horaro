<?php
/*
 * Copyright (c) 2015, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Controller;

use horaro\Library\Entity\Schedule;
use horaro\Library\Entity\ScheduleColumn;
use horaro\WebApp\Exception as Ex;
use horaro\WebApp\Validator\ScheduleColumnValidator;
use Symfony\Component\HttpFoundation\Request;

class ScheduleColumnController extends BaseController {
	public function editAction(Request $request) {
		$schedule = $this->getRequestedSchedule($request);
		$extra    = $schedule->getExtra();
		$columns  = [
			[Schedule::COLUMN_SCHEDULED, isset($extra['texts'][Schedule::COLUMN_SCHEDULED]) ? $extra['texts'][Schedule::COLUMN_SCHEDULED] : 'Scheduled', -1, true],
			[Schedule::COLUMN_ESTIMATE,  isset($extra['texts'][Schedule::COLUMN_ESTIMATE])  ? $extra['texts'][Schedule::COLUMN_ESTIMATE]  : 'Estimated',  0, true]
		];

		foreach ($schedule->getColumns() as $col) {
			$columns[] = [$this->encodeID($col->getID(), 'schedule.column'), $col->getName(), $col->getPosition(), false];
		}

		return $this->render('schedule/columns.twig', ['schedule' => $schedule, 'columns' => $columns]);
	}

	public function createAction(Request $request) {
		$schedule = $this->getRequestedSchedule($request);

		if ($this->exceedsMaxScheduleColumns($schedule)) {
			throw new Ex\BadRequestException('You cannot create more columns for this schedule.');
		}

		$payload   = $this->getPayload($request);
		$validator = $this->app['validator.schedule.column'];
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

		$em  = $this->getEntityManager();
		$col = $em->transactional(function($em) use ($schedule, $result) {
			$this->lockSchedule($schedule);

			// find max position

			$repo = $this->getRepository('ScheduleColumn');
			$last = $repo->findOneBySchedule($schedule, ['position' => 'DESC']);
			$max  = $last ? $last->getPosition() : 0;

			// prepare new column

			$col = new ScheduleColumn();
			$col->setSchedule($schedule);
			$col->setPosition($max + 1);
			$col->setName($result['name']['filtered']);

			$schedule->touch();

			// store it

			$em->persist($col);
			$em->flush();

			return $col;
		});

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
		$column    = $this->getRequestedScheduleColumn($request);
		$schedule  = $column->getSchedule();
		$payload   = $this->getPayload($request);
		$validator = $this->app['validator.schedule.column'];
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
		$schedule->touch();

		// store it

		$this->getEntityManager()->flush();

		// respond

		return $this->respondWithArray([
			'data' => [
				'id'   => $this->encodeID($column->getId(), 'schedule.column'),
				'pos'  => $column->getPosition(),
				'name' => $column->getName(),
			]
		], 200);
	}

	public function updateFixedAction(Request $request) {
		$schedule = $this->getRequestedSchedule($request);
		$key      = $request->attributes->get('column_key');

		if (!in_array($key, [Schedule::COLUMN_SCHEDULED, Schedule::COLUMN_ESTIMATE], true)) {
			return $this->respondWithArray('Column not found.', 404);
		}

		$payload = $this->getPayload($request);

		try {
			if (!isset($payload['name']) || !is_string($payload['name'])) {
				throw new \Exception('No valid name given.');
			}

			$name = trim($payload['name']);

			if (mb_strlen($name) === 0) {
				throw new \Exception('A column name cannot be empty.');
			}
		}
		catch (\Exception $e) {
			return $this->respondWithArray(['errors' => ['name' => $e->getMessage()]], 400);
		}

		// update column

		$extra = $schedule->getExtra();
		$extra['texts'][$key] = $name;

		$schedule->setExtra($extra);
		$schedule->touch();

		// store it

		$this->getEntityManager()->flush();

		// respond

		return $this->respondWithArray([
			'data' => [
				'id'   => $key,
				'pos'  => $key === Schedule::COLUMN_SCHEDULED ? -1 : 0,
				'name' => $name,
			]
		], 200);
	}

	public function deleteAction(Request $request) {
		$column   = $this->getRequestedScheduleColumn($request);
		$schedule = $column->getSchedule();

		// do not allow to delete the only column

		if ($schedule->getColumns()->count() === 1) {
			throw new Ex\ConflictException('The last column cannot be deleted.');
		}

		// delete column and move followers one position up

		$em = $this->getEntityManager();
		$em->transactional(function($em) use ($column, $schedule) {
			$this->lockSchedule($schedule);

			// re-fetch the column to get its actual current position
			$column = $this->getRepository('ScheduleColumn')->findOneById($column->getId());

			$qb    = $em->createQueryBuilder();
			$query = $qb
				->update('horaro\Library\Entity\ScheduleColumn', 'c')
				->set('c.position', 'c.position - 1')
				->where('c.position > '.$column->getPosition())
				->getQuery();

			$query->getResult();

			$schedule->touch();

			$em->remove($column);
			$em->flush();
		});

		// TODO: Cleanup schedule item data

		// respond

		return $this->respondWithArray(['data' => true], 200);
	}

	public function moveAction(Request $request) {
		$schedule = $this->getRequestedSchedule($request);
		$payload  = $this->getPayload($request);

		// get the column

		if (!isset($payload['column']) || !is_scalar($payload['column'])) {
			throw new Ex\BadRequestException('No column ID given.');
		}

		$em  = $this->getEntityManager();
		$col = $em->transactional(function($em) use ($schedule, $payload) {
			$this->lockSchedule($schedule);

			$colID    = $payload['column'];
			$resolver = $this->app['resource-resolver'];
			$col      = $resolver->resolveScheduleColumnID($colID, true);

			if ($schedule->getId() !== $col->getSchedule()->getId()) {
				throw new Ex\NotFoundException('Schedule column '.$colID.' could not be found.');
			}

			$curPos = $col->getPosition();

			if ($curPos < 1) {
				throw new Ex\BadRequestException('This column is already at position 0. This sould never happen.');
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

			$em->flush();

			return $col;
		});

		// respond

		return $this->respondWithArray([
			'data' => [
				'id'  => $this->encodeID($col->getId(), 'schedule.column'),
				'pos' => $col->getPosition()
			]
		], 200);
	}

	protected function lockSchedule(Schedule $schedule) {
		$this->getRepository('Schedule')->transientLock($schedule);
	}
}
