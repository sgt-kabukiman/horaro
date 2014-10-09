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

use horaro\Library\Entity\User;
use horaro\WebApp\Controller\BaseController;
use horaro\WebApp\Pager;
use horaro\WebApp\Validator\Admin\UserValidator;
use horaro\WebApp\Exception\ForbiddenException;
use Symfony\Component\HttpFoundation\Request;

class UserController extends BaseController {
	public function indexAction(Request $request) {
		$page = (int) $request->query->get('page', 0);
		$size = 20;

		if ($page < 0) {
			$page = 0;
		}

		$eventRepo = $this->getRepository('Event');
		$userRepo  = $this->getRepository('User');
		$users     = $userRepo->findBy([], ['login' => 'ASC'], $size, $page*$size);
		$total     = $userRepo->count();

		foreach ($users as $user) {
			$user->eventCount = $eventRepo->count($user);
		}

		return $this->render('admin/users/index.twig', [
			'users' => $users,
			'pager' => new Pager($page, $total, $size)
		]);
	}

	public function editAction(Request $request) {
		$user = $this->getRequestedUser($request);

		if (!$this->canEdit($user)) {
			return $this->render('admin/users/view.twig', ['user' => $user, 'languages' => $this->getLanguages()]);
		}

		return $this->renderForm($user);
	}

	public function updateAction(Request $request) {
		$this->checkCsrfToken($request);

		$user = $this->getRequestedUser($request);

		if (!$this->canEdit($user)) {
			throw new ForbiddenException('You are not allowed to edit this user.');
		}

		$self      = $this->getCurrentUser();
		$validator = $this->app['validator.admin.user'];
		$result    = $validator->validate([
			'login'        => $request->request->get('login'),
			'display_name' => $request->request->get('display_name'),
			'language'     => $request->request->get('language'),
			'gravatar'     => $request->request->get('gravatar'),
			'max_events'   => $request->request->get('max_events'),
			'role'         => $request->request->get('role')
		], $user, $self);

		if ($result['_errors']) {
			return $this->renderForm($user, $result);
		}

		// update user

		$user->setLogin($result['login']['filtered']);
		$user->setDisplayName($result['display_name']['filtered']);
		$user->setLanguage($result['language']['filtered']);
		$user->setGravatarHash($result['gravatar']['filtered']);
		$user->setMaxEvents($result['max_events']['filtered']);
		$user->setRole($result['role']['filtered']);

		$em = $this->getEntityManager();
		$em->persist($user);
		$em->flush();

		// done

		$this->addSuccessMsg('User '.$user->getLogin().' has been updated.');

		return $this->redirect('/-/admin/users');
	}

	public function updatePasswordAction(Request $request) {
		$this->checkCsrfToken($request);

		$user = $this->getRequestedUser($request);

		if (!$this->canEdit($user)) {
			throw new ForbiddenException('You are not allowed to edit this user.');
		}

		$validator = $this->app['validator.admin.user'];
		$result    = $validator->validatePasswordChange([
			'password'  => $request->request->get('password'),
			'password2' => $request->request->get('password2')
		], $user);

		if ($result['_errors']) {
			return $this->renderForm($user, $result);
		}

		// update user

		$user->setPassword($this->app['encoder']->encode($result['password']['filtered']));

		$em = $this->getEntityManager();
		$em->persist($user);
		$em->flush();

		// done

		$this->addSuccessMsg('The password for '.$user->getLogin().' has been changed.');

		return $this->redirect('/-/admin/users');
	}

	protected function renderForm(User $user, array $result = null) {
		return $this->render('admin/users/form.twig', [
			'result'    => $result,
			'user'      => $user,
			'languages' => $this->getLanguages()
		]);
	}

	protected function canEdit(User $user) {
		return $this->app['rolemanager']->canEditUser($this->getCurrentUser(), $user);
	}
}
