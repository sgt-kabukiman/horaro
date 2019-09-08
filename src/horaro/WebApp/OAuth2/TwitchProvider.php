<?php
/*
 * Copyright (c) 2019, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\OAuth2;

use Depotwarehouse\OAuth2\Client\Twitch\Provider\Twitch as BaseProvider;
use League\OAuth2\Client\Token\AccessToken;

class TwitchProvider extends BaseProvider {
	// override scopes because we don't want any personal data from our users
	public $scopes = [];

	// use the new id.twitch.tv domain, but leave the generic api.twitch.tv
	// as the base apiDomain for this class, because all other requests are
	// still going there
	public function getBaseAuthorizationUrl() {
		return 'https://id.twitch.tv/oauth2/authorize';
	}

	public function getBaseAccessTokenUrl(array $params) {
		return 'https://id.twitch.tv/oauth2/token';
	}

	// use Helix API to fetch user details
	protected function fetchResourceOwnerDetails(AccessToken $token) {
		// use the access token to fetch the user's profile
		$url      = $this->getUrlForEndpoint('/helix/users', $token);
		$request  = $this->getAuthenticatedRequest(self::METHOD_GET, $url, $token);
		$response = (string) $this->getResponse($request)->getBody();
		$response = json_decode($response, true);
		$response = $response['data'][0];

		// adopt Helix naming to the BaseProvider's TwitchUser class
		$response['_id']  = $response['id'];
		$response['logo'] = $response['profile_image_url'];
		$response['name'] = $response['login'];

		// because we faked how we got the user profile, the profile is missing some
		// keys; let's add them, knowing that we don't need them anway.
		$response['email']     = 'nope';
		$response['bio']       = '';
		$response['partnered'] = false;

		return $response;
	}

	// there is no Accept header in Helix API anymore
	protected function getDefaultHeaders() {
		return ['Client-ID' => $this->clientId];
	}

	// Helix API does not use "OAuth <token>" format, but instead the more standard "Bearer"
	protected function getAuthorizationHeaders($token = null) {
		if (isset($token)) {
			return ['Authorization' => 'Bearer '.$token->getToken()];
		}

		return [];
	}
}
