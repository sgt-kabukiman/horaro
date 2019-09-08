<?php
/*
 * Copyright (c) 2019, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Transformer\Version1;

use horaro\Library\Entity\Schedule;
use horaro\Library\ScheduleTransformer\JsonTransformer;
use horaro\WebApp\Application;
use horaro\WebApp\Transformer\BaseTransformer;

class ScheduleTickerTransformer extends BaseTransformer {
	protected $availableIncludes = [];

	private $includeHiddenColumns = false;

	public function __construct(Application $app, $includeHiddenColumns = false) {
		parent::__construct($app);

		$this->includeHiddenColumns = $includeHiddenColumns;
	}

	public function transform(array $ticker) {
		$schedule    = $ticker['schedule'];
		$transformer = new JsonTransformer($this->codec);
		$data        = $transformer->transformTicker($schedule, $ticker, $this->includeHiddenColumns);

		// replace "url" with an absolute "link"
		$data['schedule']['link'] = $this->base().$data['schedule']['url'];
		unset($data['schedule']['url']);

		// add additional API links
		$eventID       = $this->encodeID($schedule->getEvent()->getID(), 'event');
		$data['links'] = [
			['rel' => 'self',     'uri' => $this->url('/v1/schedules/'.$data['schedule']['id']).'/ticker'],
			['rel' => 'schedule', 'uri' => $this->url('/v1/schedules/'.$data['schedule']['id'])],
			['rel' => 'event',    'uri' => $this->url('/v1/events/'.$eventID)],
		];

		return $data;
	}
}
