<?php
/*
 * Copyright (c) 2015, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\OAuth2;

use League\OAuth2\Client\Entity\User;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;

class TwitchProvider extends AbstractProvider {
	public $authorizationHeader = 'OAuth';
	public $scopeSeparator      = ' ';
	public $scopes              = [];

	const ROOT_URL = 'https://api.twitch.tv/kraken';

	public function urlAuthorize() {
		return self::ROOT_URL.'/oauth2/authorize';
	}

	public function urlAccessToken() {
		return self::ROOT_URL.'/oauth2/token';
	}

	public function urlUserDetails(AccessToken $token) {
		// This only works if we got user_read; for the most basic tokens, we need
		// to fetch the username manually and just get the public user record.
		// See fetchUserDetails().
		return self::ROOT_URL.'/user';
	}

	protected function fetchUserDetails(AccessToken $token) {
		if (!in_array('user_read', $this->scopes)) {
			// fetch root to get the link to the authenticated user's profile
			$headers  = $this->getHeaders($token);
			$response = json_decode($this->fetchProviderData(self::ROOT_URL, $headers));
			$user     = $response->token->user_name;
			$url      = self::ROOT_URL.'/users/'.urlencode($user);
		}
		else {
			$url = $this->urlUserDetails($token);
		}

		$headers = $this->getHeaders($token);

		return $this->fetchProviderData($url, $headers);
	}

	public function userDetails($response, AccessToken $token) {
		$user = new User();
		$user->exchangeArray([
			'uid'         => $this->userUid($response, $token),
			'name'        => $this->userScreenName($response, $token),
			'email'       => $this->userEmail($response, $token),
			'nickname'    => $response->name,
			'imageUrl'    => $response->logo,
			'description' => isset($response->bio) && $response->bio ? $response->bio : null,
		]);

		return $user;
	}

	public function userUid($response, AccessToken $token) {
		return $response->_id;
	}

	public function userEmail($response, AccessToken $token) {
		return isset($response->email) ? $response->email : null;
	}

	public function userScreenName($response, AccessToken $token) {
		return $response->display_name;
	}
}
