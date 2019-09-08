<?php
/*
 * Copyright (c) 2019, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Middleware;

use horaro\WebApp\Application;
use horaro\WebApp\Exception as Ex;
use Sentry\Client as SentryClient;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig_Environment;

class ErrorHandler {
	protected $sentry;
	protected $twig;
	protected $version;

	const OUTPUT_JSON = 'middleware.errorhandler.output-json';

	public $levels = array(
		E_WARNING           => 'Warning',
		E_NOTICE            => 'Notice',
		E_USER_ERROR        => 'User Error',
		E_USER_WARNING      => 'User Warning',
		E_USER_NOTICE       => 'User Notice',
		E_STRICT            => 'Runtime Notice',
		E_RECOVERABLE_ERROR => 'Catchable Fatal Error',
		E_DEPRECATED        => 'Deprecated',
		E_USER_DEPRECATED   => 'User Deprecated',
		E_ERROR             => 'Error',
		E_CORE_ERROR        => 'Core Error',
		E_COMPILE_ERROR     => 'Compile Error',
		E_PARSE             => 'Parse',
	);

	public function __construct(SentryClient $sentry, Twig_Environment $twig, $version) {
		$this->sentry  = $sentry;
		$this->twig    = $twig;
		$this->version = $version;
	}

	/**
	 * Sets up the error handlers
	 *
	 * This cannot be done by invoking this middleware as part of the before() callbacks,
	 * because those are not executed when no route is being matched.
	 */
	public function setup(Application $app) {
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

		$this->app = $app;

		// some of this has been copied from Symfony\Component\Debug\ErrorHandler
		register_shutdown_function(function() {
			if (null === $error = error_get_last()) {
				return;
			}

			$type = $error['type'];
			if (!in_array($type, array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE))) {
				return;
			}

			$level   = isset($this->levels[$type]) ? $this->levels[$type] : $type;
			$message = sprintf('%s: %s', $level, $error['message']);

			$this->report(new FatalErrorException($message, 0, $type, $error['file'], $error['line']));
		});
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
		$this->report($e);

		return $this->respond('errors/not_found.twig', $e, 404);
	}

	public function generic(\Exception $e) {
		$this->report($e);

		return $this->respond('errors/generic.twig', $e, 500);
	}

	protected function respond($template, \Exception $e, $status = null) {
		$status = $status ?: $e->getHttpStatus();
		$json   = $this->app['request']->attributes->get(self::OUTPUT_JSON);

		if ($json) {
			$data     = ['status' => $status, 'message' => $e->getMessage()];
			$response = new JsonResponse($data, $status);
		}
		else {
			$data     = ['e' => $e, 'result' => null]; // result is only for the login view
			$response = new Response($this->twig->render($template, $data), $status);
		}

		return $response;
	}

	protected function report(\Exception $e) {
		$this->sentry->captureException($e);
	}
}
