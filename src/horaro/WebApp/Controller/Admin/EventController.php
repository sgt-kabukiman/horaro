<?php
/*
 * Copyright (c) 2015, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Controller\Admin;

use horaro\Library\Entity\Event;
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

		$query     = $request->query->get('q', '');
		$eventRepo = $this->getRepository('Event');
		$events    = $eventRepo->findFiltered($query, $size, $page*$size);
		$total     = $eventRepo->countFiltered($query);

		return $this->render('admin/events/index.twig', [
			'events' => $events,
			'pager'  => new Pager($page, $total, $size),
			'query'  => $query
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
		$event = $this->getRequestedEvent($request);

		if (!$this->canEdit($event)) {
			throw new ForbiddenException('You are not allowed to edit this event.');
		}

		$validator = $this->app['validator.admin.event'];
		$result    = $validator->validate([
			'name'          => $request->request->get('name'),
			'slug'          => $request->request->get('slug'),
			'website'       => $request->request->get('website'),
			'twitter'       => $request->request->get('twitter'),
			'twitch'        => $request->request->get('twitch'),
			'theme'         => $request->request->get('theme'),
			'secret'        => $request->request->get('secret'),
			'max_schedules' => $request->request->get('max_schedules')
		], $event);

		if ($result['_errors']) {
			return $this->renderForm($event, $result);
		}

		// update event

		$event
			->setName($result['name']['filtered'])
			->setSlug($result['slug']['filtered'])
			->setWebsite($result['website']['filtered'])
			->setTwitter($result['twitter']['filtered'])
			->setTwitch($result['twitch']['filtered'])
			->setTheme($result['theme']['filtered'])
			->setSecret($result['secret']['filtered'])
			->setMaxSchedules($result['max_schedules']['filtered'])
		;

		// update list of featured events

		$config   = $this->app['runtime-config'];
		$eventID  = $event->getID();
		$featured = $config->get('featured_events', []);

		if ($request->request->get('featured')) {
			if (!in_array($eventID, $featured)) {
				$featured[] = $eventID;
				sort($featured);

				$config->set('featured_events', $featured);
			}
		}
		elseif (($pos = array_search($eventID, $featured)) !== false) {
			unset($featured[$pos]);
			$featured = array_values($featured);

			$config->set('featured_events', $featured);
		}

		// done

		$this->getEntityManager()->flush();
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
		$config   = $this->app['runtime-config'];
		$featured = $config->get('featured_events', []);

		return $this->render('admin/events/form.twig', [
			'result'   => $result,
			'event'    => $event,
			'themes'   => $this->app['config']['themes'],
			'featured' => in_array($event->getID(), $featured)
		]);
	}

	protected function canEdit(Event $event) {
		return $this->app['rolemanager']->canEditEvent($this->getCurrentUser(), $event);
	}
}
