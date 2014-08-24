<?php
/*
 * Copyright (c) 2014, Sgt. Kabukiman, https://bitbucket.org/sgt-kabukiman/
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Controller;

use horaro\WebApp\Application;
use horaro\WebApp\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class BaseController {
	protected $app;

	public function __construct(Application $app) {
		$this->app = $app;
	}

	protected function redirect($uri, $status = 302) {
		return new RedirectResponse($uri, $status);
	}

	protected function render($template, array $params = []) {
		return $this->app['twig']->render($template, $params);
	}

	protected function getEntityManager() {
		return $this->app['entitymanager'];
	}

	protected function getRepository($className) {
		return $this->getEntityManager()->getRepository('horaro\Library\Entity\\'.$className);
	}

	public function getCurrentUser() {
		return $this->app['user'];
	}

	protected function encodeID($id, $entityType = null) {
		return $id;
	}

	protected function decodeID($hash, $entityType = null) {
		if (!ctype_digit($hash)) {
			return null;
		}

		$id = (int) $hash;

		return $id ?: null;
	}

	protected function getPayload(Request $request, $asArray = true) {
		$content = $request->getContent();
		$payload = @json_decode($content, $asArray);
		$error   = json_last_error();

		if ($error !== JSON_ERROR_NONE) {
			throw new BadRequestException('Request does not contain valid JSON.', 900);
		}

		return $payload;
	}

	protected function respondWithArray($content = [], $status = 200, array $headers = []) {
		$response = new JsonResponse($content, $status, $headers);

		$response->setExpires(new \DateTime('1924-10-10 12:00:00 UTC'));
		$response->headers->addCacheControlDirective('no-cache', true);
		$response->headers->addCacheControlDirective('private', true);

		return $response;
	}
}
