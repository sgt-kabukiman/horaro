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
		$this->checkCsrfToken($request);

		$user      = $this->getCurrentUser();
		$languages = $this->getLanguages();
		$validator = new ProfileValidator(array_keys($languages), $this->getDefaultLanguage());
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

		$em = $this->getEntityManager();
		$em->persist($user);
		$em->flush();

		// done

		$this->addSuccessMsg('Your profile has been updated.');

		return $this->redirect('/-/profile');
	}

	public function updatePasswordAction(Request $request) {
		$this->checkCsrfToken($request);

		$user      = $this->getCurrentUser();
		$validator = new ProfileValidator([], null);
		$result    = $validator->validatePasswordChange([
			'current'   => $request->request->get('current'),
			'password'  => $request->request->get('password'),
			'password2' => $request->request->get('password2')
		], $user);

		if ($result['_errors']) {
			return $this->renderForm($user, $result);
		}

		// update profile

		$costs = $this->app['config']['bcryptCost'];
		$user->setPassword(password_hash($result['password']['filtered'], PASSWORD_DEFAULT, ['cost' => $costs]));

		$em = $this->getEntityManager();
		$em->persist($user);
		$em->flush();

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
