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

use horaro\WebApp\Controller\Api\BaseController;
use horaro\WebApp\Exception\NotFoundException;
use horaro\WebApp\Transformer\Version1\ScheduleTransformer;
use horaro\WebApp\Transformer\Version1\ScheduleTickerTransformer;
use Symfony\Component\HttpFoundation\Request;

class ScheduleController extends BaseController {
	public function viewAction(Request $request) {
		$scheduleID = $request->attributes->get('scheduleid');
		$codec      = $this->app['obscurity-codec'];
		$id         = $codec->decode($scheduleID, 'schedule');

		if ($id === null) {
			throw new NotFoundException('Schedule '.$scheduleID.' could not be found.');
		}

		$repo     = $this->getRepository('Schedule');
		$schedule = $repo->findOneById($id);

		if (!$schedule || !$schedule->isPublic()) {
			throw new NotFoundException('Schedule '.$scheduleID.' could not be found.');
		}

		// check if hidden columns can be shown
		$hiddenSecret         = $schedule->getHiddenSecret();
		$includeHiddenColumns = $hiddenSecret === null;

		if (!$includeHiddenColumns) {
			$includeHiddenColumns = $request->query->get('hiddenkey') === $hiddenSecret;
		}

		return $this->respondWithItem($schedule, new ScheduleTransformer($this->app, $includeHiddenColumns));
	}

	public function tickerAction(Request $request) {
		$scheduleID = $request->attributes->get('scheduleid');
		$codec      = $this->app['obscurity-codec'];
		$id         = $codec->decode($scheduleID, 'schedule');

		if ($id === null) {
			throw new NotFoundException('Schedule '.$scheduleID.' could not be found.');
		}

		$repo     = $this->getRepository('Schedule');
		$schedule = $repo->findOneById($id);

		if (!$schedule || !$schedule->isPublic()) {
			throw new NotFoundException('Schedule '.$scheduleID.' could not be found.');
		}

		// determine the currently active item
		$now    = new \DateTime('now');
		$prev   = null;
		$active = null;
		$next   = null;

		foreach ($schedule->getScheduledItems() as $item) {
			$scheduled = $item->getScheduled();

			if ($scheduled <= $now) {
				$prev   = $active;
				$active = $item;

				// getting $next is more involved because we cannot access scheduled items by index
			}
			elseif ($next === null) {
				$next = $item;
			}
		}

		// check if the schedule is over already
		if ($active && $active->getScheduledEnd() <= $now) {
			$prev   = $active;
			$active = null;
		}

		// check if hidden columns can be shown
		$hiddenSecret         = $schedule->getHiddenSecret();
		$includeHiddenColumns = $hiddenSecret === null;

		if (!$includeHiddenColumns) {
			$includeHiddenColumns = $request->query->get('hiddenkey') === $hiddenSecret;
		}

		$toTransform = [
			'schedule' => $schedule,
			'prev'     => $prev,
			'active'   => $active,
			'next'     => $next,
		];

		return $this->respondWithItem($toTransform, new ScheduleTickerTransformer($this->app, $includeHiddenColumns));
	}
}
