<?php
/*
 * Copyright (c) 2014, Sgt. Kabukiman, https://bitbucket.org/sgt-kabukiman/
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Controller\Admin\Utils;

use horaro\Library\Configuration;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;

class ToolsController extends BaseController {
	public function formAction() {
		return $this->render('admin/utils/tools.twig');
	}

	public function cleartwigcacheAction(Request $request) {
		$cacheDir = $this->app['twig']->getCache();

		if (!$cacheDir || !is_dir($cacheDir)) {
			$this->addErrorMsg('Could not find Twig cache.');

			return $this->redirect('/-/admin/utils/tools');
		}

		$files  = [];
		$finder = new Finder();
		$finder->in($cacheDir);

		foreach ($finder as $file) {
			$files[] = (string) $file;
		}

		foreach (array_reverse($files) as $file) {
			if (is_file($file)) {
				unlink($file);
			}
			else {
				rmdir($file);
			}
		}

		// done

		$this->addSuccessMsg('The Twig cache has been cleared.');

		return $this->redirect('/-/admin/utils/tools');
	}

	public function fixpositionsAction(Request $request) {
		$scheduleRepo = $this->getRepository('Schedule');
		$schedules    = $scheduleRepo->findAll();

		$em   = $this->getEntityManager();
		$conn = $em->getConnection();

		foreach ($schedules as $schedule) {
			$scheduleID = $schedule->getId();

			$conn->executeQuery('SET @pos = 0;');
			$conn->executeUpdate('UPDATE schedule_items SET position := (@pos := @pos + 1) WHERE schedule_id = ? ORDER BY position', [$scheduleID]);

			$conn->executeQuery('SET @pos = 0;');
			$conn->executeUpdate('UPDATE schedule_columns SET position := (@pos := @pos + 1) WHERE schedule_id = ? ORDER BY position', [$scheduleID]);
		}

		// done

		$this->addSuccessMsg('The items have been re-numbered.');

		return $this->redirect('/-/admin/utils/tools');
	}
}
