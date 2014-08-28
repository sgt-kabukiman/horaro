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

class ScheduleColumnController extends BaseController {
	public function editAction(Request $request) {
		$schedule = $this->getRequestedSchedule($request);
		$columns  = [];

		foreach ($schedule->getColumns() as $col) {
			$columns[] = [$col->getID(), $col->getName()];
		}

		return $this->render('schedule/columns.twig', ['schedule' => $schedule, 'columns' => $columns]);
	}
}
