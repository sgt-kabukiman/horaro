<?php
/*
 * Copyright (c) 2015, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Controller;

use horaro\Library\Entity\User;
use horaro\Library\Entity\Event;
use horaro\Library\Entity\Schedule;
use horaro\Library\Entity\ScheduleItem;
use horaro\WebApp\Application;
use horaro\WebApp\Exception as Ex;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

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

	protected function hasResourceAccess($resource) {
		return $this->app['rolemanager']->hasRegularAccess($this->getCurrentUser(), $resource);
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
		return $this->app['obscurity-codec']->encode($id, $entityType);
	}

	protected function decodeID($hash, $entityType = null) {
		return $this->app['obscurity-codec']->decode($hash, $entityType);
	}

	protected function getPayload(Request $request, $asArray = true) {
		$content = $request->getContent();
		$payload = @json_decode($content, $asArray);
		$error   = json_last_error();

		if ($error !== JSON_ERROR_NONE) {
			throw new Ex\BadRequestException('Request does not contain valid JSON.', 900);
		}

		return $payload;
	}

	protected function respondWithArray($content = [], $status = 200, array $headers = []) {
		$response = new JsonResponse($content, $status, $headers);

		$response->setExpires(new \DateTime('1924-10-10 12:00:00', new \DateTimeZone('UTC')));
		$response->headers->addCacheControlDirective('no-cache', true);
		$response->headers->addCacheControlDirective('private', true);

		return $response;
	}

	protected function getRequestedEvent(Request $request) {
		return $request->attributes->get('event');
	}

	protected function getRequestedSchedule(Request $request) {
		return $request->attributes->get('schedule');
	}

	protected function getRequestedScheduleItem(Request $request) {
		return $request->attributes->get('schedule_item');
	}

	protected function getRequestedScheduleColumn(Request $request) {
		return $request->attributes->get('schedule_column');
	}

	protected function getLanguages() {
		return $this->app['config']['languages'];
	}

	protected function getDefaultLanguage() {
		return $this->app['config']['default_language'];
	}

	protected function addFlashMsg($type, $message) {
		$this->app['session']->getFlashBag()->add($type, $message);
	}

	protected function addSuccessMsg($message) {
		$this->addFlashMsg('success', $message);
	}

	protected function addErrorMsg($message) {
		$this->addFlashMsg('error', $message);
	}

	protected function exceedsMaxUsers() {
		return $this->getRepository('User')->count() >= $this->app['config']['max_users'];
	}

	protected function exceedsMaxEvents(User $u) {
		return $this->getRepository('Event')->count($u) >= $u->getMaxEvents();
	}

	protected function exceedsMaxSchedules(Event $e) {
		return $this->getRepository('Schedule')->count($e) >= $e->getMaxSchedules();
	}

	protected function exceedsMaxScheduleItems(Schedule $s) {
		return $this->getRepository('ScheduleItem')->count($s) >= $s->getMaxItems();
	}

	protected function exceedsMaxScheduleColumns(Schedule $s) {
		return $this->getRepository('ScheduleColumn')->countVisible($s) >= 10;
	}

	protected function setCachingHeader(Response $response, $resourceType, \DateTime $lastModified = null) {
		if ($lastModified) {
			$response->setLastModified($lastModified);
		}

		$times = $this->app['config']['cache_ttls'];
		$user  = $this->app['user'];
		$ttl   = $times[$resourceType];

		if ($user) {
			$response->setPrivate();
		}
		elseif ($ttl > 0) {
			$response->setTtl($ttl * 60);
			$response->headers->set('X-Accel-Expires', $ttl * 60); // nginx will not honor s-maxage set by setTtl() above
		}

		return $response;
	}
}
