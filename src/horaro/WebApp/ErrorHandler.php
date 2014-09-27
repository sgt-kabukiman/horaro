<?php
/*
 * Copyright (c) 2014, Sgt. Kabukiman, https://bitbucket.org/sgt-kabukiman/
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp;

use horaro\WebApp\Exception\TooAuthorizedException;
use horaro\WebApp\Exception\UnauthorizedException;
use horaro\WebApp\Exception\BadCsrfTokenException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ErrorHandler {
	protected $app;

	public function __construct(Application $app) {
		$this->app = $app;
	}

	public function handleAuthErrors(UnauthorizedException $e) {
		return new Response($this->app['twig']->render('index/login.twig', ['result' => null]), $e->getHttpStatus());
	}

	public function handleReverseAuthErrors(TooAuthorizedException $e) {
		return new RedirectResponse('/-/home');
	}

	public function handleBadCsrf(BadCsrfTokenException $e) {
		return new Response($this->app['twig']->render('errors/bad_csrf_token.twig'), $e->getHttpStatus());
	}

	public function notFound(NotFoundHttpException $e) {
		return new Response($this->app['twig']->render('errors/not_found.twig'), 404);
	}

	public function generic(\Exception $e) {
		return new Response($this->app['twig']->render('errors/generic.twig'), 500);
	}
}
