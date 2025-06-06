<?php

use OCA\Jukebox\AppInfo\Application;
use OCP\Util;

/* @var array $_ */
$script = $_['script'];
Util::addScript(Application::APP_ID, Application::JS_DIR . "/jukebox-$script");
Util::addStyle(Application::APP_ID, Application::CSS_DIR . '/jukebox-style');
?>
<div id="jukebox-app"></div>
