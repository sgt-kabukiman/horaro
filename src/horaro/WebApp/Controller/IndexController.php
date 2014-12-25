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

use horaro\Library\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexController extends BaseController {
	public function welcomeAction(Request $request) {
		$user = $this->getCurrentUser();

		if ($user) {
			return $this->redirect('/-/home');
		}

		// dummy: set locale based on Accept-Language header
		// this needs to be done in a general pre-controller filter
//		$this->app['locale'] = strtolower($request->getPreferredLanguage(['de_DE', 'en_US']));

		// find upcoming event schedules (blatenly ignoring that the starting times
		// in the database are not in UTC).

		$scheduleRepo = $this->getRepository('Schedule');
		$schedules    = $scheduleRepo->findUpcoming(3132, 10);
		$upcoming     = [];

		// group by event
		foreach ($schedules as $schedule) {
			$event   = $schedule->getEvent();
			$eventID = $event->getID();

			$upcoming[$eventID]['event']       = $event;
			$upcoming[$eventID]['schedules'][] = $schedule;
		}

		// find featured, old events
		$ids       = $this->app['runtime-config']->get('featured_events', []);
		$eventRepo = $this->getRepository('Event');
		$featured  = $eventRepo->findById($ids);

		// remove featured events that are already included in the upcoming list
		foreach ($featured as $idx => $event) {
			$eventID = $event->getID();

			if (isset($upcoming[$eventID])) {
				unset($featured[$idx]);
			}
		}

		return $this->render('index/welcome.twig', [
			'noRegister' => $this->exceedsMaxUsers(),
			'upcoming'   => array_slice($upcoming, 0, 5),
			'featured'   => array_slice($featured, 0, 5)
		]);
	}

	public function registerFormAction(Request $request) {
		if ($this->exceedsMaxUsers()) {
			return $this->redirect('/');
		}

		return $this->render('index/register.twig', ['result' => null]);
	}

	public function registerAction(Request $request) {
		if ($this->exceedsMaxUsers()) {
			return $this->redirect('/');
		}

		$validator = $this->app['validator.createaccount'];
		$result    = $validator->validate([
			'login'        => $request->request->get('login'),
			'password'     => $request->request->get('password'),
			'password2'    => $request->request->get('password2'),
			'display_name' => $request->request->get('display_name')
		]);

		if ($result['_errors']) {
			return $this->render('index/register.twig', ['result' => $result]);
		}

		// create new user

		$config = $this->app['config'];

		$user = new User();
		$user->setLogin($result['login']['filtered']);
		$user->setPassword($this->app['encoder']->encode($result['password']['filtered']));
		$user->setDisplayName($result['display_name']['filtered']);
		$user->setRole($config['default_role']);
		$user->setMaxEvents($config['max_events']);
		$user->setLanguage('en_us');

		$em = $this->getEntityManager();
		$em->persist($user);
		$em->flush();

		// open session

		$session = $this->app['session'];
		$session->start();
		$session->migrate(); // create new session ID (prevents session fixation)
		$session->set('horaro.user', $user->getId());

		$this->app['csrf']->initSession($session);
		$this->addSuccessMsg('Welcome to Horaro, your account has been successfully created.');

		return $this->redirect('/-/home');
	}

	public function loginFormAction(Request $request) {
		return $this->render('index/login.twig', ['result' => null]);
	}

	public function loginAction(Request $request) {
		$validator = $this->app['validator.login'];
		$result    = $validator->validate([
			'login'    => $request->request->get('login'),
			'password' => $request->request->get('password')
		]);

		if ($result['_errors']) {
			return $this->render('index/login.twig', ['result' => $result]);
		}

		// open session

		$user    = $result['_user'];
		$session = $this->app['session'];

		$session->start();
		$session->migrate(); // create new session ID (prevents session fixation)
		$session->set('horaro.user', $user->getId());
		$session->set('horaro.pwdhash', sha1($user->getPassword()));

		$this->app['csrf']->initSession($session);

		return $this->redirect('/-/home');
	}

	public function logoutAction(Request $request) {
		$session = $this->app['session'];
		$session->invalidate();

		return $this->redirect('/');
	}

	public function licensesAction(Request $request) {
		return $this->render('index/licenses.twig');
	}
}
