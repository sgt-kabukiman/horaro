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

use horaro\WebApp\Exception\BadCsrfTokenException;
use horaro\WebApp\Exception\ForbiddenException;
use horaro\WebApp\Exception\NotFoundException;
use horaro\WebApp\Exception\TooAuthorizedException;
use horaro\WebApp\Exception\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ErrorHandler {
	protected $app;

	public function __construct(Application $app) {
		$this->app = $app;
	}

	public function setupMiddleware($debug) {
		$this->app->error([$this, 'handleNotLoggedIn']);
		$this->app->error([$this, 'handleAccessDenied']);
		$this->app->error([$this, 'handleReverseAuthErrors']);
		$this->app->error([$this, 'handleBadCsrf']);
		$this->app->error([$this, 'sfRoutingException']);
		$this->app->error([$this, 'notFound']);

		if (!$debug) {
			$this->app->error([$this, 'generic']);
		}
	}

	public function handleNotLoggedIn(UnauthorizedException $e) {
		return $this->respond('index/login.twig', $e);
	}

	public function handleAccessDenied(ForbiddenException $e) {
		return $this->respond('errors/access_denied.twig', $e);
	}

	public function handleReverseAuthErrors(TooAuthorizedException $e) {
		return new RedirectResponse('/-/home');
	}

	public function handleBadCsrf(BadCsrfTokenException $e) {
		return $this->respond('errors/bad_csrf_token.twig', $e);
	}

	public function sfRoutingException(NotFoundHttpException $e) {
		return $this->respond('errors/not_found.twig', $e);
	}

	public function notFound(NotFoundException $e) {
		return $this->respond('errors/not_found.twig', $e);
	}

	public function generic(\Exception $e) {
		return $this->respond('errors/generic.twig', $e, 500);
	}

	protected function respond($template, \Exception $e, $status = null) {
		$data = ['e' => $e, 'result' => null]; // result is only for the login view

		return new Response($this->app['twig']->render($template, $data), $status ?: $e->getHttpStatus());
	}
}
