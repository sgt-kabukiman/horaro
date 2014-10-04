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
use horaro\WebApp\Exception as Ex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EventController extends BaseController {
	public function detailAction(Request $request) {
		$event = $this->getRequestedEvent($request);

		return $this->render('event/detail.twig', [
			'event'  => $event,
			'isFull' => $this->exceedsMaxSchedules($event)
		]);
	}

	public function newAction(Request $request) {
		if ($this->exceedsMaxEvents($this->getCurrentUser())) {
			return $this->redirect('/-/home');
		}

		return $this->render('event/form.twig', ['event' => null, 'result' => null]);
	}

	public function createAction(Request $request) {
		// do not leak information, check CSRF token before checking for max events
		$this->checkCsrfToken($request);

		if ($this->exceedsMaxEvents($this->getCurrentUser())) {
			return $this->redirect('/-/home');
		}

		$validator = $this->app['validator.event'];
		$result    = $validator->validate([
			'name'    => $request->request->get('name'),
			'slug'    => $request->request->get('slug'),
			'website' => $request->request->get('website'),
			'twitch'  => $request->request->get('twitch'),
			'twitter' => $request->request->get('twitter')
		]);

		if ($result['_errors']) {
			return $this->render('event/form.twig', ['event' => null, 'result' => $result]);
		}

		// create event

		$user  = $this->getCurrentUser();
		$event = new Event();

		$event
			->setOwner($user)
			->setName($result['name']['filtered'])
			->setSlug($result['slug']['filtered'])
			->setWebsite($result['website']['filtered'])
			->setTwitch($result['twitch']['filtered'])
			->setTwitter($result['twitter']['filtered'])
			->setMaxSchedules($this->app['config']['max_schedules'])
		;

		$em = $this->getEntityManager();
		$em->persist($event);
		$em->flush();

		// done

		$this->addSuccessMsg('Your new event has been created.');

		return $this->redirect('/-/events/'.$event->getId());
	}

	public function editAction(Request $request) {
		$event = $this->getRequestedEvent($request);

		return $this->render('event/form.twig', ['event' => $event, 'result' => null]);
	}

	public function updateAction(Request $request) {
		$this->checkCsrfToken($request);

		$event     = $this->getRequestedEvent($request);
		$validator = $this->app['validator.event'];
		$result    = $validator->validate([
			'name'    => $request->request->get('name'),
			'slug'    => $request->request->get('slug'),
			'website' => $request->request->get('website'),
			'twitch'  => $request->request->get('twitch'),
			'twitter' => $request->request->get('twitter')
		], $event);

		if ($result['_errors']) {
			return $this->render('event/form.twig', ['event' => $event, 'result' => $result]);
		}

		// update

		$event
			->setName($result['name']['filtered'])
			->setSlug($result['slug']['filtered'])
			->setWebsite($result['website']['filtered'])
			->setTwitch($result['twitch']['filtered'])
			->setTwitter($result['twitter']['filtered'])
		;

		$em = $this->getEntityManager();
		$em->persist($event);
		$em->flush();

		// done

		$this->addSuccessMsg('Your event has been updated.');

		return $this->redirect('/-/events/'.$event->getId());
	}

	public function confirmationAction(Request $request) {
		$event = $this->getRequestedEvent($request);

		return $this->render('event/confirmation.twig', ['event' => $event]);
	}

	public function deleteAction(Request $request) {
		$this->checkCsrfToken($request);

		$event = $this->getRequestedEvent($request);
		$em    = $this->getEntityManager();

		$em->remove($event);
		$em->flush();

		$this->addSuccessMsg('The requested event has been deleted.');

		return $this->redirect('/-/home');
	}
}
