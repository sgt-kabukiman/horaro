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

use horaro\Library\Entity\Event;
use horaro\WebApp\Controller\BaseController;
use horaro\WebApp\Pager;
use horaro\WebApp\Validator\Admin\UserValidator;
use horaro\WebApp\Exception\ForbiddenException;
use Symfony\Component\HttpFoundation\Request;

class EventController extends BaseController {
	public function indexAction(Request $request) {
		$page = (int) $request->query->get('page', 0);
		$size = 20;

		if ($page < 0) {
			$page = 0;
		}

		$userRepo = $this->getRepository('Event');
		$events   = $userRepo->findBy([], ['name' => 'ASC'], $size, $page*$size);
		$total    = $this->getEntityManager()
			->createQuery('SELECT COUNT(e.id) FROM horaro\Library\Entity\Event e')
			->getSingleScalarResult();

		return $this->render('admin/events/index.twig', [
			'events' => $events,
			'pager'  => new Pager($page, $total, $size)
		]);
	}

	public function editAction(Request $request) {
		$event = $this->getRequestedEvent($request);

		if (!$this->canEdit($event)) {
			return $this->render('admin/events/view.twig', ['event' => $event]);
		}

		return $this->renderForm($event);
	}

	public function updateAction(Request $request) {
		$this->checkCsrfToken($request);

		$event = $this->getRequestedEvent($request);

		if (!$this->canEdit($event)) {
			throw new ForbiddenException('You are not allowed to edit this event.');
		}

		$validator = $this->app['validator.admin.event'];
		$result    = $validator->validate([
			'name'          => $request->request->get('name'),
			'slug'          => $request->request->get('slug'),
			'website'       => $request->request->get('website'),
			'twitch'        => $request->request->get('twitch'),
			'twitter'       => $request->request->get('twitter'),
			'max_schedules' => $request->request->get('max_schedules')
		], $event);

		if ($result['_errors']) {
			return $this->renderForm($user, $result);
		}

		// update event

		$event
			->setName($result['name']['filtered'])
			->setSlug($result['slug']['filtered'])
			->setWebsite($result['website']['filtered'])
			->setTwitch($result['twitch']['filtered'])
			->setTwitter($result['twitter']['filtered'])
			->setMaxSchedules($result['max_schedules']['filtered'])
		;

		$em = $this->getEntityManager();
		$em->persist($event);
		$em->flush();

		// done

		$this->addSuccessMsg('Event '.$event->getName().' has been updated.');

		return $this->redirect('/-/admin/events');
	}

	public function confirmationAction(Request $request) {
		$event = $this->getRequestedEvent($request);

		if (!$this->canEdit($event)) {
			throw new ForbiddenException('You are not allowed to delete this event.');
		}

		return $this->render('admin/events/confirmation.twig', ['event' => $event]);
	}

	public function deleteAction(Request $request) {
		$this->checkCsrfToken($request);

		$event = $this->getRequestedEvent($request);

		if (!$this->canEdit($event)) {
			throw new ForbiddenException('You are not allowed to delete this event.');
		}

		$em = $this->getEntityManager();
		$em->remove($event);
		$em->flush();

		$this->addSuccessMsg('The requested event has been deleted.');

		return $this->redirect('/-/admin/events');
	}

	protected function renderForm(Event $event, array $result = null) {
		return $this->render('admin/events/form.twig', [
			'result' => $result,
			'event'  => $event
		]);
	}

	protected function canEdit(Event $event) {
		$self = $this->getCurrentUser();

		return $this->app['rolemanager']->canEditEvent($self, $event);
	}
}
