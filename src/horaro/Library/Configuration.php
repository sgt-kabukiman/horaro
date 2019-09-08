<?php
/*
 * Copyright (c) 2019, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\Library;

use Symfony\Component\Yaml\Yaml;

class Configuration extends \ArrayObject {
	public function loadFile($filename) {
		if (!file_exists($filename)) {
			throw new \RuntimeException('Configuration file "'.$filename.'" does not exist.');
		}

		$data = Yaml::parse(file_get_contents($filename));

		if (!is_array($data)) {
			throw new \RuntimeException('Configuration file "'.$filename.'" did not contain an array.');
		}

		foreach ($data as $key => $value) {
			$this[$key] = $value;
		}
	}
}
