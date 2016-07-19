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
}
