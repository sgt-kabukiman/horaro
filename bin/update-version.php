<?php
/*
 * Copyright (c) 2014, Sgt. Kabukiman, https://bitbucket.org/sgt-kabukiman/
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

chdir(dirname(__DIR__));

// try to get Mercurial to tell us the exact version
$exit   = 0;
$output = [];

exec('hg log -r . --template="{tags}|{node|short}"', $output, $exit);

if ($exit !== 0) {
	exit(1);
}

list($tags, $node) = explode('|', $output[0]);
$version           = 'rev. '.substr($node, 0, 8);

if (!empty($tags)) {
	$tags = explode(' ', $tags);

	foreach ($tags as $tag) {
		if ($tag === 'tip') continue;

		$version = $tag;
		break;
	}
}

// is the working copy dirty?
$exit   = 0;
$output = [];

exec('hg st -mard', $output, $exit);

if (!empty($output)) {
	$version .= '+';
}

file_put_contents('version', $version);
