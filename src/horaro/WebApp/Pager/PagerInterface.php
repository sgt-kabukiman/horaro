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

use League\Fractal\Resource\Collection;

interface PagerInterface {
	public function getOffset();
	public function getPageSize();
	public function getOrder(array $allowed, $default);
	public function getDirection($default);
	public function setCurrentCollection(Collection $collection);
	public function createData();
}
