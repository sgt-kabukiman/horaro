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

use Depotwarehouse\OAuth2\Client\Twitch\Provider\Twitch as BaseProvider;
use League\OAuth2\Client\Token\AccessToken;

class TwitchProvider extends BaseProvider {
	public $scopes = [];

	protected function fetchResourceOwnerDetails(AccessToken $token) {
		// fetch root to get the link to the authenticated user's profile
		$headers  = $this->getHeaders($token);
		$url      = $this->getAuthenticatedUrlForEndpoint('/kraken', $token);
		$request  = $this->getAuthenticatedRequest(self::METHOD_GET, $url, $token);
		$response = $this->getResponse($request);

		// construct the URL to this user's profile
		$user = $response['token']['user_name'];
		$url  = $this->apiDomain.'/kraken/users/'.urlencode($user);

		// and fetch it
		$request  = $this->getAuthenticatedRequest(self::METHOD_GET, $url, $token);
		$response = $this->getResponse($request);

		// because we faked how we got the user profile, the profile is missing some
		// keys; let's add them, knowing that we don't need them anway.
		$response['email'] = 'nope';
		$response['partnered'] = false;

		return $response;
    }
}
