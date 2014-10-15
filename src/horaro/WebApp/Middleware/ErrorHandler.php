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

use horaro\WebApp\Application;
use horaro\WebApp\Exception as Ex;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig_Environment;

class ErrorHandler {
	protected $twig;

	public function __construct(Twig_Environment $twig) {
		$this->twig = $twig;
	}

	public function __invoke(Request $request, Application $app) {
		$app->error([$this, 'handleNotLoggedIn']);
		$app->error([$this, 'handleAccessDenied']);
		$app->error([$this, 'handleReverseAuthErrors']);
		$app->error([$this, 'handleBadCsrf']);
		$app->error([$this, 'handleBadRequest']);
		$app->error([$this, 'sfRoutingException']);
		$app->error([$this, 'notFound']);

		if (!$app['debug']) {
			$app->error([$this, 'generic']);
		}
	}

	public function handleNotLoggedIn(Ex\UnauthorizedException $e) {
		return $this->respond('index/login.twig', $e);
	}

	public function handleAccessDenied(Ex\ForbiddenException $e) {
		return $this->respond('errors/access_denied.twig', $e);
	}

	public function handleReverseAuthErrors(Ex\TooAuthorizedException $e) {
		return new RedirectResponse('/-/home');
	}

	public function handleBadCsrf(Ex\BadCsrfTokenException $e) {
		return $this->respond('errors/bad_csrf_token.twig', $e);
	}

	public function handleBadRequest(Ex\BadRequestException $e) {
		return $this->respond('errors/bad_request.twig', $e);
	}

	public function notFound(Ex\NotFoundException $e) {
		return $this->respond('errors/not_found.twig', $e);
	}

	public function sfRoutingException(NotFoundHttpException $e) {
		return $this->respond('errors/not_found.twig', $e, 404);
	}

	public function generic(\Exception $e) {
		return $this->respond('errors/generic.twig', $e, 500);
	}

	protected function respond($template, \Exception $e, $status = null) {
		$data = ['e' => $e, 'result' => null]; // result is only for the login view

		return new Response($this->twig->render($template, $data), $status ?: $e->getHttpStatus());
	}
}
