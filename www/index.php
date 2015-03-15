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
require HORARO_ROOT.'/vendor/autoload.php';

Symfony\Component\HttpFoundation\Request::enableHttpMethodParameterOverride();

mb_internal_encoding('UTF-8');

$app = new horaro\WebApp\Application();
$app->run();
