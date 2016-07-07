<?php
/*
 * Copyright (c) 2015, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Controller\Api;

use horaro\WebApp\Transformer\IndexTransformer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexController extends BaseController {
	public function indexAction(Request $request) {
		$html = $this->render('index/api.twig', [
			'baseUri' => $request->getUriForPath('')
		]);

		return $this->setCachingHeader(new Response($html), 'other');
	}
}
