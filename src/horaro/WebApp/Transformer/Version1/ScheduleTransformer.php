<?php
/*
 * Copyright (c) 2015, Sgt. Kabukiman, https://github.com/sgt-kabukiman
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

class ScheduleTransformer extends BaseTransformer {
	protected $availableIncludes = [];

	private $includeHiddenColumns = false;

	public function __construct(Application $app, $includeHiddenColumns = false) {
		parent::__construct($app);

		$this->includeHiddenColumns = $includeHiddenColumns;
	}

	public function transform(Schedule $schedule) {
		$transformer = new JsonTransformer($this->codec);
		$transformed = json_decode($transformer->transform($schedule, false, $this->includeHiddenColumns), true);

		$data = $transformed['schedule'];

		// remove private data (do not use transform()'s $public parameter because it removes the IDs as well)
		unset($data['theme'], $data['secret']);

		// embedding the event is handled by Fractal
		unset($data['event']);

		// replace "url" with an absolute "link"
		$data['link'] = $this->base().$data['url'];
		unset($data['url']);

		// "re-sort"
		$i = $data['items'];
		$c = $data['columns'];

		unset($data['items'], $data['columns']);

		$data['columns'] = $c;
		$data['items']   = $i;

		// add additional API links
		$eventID       = $this->encodeID($schedule->getEvent()->getID(), 'event');
		$data['links'] = [
			['rel' => 'self',  'uri' => $this->url('/v1/schedules/'.$data['id'])],
			['rel' => 'event', 'uri' => $this->url('/v1/events/'.$eventID)],
		];

		return $data;
	}
}
