<?php
/*
 * Copyright (c) 2015, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp;

class ContentSecurityPolicy {
	protected $policy;
	protected $reportOnly;
	protected $reportURI;

	public function __construct() {
		$this->reportOnly = false;
		$this->reportURI  = null;
		$this->policy     = [
			'default-src' => [],
			'connect-src' => [],
			'font-src'    => [],
			'frame-src'   => [],
			'img-src'     => [],
			'media-src'   => [],
			'object-src'  => [],
			'report-uri'  => [],
			'script-src'  => [],
			'style-src'   => []
		];
	}

	public function addConnectSource($source) {
		return $this->add('connect-src', $source);
	}

	public function addDefaultSource($source) {
		return $this->add('default-src', $source);
	}

	public function addFontSource($source) {
		return $this->add('font-src', $source);
	}

	public function addFrameSource($source) {
		return $this->add('frame-src', $source);
	}

	public function addImageSource($source) {
		return $this->add('img-src', $source);
	}

	public function addMediaSource($source) {
		return $this->add('media-src', $source);
	}

	public function addObjectSource($source) {
		return $this->add('object-src', $source);
	}

	public function addScriptSource($source) {
		return $this->add('script-src', $source);
	}

	public function addStyleSource($source) {
		return $this->add('style-src', $source);
	}

	public function setReportOnly($flag) {
		$this->reportOnly = !!$flag;
		return $this;
	}

	public function setReportUri($uri) {
		$this->reportURI = $uri;
		return $this;
	}

	public function getHeader() {
		$header = [];

		foreach ($this->policy as $key => $values) {
			if (count($values) === 0) continue;

			$header[] = sprintf('%s %s', $key, implode(' ', $values));
		}

		if ($this->reportURI !== null) {
			$header[] = 'report-uri '.$this->reportURI;
		}

		return implode('; ', $header);
	}

	protected function add($type, $value) {
		if (in_array($value, ['self', 'none', 'unsafe-inline', 'unsafe-eval'], true)) {
			$value = "'$value'";
		}

		if (!isset($this->policy[$type])) {
			$this->policy[$type] = [];
		}

		if (!in_array($value, $this->policy[$type], true)) {
			$this->policy[$type][] = $value;
		}

		return $this;
	}
}
