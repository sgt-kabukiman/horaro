<?php
/*
 * Copyright (c) 2014, Sgt. Kabukiman, https://bitbucket.org/sgt-kabukiman/
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\Library\ScheduleTransformer;

use horaro\Library\Entity\Schedule;
use horaro\Library\ObscurityCodec;
use Symfony\Component\HttpFoundation\Request;

class JsonpTransformer extends JsonTransformer {
	protected $request;

	public function __construct(Request $request, ObscurityCodec $codec) {
		parent::__construct($codec);

		$this->request = $request;
		$this->hint    = false;
	}

	public function getContentType() {
		return 'application/javascript; charset=UTF-8';
	}

	public function getFileExtension() {
		return 'jsonp';
	}

	public function transform(Schedule $schedule, $public = false) {
		$callback = $this->request->query->get('callback');

		if (!$this->isValidCallback($callback)) {
			throw new \InvalidArgumentException('The given callback is malformed.');
		}

		$json = parent::transform($schedule, $public);

		// add empty inline comment to prevent content type sniffing attacks like Rosetta Flash
		return sprintf('/**/%s(%s);', $callback, $json);
	}

	/**
	 * @see https://gist.github.com/ptz0n/1217080
	 */
	protected function isValidCallback($callback) {
		$reserved = [
			'break', 'case', 'catch', 'class', 'const', 'continue', 'debugger', 'default', 'delete',
			'do', 'else', 'enum', 'export', 'extends', 'false', 'finally', 'for', 'function', 'if',
			'implements', 'import', 'in', 'instanceof', 'interface', 'let', 'new', 'null', 'package',
			'private', 'protected', 'public', 'return', 'static', 'super', 'switch', 'this', 'throw',
			'true', 'try', 'typeof', 'var', 'void', 'while', 'with', 'yield',
		];

		foreach (explode('.', $callback) as $identifier) {
			if (!preg_match('/^[a-zA-Z_$][0-9a-zA-Z_$]*(?:\[(?:".+"|\'.+\'|\d+)\])*?$/', $identifier)) {
				return false;
			}

			if (in_array($identifier, $reserved, true)) {
				return false;
			}
		}

		return true;
	}
}
