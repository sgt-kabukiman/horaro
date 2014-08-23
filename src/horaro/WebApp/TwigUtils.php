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

		$class[] = ($idx < 4) ? 'col-lg-2' : 'hidden-lg';
		$class[] = ($idx < 3) ? 'col-md-2' : 'hidden-md';
		$class[] = ($idx < 2) ? 'col-sm-3' : 'hidden-sm';
		$class[] = ($idx < 2) ? 'col-xs-3' : 'hidden-xs';

		return implode(' ', $class);
	}
}
