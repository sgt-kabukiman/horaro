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

use Michelf\Markdown;

class MarkdownConverter {
	protected $md;

	public function __construct(Markdown $md) {
		$md->empty_element_suffix = '>';
		$md->no_markup            = false;
		$md->no_entities          = false;
		$md->url_filter_func      = function($url) {
			$url      = html_entity_decode($url, ENT_QUOTES);
			$filtered = filter_var($url, FILTER_VALIDATE_URL);

			if ($filtered === false) {
				return '#';
			}

			$scheme = parse_url($filtered, PHP_URL_SCHEME);

			if (!is_string($scheme) || !in_array($scheme, ['http', 'https', 'mailto'])) {
				return '#';
			}

			return $url;
		};

		$this->md = $md;
	}

	public function convert($markdown) {
		$html = htmlspecialchars($markdown, ENT_QUOTES, 'UTF-8');
		$html = $this->md->transform($html);
		$html = str_replace('<img', '<img class="img-responsive"', $html);

		return $html;
	}
}
