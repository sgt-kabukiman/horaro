<?php
/*
 * Copyright (c) 2015, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Middleware;

use horaro\WebApp\ResourceResolver;
use Symfony\Component\HttpFoundation\Request;

class Resolver {
	protected $resolver;

	public function __construct(ResourceResolver $resolver) {
		$this->resolver = $resolver;
	}

	public function __invoke(Request $request) {
		$params = $request->attributes->get('_route_params');

		foreach ($params as $resourceKey => $userValue) {
			$resolved = null;
			$encoded  = false;

			// if the parameter is encoded, decode it
			if (substr($resourceKey, -2) === '_e') {
				$resourceKey = substr($resourceKey, 0, -2);
				$encoded     = true;
			}

			switch ($resourceKey) {
				case 'event':           $resolved = $this->resolver->resolveEventID         ($userValue, $encoded); break;
				case 'schedule':        $resolved = $this->resolver->resolveScheduleID      ($userValue, $encoded); break;
				case 'schedule_item':   $resolved = $this->resolver->resolveScheduleItemID  ($userValue, $encoded); break;
				case 'schedule_column': $resolved = $this->resolver->resolveScheduleColumnID($userValue, $encoded); break;
				case 'user':            $resolved = $this->resolver->resolveUserID          ($userValue, $encoded); break;
				default:                $resolved = $userValue;
			}

			$request->attributes->set($resourceKey, $resolved);
			$request->attributes->set($resourceKey.':input', $userValue);
		}

		return $request;
	}
}
