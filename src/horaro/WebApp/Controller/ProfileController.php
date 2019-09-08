<?php
/*
 * Copyright (c) 2019, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Controller;

use horaro\Library\Entity\User;
use horaro\WebApp\Validator\ProfileValidator;
use Symfony\Component\HttpFoundation\Request;

class ProfileController extends BaseController {
	public function editAction(Request $request) {
		$user = $this->getCurrentUser();

		return $this->renderForm($user, null);
	}

	public function updateAction(Request $request) {
		$user      = $this->getCurrentUser();
		$validator = $this->app['validator.profile'];
		$result    = $validator->validate([
			'display_name' => $request->request->get('display_name'),
			'language'     => $request->request->get('language'),
			'gravatar'     => $request->request->get('gravatar')
		]);

		if ($result['_errors']) {
			return $this->renderForm($user, $result);
		}

		// update profile

		$user->setDisplayName($result['display_name']['filtered']);
		$user->setLanguage($result['language']['filtered']);
		$user->setGravatarHash($result['gravatar']['filtered']);

		$this->getEntityManager()->flush();

		// done

		$this->addSuccessMsg('Your profile has been updated.');

		return $this->redirect('/-/profile');
	}

	public function updatePasswordAction(Request $request) {
		$user      = $this->getCurrentUser();
		$validator = $this->app['validator.profile'];
		$result    = $validator->validatePasswordChange([
			'current'   => $request->request->get('current'),
			'password'  => $request->request->get('password'),
			'password2' => $request->request->get('password2')
		], $user);

		if ($result['_errors']) {
			return $this->renderForm($user, $result);
		}

		// update profile

		$user->setPassword($this->app['encoder']->encode($result['password']['filtered']));
		$this->getEntityManager()->flush();
		$this->createFreshSession($user, 'Your password has been changed.');

		return $this->redirect('/-/profile');
	}

	public function oauthAction(Request $request) {
		$user = $this->getCurrentUser();

		if ($user->getTwitchOAuth() === null || $user->getPassword() === null) {
			return $this->redirect('/-/profile');
		}

		return $this->renderOAuthForm($user, null);
	}

	public function unconnectAction(Request $request) {
		$user = $this->getCurrentUser();

		if ($user->getTwitchOAuth() === null) {
			$this->addErrorMsg('Your account is not linked with any Twitch account.');
			return $this->redirect('/-/profile/oauth');
		}

		if ($user->getPassword() === null) {
			$this->addErrorMsg('You cannot remove the only access to your account.');
			return $this->redirect('/-/profile/oauth');
		}

		// update profile

		$user->setTwitchOAuth(null);
		$this->getEntityManager()->flush();
		$this->createFreshSession($user, 'Your account is no longer connected to any Twitch account.');

		return $this->redirect('/-/profile');
	}

	public function removePasswordAction(Request $request) {
		$user = $this->getCurrentUser();

		if ($user->getPassword() === null) {
			$this->addErrorMsg('You already have no password.');
			return $this->redirect('/-/profile');
		}

		if ($user->getTwitchOAuth() === null) {
			$this->addErrorMsg('You can only remove your password if your account is linked to Twitch.');
			return $this->redirect('/-/profile');
		}

		$validator = $this->app['validator.profile'];
		$result    = $validator->validatePasswordChange([
			'current'   => $request->request->get('current'),
			'password'  => 'some valid dummy password',
			'password2' => 'some valid dummy password'
		], $user);

		if ($result['_errors']) {
			return $this->renderOAuthForm($user, $result);
		}

		// update profile

		$user->setPassword(null);
		$this->getEntityManager()->flush();
		$this->createFreshSession($user, 'Your password has been removed. Login via Twitch from now on.');

		return $this->redirect('/-/profile');
	}

	protected function renderForm(User $user, array $result = null) {
		return $this->render('profile/form.twig', [
			'result'    => $result,
			'user'      => $user,
			'languages' => $this->getLanguages()
		]);
	}

	protected function renderOAuthForm(User $user, array $result = null) {
		return $this->render('profile/oauth.twig', [
			'result' => $result,
			'user'   => $user
		]);
	}

	protected function createFreshSession(User $user, $successMsg) {
		$session = $this->app['session'];

		$session->migrate();
		$session->set('horaro.user', $user->getId());
		$session->set('horaro.pwdhash', sha1($user->getPassword()));

		$this->app['csrf']->initSession($session);

		// done

		$this->addSuccessMsg($successMsg);
	}
}
