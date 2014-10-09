<?php
/*
 * Copyright (c) 2014, Sgt. Kabukiman, https://bitbucket.org/sgt-kabukiman/
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Controller\Admin;

use horaro\Library\Entity\Schedule;
use horaro\WebApp\Controller\BaseController;
use horaro\WebApp\Pager;
use horaro\WebApp\Validator\Admin\UserValidator;
use horaro\WebApp\Exception\ForbiddenException;
use Symfony\Component\HttpFoundation\Request;

class ScheduleController extends BaseController {
	public function indexAction(Request $request) {
		$page = (int) $request->query->get('page', 0);
		$size = 20;

		if ($page < 0) {
			$page = 0;
		}

		$itemRepo     = $this->getRepository('ScheduleItem');
		$scheduleRepo = $this->getRepository('Schedule');
		$schedules    = $scheduleRepo->findBy([], ['name' => 'ASC'], $size, $page*$size);
		$total        = $scheduleRepo->count();

		foreach ($schedules as $schedule) {
			$schedule->itemCount = $itemRepo->count($schedule);
		}

		return $this->render('admin/schedules/index.twig', [
			'schedules' => $schedules,
			'pager'  => new Pager($page, $total, $size)
		]);
	}

	public function editAction(Request $request) {
		$schedule = $this->getRequestedSchedule($request);

		if (!$this->canEdit($schedule)) {
			return $this->render('admin/schedules/view.twig', ['schedule' => $schedule]);
		}

		return $this->renderForm($schedule);
	}

	public function updateAction(Request $request) {
		$this->checkCsrfToken($request);

		$schedule = $this->getRequestedSchedule($request);

		if (!$this->canEdit($schedule)) {
			throw new ForbiddenException('You are not allowed to edit this schedule.');
		}

		$validator = $this->app['validator.admin.schedule'];
		$result    = $validator->validate([
			'name'       => $request->request->get('name'),
			'slug'       => $request->request->get('slug'),
			'twitch'     => '',
			'timezone'   => $request->request->get('timezone'),
			'start_date' => $request->request->get('start_date'),
			'start_time' => $request->request->get('start_time'),
			'theme'      => $request->request->get('theme'),
			'max_items'  => $request->request->get('max_items')
		], $schedule->getEvent(), $schedule);

		if ($result['_errors']) {
			return $this->renderForm($schedule, $result);
		}

		// update schedule

		$schedule
			->setName($result['name']['filtered'])
			->setSlug($result['slug']['filtered'])
			->setTimezone($result['timezone']['filtered'])
			->setUpdatedAt(new \DateTime('now UTC'))
			->setStart($result['start']['filtered'])
			->setTheme($result['theme']['filtered'])
			->setMaxItems($result['max_items']['filtered'])
		;

		$em = $this->getEntityManager();
		$em->persist($schedule);
		$em->flush();

		// done

		$this->addSuccessMsg('Schedule '.$schedule->getName().' has been updated.');

		return $this->redirect('/-/admin/schedules');
	}

	public function confirmationAction(Request $request) {
		$schedule = $this->getRequestedSchedule($request);

		if (!$this->canEdit($schedule)) {
			throw new ForbiddenException('You are not allowed to delete this schedule.');
		}

		return $this->render('admin/schedules/confirmation.twig', ['schedule' => $schedule]);
	}

	public function deleteAction(Request $request) {
		$this->checkCsrfToken($request);

		$schedule = $this->getRequestedSchedule($request);

		if (!$this->canEdit($schedule)) {
			throw new ForbiddenException('You are not allowed to delete this schedule.');
		}

		$em = $this->getEntityManager();
		$em->remove($schedule);
		$em->flush();

		$this->addSuccessMsg('The requested schedule has been deleted.');

		return $this->redirect('/-/admin/schedules');
	}

	protected function renderForm(Schedule $schedule, array $result = null) {
		$itemRepo  = $this->getRepository('ScheduleItem');
		$config    = $this->app['config'];
		$timezones = \DateTimeZone::listIdentifiers();

		$schedule->itemCount = $itemRepo->count($schedule);

		return $this->render('admin/schedules/form.twig', [
			'result'    => $result,
			'timezones' => $timezones,
			'themes'    => $config['themes'],
			'schedule'  => $schedule
		]);
	}

	protected function canEdit(Schedule $schedule) {
		return $this->app['rolemanager']->canEditSchedule($this->getCurrentUser(), $schedule);
	}
}
