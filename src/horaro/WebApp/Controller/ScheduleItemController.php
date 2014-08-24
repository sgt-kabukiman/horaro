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
use Symfony\Component\HttpFoundation\JsonResponse;

class ScheduleItemController extends BaseController {
	public function createAction(Request $request) {
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
		$schedule  = $this->getRequestedSchedule($request);
		$item      = $this->getRequestedItem($request, $schedule);
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

	protected function getRequestedSchedule(Request $request) {
		$hash = $request->attributes->get('id');
		$id   = $this->decodeID($hash, 'schedule');

		if ($id === null) {
			throw new Ex\NotFoundException('The schedule could not be found.');
		}

		$repo     = $this->getRepository('Schedule');
		$schedule = $repo->findOneById($id);

		if (!$schedule) {
			throw new Ex\NotFoundException('Schedule '.$hash.' could not be found.');
		}

		$user  = $this->getCurrentUser();
		$owner = $schedule->getEvent()->getUser();

		if (!$owner || $user->getId() !== $owner->getId()) {
			throw new Ex\NotFoundException('Schedule '.$hash.' could not be found.');
		}

		return $schedule;
	}

	protected function getRequestedItem(Request $request, Schedule $schedule) {
		$hash = $request->attributes->get('itemid');
		$id   = $this->decodeID($hash, 'schedule.item');

		if ($id === null) {
			throw new Ex\NotFoundException('The item could not be found.');
		}

		$repo = $this->getRepository('ScheduleItem');
		$item = $repo->findOneById($id);

		if (!$item) {
			throw new Ex\NotFoundException('Schedule item '.$hash.' could not be found.');
		}

		$user  = $this->getCurrentUser();
		$owner = $schedule->getEvent()->getUser();

		if ($item->getSchedule()->getId() != $schedule->getId()) {
			throw new Ex\NotFoundException('Schedule item '.$hash.' could not be found.');
		}

		return $item;
	}
}
