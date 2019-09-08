<?php
/*
 * Copyright (c) 2019, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Controller\Api\Version1;

use horaro\WebApp\Controller\Api\BaseController;
use horaro\WebApp\Transformer\Version1\IndexTransformer;
use Symfony\Component\HttpFoundation\Request;

class IndexController extends BaseController {
	public function indexAction(Request $request) {
		return $this->respondWithItem(null, new IndexTransformer($this->app));
	}
}
