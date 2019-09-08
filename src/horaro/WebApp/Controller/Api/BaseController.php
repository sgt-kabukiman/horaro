<?php
/*
 * Copyright (c) 2019, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Controller\Api;

use horaro\WebApp\Controller\BaseController as RegularBaseController;
use horaro\WebApp\Exception\BadRequestException;
use horaro\WebApp\JsonRedirectResponse;
use horaro\WebApp\Pager\PagerInterface;
use League\Fractal\Resource;
use League\Fractal\Resource\ResourceInterface;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class BaseController extends RegularBaseController {
	// allow access to all public elements
	protected function hasResourceAccess($resource) {
		return $resource->isPublic();
	}

	protected function redirect($uri, $status = 302) {
		return new JsonRedirectResponse($uri, $status);
	}

	protected function getFractalManager() {
		return $this->app['fractal'];
	}

	protected function transform(ResourceInterface $resource) {
		$fractal   = $this->getFractalManager();
		$rootScope = $fractal->createData($resource);

		return $rootScope->toArray();
	}

	protected function respondWithCollection($collection, TransformerAbstract $transformer, PagerInterface $pager = null, $dataKey = 'data', $status = 200) {
		$collection = new Resource\Collection($collection, $transformer, $dataKey);
		$data       = $this->transform($collection);

		if ($pager) {
			$pager->setCurrentCollection($collection);
			$data['pagination'] = $pager->createData();
		}

		return $this->respondWithArray($data, $status);
	}

	protected function respondWithItem($item, TransformerAbstract $transformer, $dataKey = 'data', $status = 200) {
		$data = $this->transform(new Resource\Item($item, $transformer, $dataKey));

		return $this->respondWithArray($data, $status);
	}

	protected function respondWithArray($content = [], $status = 200, array $headers = []) {
		$request = $this->app['request'];

		$response = new JsonResponse($content, $status, $headers);
		$response->setEncodingOptions($response->getEncodingOptions() | JSON_UNESCAPED_SLASHES);

		if ($request->query->has('callback')) {
			try {
				$response->setCallback($request->query->get('callback'));
			}
			catch (\Exception $e) {
				throw new BadRequestException($e->getMessage());
			}
		}

		$response->setExpires(new \DateTime('1924-10-10 12:00:00 UTC'));
		$response->headers->addCacheControlDirective('no-cache', true);
		$response->headers->addCacheControlDirective('private', true);

		return $response;
	}
}
