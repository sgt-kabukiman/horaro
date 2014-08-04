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
use horaro\WebApp\Validator\EventValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EventController extends BaseController {
	public function detailAction(Request $request) {
		$event = $this->getRequestedEvent($request);

		return $this->render('event/detail.twig', ['event' => $event]);
	}

	public function newAction(Request $request) {
		return $this->render('event/form.twig', ['event' => null, 'result' => null]);
	}

	public function createAction(Request $request) {
		$validator = new EventValidator($this->getRepository('Event'));
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
		;

		$em = $this->getEntityManager();
		$em->persist($event);
		$em->flush();

		// done

		return $this->redirect('/-/events/'.$event->getId());
	}

	public function editAction(Request $request) {
		$event = $this->getRequestedEvent($request);

		return $this->render('event/form.twig', ['event' => $event, 'result' => null]);
	}

	public function updateAction(Request $request) {
		$event     = $this->getRequestedEvent($request);
		$validator = new EventValidator($this->getRepository('Event'));
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

		return $this->redirect('/-/events/'.$event->getId());
	}

	public function confirmationAction(Request $request) {
		$event = $this->getRequestedEvent($request);

		return $this->render('event/confirmation.twig', ['event' => $event]);
	}

	public function deleteAction(Request $request) {
		$event = $this->getRequestedEvent($request);
		$em    = $this->getEntityManager();

		$em->remove($event);
		$em->flush();

		return $this->redirect('/-/home');
	}

	protected function getRequestedEvent(Request $request) {
		$hash = $request->attributes->get('id');
		$id   = $this->decodeID($hash, 'event');

		if ($id === null) {
			throw new Ex\NotFoundException('The event could not be found.');
		}

		$repo  = $this->getRepository('Event');
		$event = $repo->findOneById($id);

		if (!$event) {
			throw new Ex\NotFoundException('Event '.$hash.' could not be found.');
		}

		$user  = $this->getCurrentUser();
		$owner = $event->getUser();

		if (!$owner || $user->getId() !== $owner->getId()) {
			throw new Ex\NotFoundException('Event '.$hash.' could not be found.');
		}

		return $event;
	}
}
