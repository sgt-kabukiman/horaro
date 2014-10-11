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

		// create a fresh session

		$session = $this->app['session'];

		$session->migrate();
		$session->set('horaro.user', $user->getId());
		$session->set('horaro.pwdhash', sha1($user->getPassword()));

		$this->app['csrf']->initSession($session);

		// done

		$this->addSuccessMsg('Your password has been changed.');

		return $this->redirect('/-/profile');
	}

	protected function renderForm(User $user, array $result = null) {
		return $this->render('profile/form.twig', [
			'result'    => $result,
			'user'      => $user,
			'languages' => $this->getLanguages()
		]);
	}
}
