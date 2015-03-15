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

use horaro\Library\Configuration;
use Symfony\Component\HttpFoundation\Request;

class ConfigController extends BaseController {
	public function formAction() {
		return $this->renderForm();
	}

	public function updateAction(Request $request) {
		$config    = $this->app['config'];
		$validator = $this->app['validator.admin.utils.config'];
		$result    = $validator->validate([
			'bcrypt_cost'         => $request->request->get('bcrypt_cost'),
			'cookie_lifetime'     => $request->request->get('cookie_lifetime'),
			'csrf_token_name'     => $request->request->get('csrf_token_name'),
			'default_event_theme' => $request->request->get('default_event_theme'),
			'default_language'    => $request->request->get('default_language'),
			'max_events'          => $request->request->get('max_events'),
			'max_schedule_items'  => $request->request->get('max_schedule_items'),
			'max_schedules'       => $request->request->get('max_schedules'),
			'max_users'           => $request->request->get('max_users'),
			'sentry_dsn'          => $request->request->get('sentry_dsn')
		], $config);

		if ($result['_errors']) {
			return $this->renderForm($config, $result);
		}

		// update configuration

		$rtconfig = $this->app['runtime-config'];
		$rtconfig
			->set('bcrypt_cost',         $result['bcrypt_cost']['filtered'])
			->set('cookie_lifetime',     $result['cookie_lifetime']['filtered'])
			->set('csrf_token_name',     $result['csrf_token_name']['filtered'])
			->set('default_event_theme', $result['default_event_theme']['filtered'])
			->set('default_language',    $result['default_language']['filtered'])
			->set('max_events',          $result['max_events']['filtered'])
			->set('max_schedule_items',  $result['max_schedule_items']['filtered'])
			->set('max_schedules',       $result['max_schedules']['filtered'])
			->set('max_users',           $result['max_users']['filtered'])
			->set('sentry_dsn',          $result['sentry_dsn']['filtered'])
		;

		$this->getEntityManager()->flush();

		// done

		$this->addSuccessMsg('The configuration has been updated.');

		return $this->redirect('/-/admin/utils/config');
	}

	protected function renderForm(Configuration $config = null, $result = null) {
		return $this->render('admin/utils/config.twig', [
			'config'    => $config ?: $this->app['config'],
			'languages' => $this->getLanguages(),
			'result'    => $result
		]);
	}
}
