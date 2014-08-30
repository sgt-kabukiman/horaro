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

use horaro\WebApp\Validator\ProfileValidator;
use Symfony\Component\HttpFoundation\Request;

class ProfileController extends BaseController {
	public function editAction(Request $request) {
		$user      = $this->getCurrentUser();
		$languages = $this->getLanguages();

		return $this->render('profile/form.twig', [
			'user'      => $user,
			'languages' => $languages
		]);
	}

	public function updateAction(Request $request) {
		$user      = $this->getCurrentUser();
		$languages = $this->getLanguages();
		$validator = new ProfileValidator(array_keys($languages), $this->getDefaultLanguage());
		$result    = $validator->validate([
			'display_name' => $request->request->get('display_name'),
			'language'     => $request->request->get('language'),
			'gravatar'     => $request->request->get('gravatar')
		]);

		if ($result['_errors']) {
			return $this->render('profile/form.twig', [
				'result'    => $result,
				'user'      => $user,
				'languages' => $languages
			]);
		}

		// update profile

		$user->setDisplayName($result['display_name']['filtered']);
		$user->setLanguage($result['language']['filtered']);
		$user->setGravatarHash($result['gravatar']['filtered']);

		$em = $this->getEntityManager();
		$em->persist($user);
		$em->flush();

		// done

		return $this->redirect('/-/profile');
	}
}
