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

use horaro\Library\Entity\Event;
use horaro\WebApp\Transformer\BaseTransformer;

class EventTransformer extends BaseTransformer {
	protected $availableIncludes = [];

	public function transform(Event $event) {
		$id        = $event->getID();
		$encodedID = $this->encodeID($id, 'event');
		$owner     = $event->getOwner();
		$links     = [
			['rel' => 'self',      'uri' => $this->url('/v1/events/'.$encodedID)],
			['rel' => 'schedules', 'uri' => $this->url('/v1/events/'.$encodedID.'/schedules')],
		];

		return [
			'id'          => $encodedID,
			'name'        => $event->getName(),
			'slug'        => $event->getSlug(),
			'link'        => $this->base().'/'.$event->getSlug(),
			'description' => $event->getDescription(),
			'owner'       => $owner->getName(),
			'website'     => $event->getWebsite(),
			'twitter'     => $event->getTwitter(),
			'twitch'      => $event->getTwitch(),
			'links'       => $links,
		];
	}
}
