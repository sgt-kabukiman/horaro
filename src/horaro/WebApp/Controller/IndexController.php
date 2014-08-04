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
use horaro\WebApp\Validator\CreateAccountValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexController extends BaseController {
	public function indexAction(Request $request) {
		// dummy: set locale based on Accept-Language header
		// this needs to be done in a general pre-controller filter
//		$this->app['locale'] = strtolower($request->getPreferredLanguage(['de_DE', 'en_US']));

		return $this->render('index/home.twig');
	}

	public function registerFormAction(Request $request) {
		return $this->render('index/register.twig', ['result' => null]);
	}

	public function registerAction(Request $request) {
		$validator = new CreateAccountValidator($this->getRepository('User'));
		$result    = $validator->validate([
			'login'        => $request->request->get('username'),
			'password'     => $request->request->get('password'),
			'password2'    => $request->request->get('password2'),
			'display_name' => $request->request->get('display_name')
		]);

		if ($result['_errors']) {
			return $this->render('index/register.twig', ['result' => $result]);
		}

		// create new user

		$user = new User();
		$user->setLogin($result['login']['filtered']);
		$user->setPassword(password_hash($result['password']['filtered'], PASSWORD_DEFAULT, ['cost' => 11]));
		$user->setDisplayName($result['display_name']['filtered']);
		$user->setRole('ROLE_USER');

		$em = $this->getEntityManager();
		$em->persist($user);
		$em->flush();

		// open session

		$session = $this->app['session'];
		$session->start();
		$session->migrate(); // create new session ID (prevents session fixation)
		$session->set('horaro.user', $user->getId());
		$session->getFlashBag()->add('message', 'Welcome to Horaro, your account has been successfully created. You can now create your first team or be invited to an already existing one.');

		return $this->redirect('/-/home');
	}

	public function loginFormAction(Request $request) {
		return $this->render('index/login.twig', ['result' => null]);
	}

	public function loginAction(Request $request) {
		$validator = new \horaro\WebApp\Validator\LoginValidator($this->getRepository('User'));
		$result    = $validator->validate([
			'login'    => $request->request->get('username'),
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

		return $this->redirect('/-/home');
	}

	public function logoutAction(Request $request) {
		$session = $this->app['session'];
		$session->invalidate();

		return $this->redirect('/');
	}
}
