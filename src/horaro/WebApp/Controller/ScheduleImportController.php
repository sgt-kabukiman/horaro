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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class ScheduleImportController extends BaseController {
	public function formAction(Request $request) {
		$schedule = $this->getRequestedSchedule($request);

		return $this->renderForm($schedule);
	}

	public function importAction(Request $request) {
		$schedule  = $this->getRequestedSchedule($request);
		$validator = $this->app['validator.schedule.import'];
		$result    = $validator->validate($request, $schedule);

		if ($result['_errors']) {
			return $this->renderForm($schedule, $result);
		}

		$filetype       = $result['type']['filtered'];
		$filepath       = $result['upload']['filtered']->getPathname();
		$importer       = $this->app['schedule-importer-'.$filetype];
		$ignoreErrors   = !!$request->request->get('ignore');
		$updateMetadata = !!$request->request->get('metadata');

		try {
			$log = $importer->import($filepath, $schedule, $ignoreErrors, $updateMetadata);
		}
		catch (\Exception $e) {
			$log = $e;
		}

		$hasErrors = false;

		foreach ($log as $row) {
			if ($row[0] === 'error') {
				$hasErrors = true;
				break;
			}
		}

		// respond

		return $this->render('schedule/import-result.twig', [
			'schedule' => $schedule,
			'log'      => $log,
			'errors'   => $hasErrors,
			'stopped'  => $hasErrors && !$ignoreErrors,
			'failed'   => $log instanceof \Exception,
			'upload'   => $result['upload']['filtered']
		]);
	}

	protected function renderForm(Schedule $schedule, array $result = null) {
		return $this->render('schedule/import.twig', [
			'schedule' => $schedule,
			'result'   => $result,
			'max_size' => floor(UploadedFile::getMaxFilesize() / 1024)
		]);
	}
}
