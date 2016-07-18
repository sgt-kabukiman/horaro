<?php
/*
 * Copyright (c) 2015, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

define('HORARO_ROOT', dirname(__DIR__));

$file = HORARO_ROOT.'/maintenance';

if (file_exists($file)) {
	$allowedIPs = array_map('trim', file($file));

	if (isset($_SERVER['REMOTE_ADDR']) && !in_array($_SERVER['REMOTE_ADDR'], $allowedIPs)) {
		header('HTTP/1.1 503 Service Unavailable');
		header('Cache-Control: private, no-cache');
		header('Expires: Tue, 09 Apr 1975 12:00:00 GMT');
		print file_get_contents(HORARO_ROOT.'/resources/maintenance.html');
		exit(1);
	}
}

require HORARO_ROOT.'/vendor/autoload.php';

Symfony\Component\HttpFoundation\Request::enableHttpMethodParameterOverride();

mb_internal_encoding('UTF-8');

$app = new horaro\WebApp\Application();
$app->run();
