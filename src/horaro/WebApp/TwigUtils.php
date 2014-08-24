<?php
/*
 * Copyright (c) 2014, Sgt. Kabukiman, https://bitbucket.org/sgt-kabukiman/
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp;

class TwigUtils {
	public function scheduleClass($idx, $availableSpace = 8) {
		$class = [];

		if ($idx >= 4) $class[] = 'hidden-lg';
		if ($idx >= 3) $class[] = 'hidden-md';
		if ($idx >= 2) $class[] = 'hidden-sm';
		if ($idx >= 2) $class[] = 'hidden-xs';

		return implode(' ', $class);
	}
}
