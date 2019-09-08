<?php
/*
 * Copyright (c) 2019, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Pager;

use horaro\WebApp\Exception\BadRequestException;
use League\Fractal\Resource\Collection;
use Symfony\Component\HttpFoundation\Request;

class OffsetLimitPager implements PagerInterface {
	protected $request;
	protected $defaultSize;
	protected $maxSize;
	protected $collection;

	public function __construct(Request $request, $pageSize = 20, $maxPageSize = 100, Collection $collection = null) {
		$this->request     = $request;
		$this->defaultSize = (int) $pageSize;
		$this->maxSize     = (int) $maxPageSize;
		$this->collection  = $collection;
	}

	public function getOffset() {
		$offset = (int) $this->request->query->get('offset');

		if ($offset < 0) {
			$offset = 0;
		}

		return $offset;
	}

	public function getPageSize() {
		$size = (int) $this->request->query->get('max');

		if ($size < 1) {
			$size = $this->defaultSize;
		}
		elseif ($size > $this->maxSize) {
			$size = $this->maxSize;
		}

		return $size;
	}

	public function getOrder(array $allowed, $default) {
		$orderBy = trim($this->request->query->get('orderby'));

		if (mb_strlen($orderBy) === 0) {
			return $default;
		}

		// do *not* perform strtolower or strtoupper to sanitise $orderBy here, or else
		// the return value of this function will be something unexpected. The caller might
		// perform some additional logic on the return value.

		if (!in_array($orderBy, $allowed, true)) {
			throw new BadRequestException('Invalid `orderby` identifier given. Possible values are \''.implode("', '", $allowed).'\', default if none given is \''.$default.'\'.');
		}

		return $orderBy;
	}

	public function getDirection($default) {
		$direction = trim($this->request->query->get('direction'));

		if (mb_strlen($direction) === 0) {
			return $default;
		}

		$direction = strtoupper($direction);

		if (!in_array($direction, ['ASC', 'DESC'], true)) {
			throw new BadRequestException('Invalid `direction` identifier given. Possible values are \'asc\', \'desc\', default if none given is \''.$default.'\'.');
		}

		return $direction;
	}

	public function setCurrentCollection(Collection $collection) {
		$this->collection = $collection;
	}

	public function createData() {
		$offset     = $this->getOffset();
		$size       = $this->getPageSize();
		$actualSize = count($this->collection->getData());
		$links      = [];

		if ($offset > 0) {
			$prevOffset = $offset - $size;

			if ($prevOffset < 0) {
				$prevOffset = 0;
			}

			$links[] = ['rel' => 'prev', 'uri' => $this->getUrl($prevOffset)];
		}

		if ($actualSize >= $size) {
			$links[] = ['rel' => 'next', 'uri' => $this->getUrl($offset + $size)];
		}

		return [
			'offset' => $offset,
			'max'    => $size,
			'size'   => $actualSize,
			'links'  => $links,
		];
	}

	protected function getUrl($offset) {
		$size    = $this->getPageSize();
		$request = clone $this->request;
		$query   = $request->query;
		$baseUri = $request->getSchemeAndHttpHost().$request->getBaseUrl().$request->getPathInfo();

		if ($offset > 0) {
			$query->set('offset', $offset);
		}
		else {
			$query->remove('offset');
		}

		if ($size != $this->defaultSize) {
			$query->set('max', $size);
		}
		else {
			$query->remove('max');
		}

		if (count($query) > 0) {
			$baseUri .= '?'.http_build_query($query->all(), '', '&');
		}

		return $baseUri;
	}
}
