<?php
/*
 * Copyright (c) 2019, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Transformer;

use horaro\WebApp\Application;
use League\Fractal\TransformerAbstract;

abstract class BaseTransformer extends TransformerAbstract {
	protected $codec;
	protected $baseUri;

	public function __construct(Application $app) {
		$this->codec   = $app['obscurity-codec'];
		$this->baseUri = $app['request']->getUriForPath('');
	}

	protected function encodeID($id, $entityType = null) {
		return $this->codec->encode($id, $entityType);
	}

	protected function decodeID($hash, $entityType = null) {
		return $this->codec->decode($hash, $entityType);
	}

	protected function base() {
		return $this->baseUri;
	}

	protected function url($relative) {
		return $this->base().'/-/api'.$relative;
	}
}
