<?php
/*
 * Copyright (c) 2015, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Controller\Admin\Utils;

use Symfony\Component\HttpFoundation\Response;

class ServerInfoController extends BaseController {
	public function formAction() {
		if (function_exists('phpinfo')) {
			$this->app['csp']->addFrameSource('self');
		}

		return $this->render('admin/utils/serverinfo.twig', [
			'phpversion' => PHP_VERSION,
			'root'       => HORARO_ROOT,
			'config'     => $this->app['config'],
			'hasPhpinfo' => function_exists('phpinfo')
		]);
	}

	public function phpinfoAction() {
		if (function_exists('phpinfo')) {
			phpinfo();
			die;
		}

		return new Response('phpinfo() is disabled.', 501);
	}
}
