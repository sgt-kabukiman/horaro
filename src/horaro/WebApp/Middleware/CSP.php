<?php
/*
 * Copyright (c) 2014, Sgt. Kabukiman, https://bitbucket.org/sgt-kabukiman/
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Middleware;

use horaro\WebApp\ContentSecurityPolicy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CSP {
	protected $policy;

	public function __construct(ContentSecurityPolicy $policy) {
		$this->policy = $policy;
	}

	public function before(Request $request) {
		$proto = $request->getScheme();

		$this->policy
			->addDefaultSource('none') // without this, all non-defined sources are ALLOWED, which is not what we want

			->addConnectSource('self')

			->addScriptSource('self')
			->addScriptSource($proto.'://ajax.googleapis.com/')
			->addScriptSource($proto.'://maxcdn.bootstrapcdn.com/')
			->addScriptSource($proto.'://cdnjs.cloudflare.com/')

			->addStyleSource('self')
			->addStyleSource($proto.'://fonts.googleapis.com/')
			->addStyleSource($proto.'://maxcdn.bootstrapcdn.com/')
			->addStyleSource($proto.'://cdnjs.cloudflare.com/')

			->addFontSource('self')
			->addFontSource($proto.'://fonts.gstatic.com/')
			->addFontSource($proto.'://fonts.googleapis.com/')
			->addFontSource($proto.'://maxcdn.bootstrapcdn.com/')

			->addImageSource('self')
			->addImageSource('data:')
			->addImageSource($proto.'://www.gravatar.com/')
		;
	}

	public function after(Request $request, Response $response) {
		$header = $this->policy->getHeader();

		if (strlen($header) > 0) {
			$name = 'Content-Security-Policy';

			if ($this->reportOnly) {
				$name .= '-Report-Only';
			}

			$response->headers->set($name, $header);
		}
	}
}
