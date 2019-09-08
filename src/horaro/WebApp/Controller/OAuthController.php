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
use horaro\WebApp\OAuth2\TwitchProvider;
use League\OAuth2\Client\Provider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OAuthController extends BaseController {
	public function startAction(Request $request) {
		$providerName = trim($request->query->get('provider'));
		$provider     = $this->getProvider($providerName);

		if (!$provider) {
			return $this->redirect('/');
		}

		$authUrl = $provider->getAuthorizationUrl();
		$session = $request->getSession();

		$user = $this->getCurrentUser();

		if ($user) {
			// do not allow to re-link
			if ($user->getTwitchOAuth() !== null) {
				return $this->redirect('/-/profile');
			}
		}
		else {
			$session->start();
		}

		$session->set('oauth2provider', $providerName);
		$session->set('oauth2state', $provider->getState());

		return $this->redirect($authUrl);
	}

	public function callbackAction(Request $request) {
		$currentUser  = $this->getCurrentUser();
		$session      = $request->getSession();
		$providerName = $session->get('oauth2provider');
		$oldState     = $session->get('oauth2state');
		$state        = $request->query->get('state');
		$code         = $request->query->get('code');
		$provider     = $this->getProvider($providerName);

		// this data is now invalid, so remove it just in case
		$session->remove('oauth2provider');
		$session->remove('oauth2state');

		if (!$code || !$state || $state !== $oldState || !$provider) {
			return $this->redirect($currentUser ? '/-/profile' : '/');
		}

		try {
			// try to get an access token
			$token       = $provider->getAccessToken('authorization_code', ['code' => $code]);
			$userDetails = $provider->getResourceOwner($token);
		}
		catch (Exception $e) {
			$message = 'Something unexpected happened when completing your login. Please try again later.';

			if ($currentUser) {
				$this->addErrorMsg($message);
				return $this->redirect('/-/profile');
			}

			$html = $this->render('index/login.twig', ['error_message' => $message, 'result' => null]);

			return $this->setCachingHeader(new Response($html), 'other');
		}

		// find the user
		$userRepo = $this->getRepository('User');
		$identity = $userDetails->getId();
		$existing = $userRepo->findOneBy(['twitch_oauth' => $identity]);

		if (!$existing) {
			$config = $this->app['config'];
			$em     = $this->getEntityManager();

			// nobody is logged in, so let's create a new account
			if (!$currentUser) {
				$user = new User();
				$user->setLogin('oauth:twitch:'.$userDetails->getUsername());
				$user->setPassword(null);
				$user->setDisplayName($userDetails->getDisplayName());
				$user->setRole($config['default_role']);
				$user->setMaxEvents($config['max_events']);
				$user->setLanguage('en_us');

				$em->persist($user);
			}
			else {
				$user = $currentUser;
			}

			// link the current account to the just authenticated Twitch account
			$user->setTwitchOAuth($identity);
			$em->flush();

			// we're done for logged-in users
			if ($currentUser) {
				$this->addSuccessMsg('You have successfully linked your accounts.');
				return $this->redirect('/-/profile');
			}
		}
		else {
			if ($currentUser) {
				$this->addSuccessMsg('This account is already used by another Horaro account.');
				return $this->redirect('/-/profile');
			}

			if ($existing->getRole() === 'ROLE_GHOST') {
				$html = $this->render('index/login.twig', ['error_message' => 'Your account has ben disabled.', 'result' => null]);
				return $this->setCachingHeader(new Response($html), 'other');
			}

			$user = $existing;
		}

		$session->migrate(); // create new session ID (prevents session fixation)
		$session->set('horaro.user', $user->getId());
		$session->set('horaro.pwdhash', sha1($user->getPassword()));

		if (!$existing) {
			$this->addSuccessMsg('Welcome to Horaro, your account has been successfully created.');
		}

		$this->app['csrf']->initSession($session);

		return $this->redirect('/');
	}

	protected function getProvider($name) {
		$config = $this->app['config'];

		if (!isset($config['oauth'][$name])) {
			return null;
		}

		$params  = $config['oauth'][$name];
		$request = $this->app['request'];

		// auto-determine the callback URL
		$params['redirectUri'] = $request->getSchemeAndHttpHost().'/-/oauth/callback';

		switch ($name) {
			case 'twitch': $provider = new TwitchProvider($params); break;
			default:       throw new \Exception('Invalid provider "'.$name.'" configured.');
		}

		return $provider;
	}
}
