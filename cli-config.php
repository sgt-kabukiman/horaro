<?php
/*
 * Copyright (c) 2019, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use Doctrine\ORM\Tools\Console\ConsoleRunner;
use horaro\WebApp\Application;

define('HORARO_ROOT', __DIR__);
require HORARO_ROOT.'/vendor/autoload.php';

$app = new Application();

return ConsoleRunner::createHelperSet($app['entitymanager']);
