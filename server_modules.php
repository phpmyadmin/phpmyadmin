<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * object the server modules page
 *
 * @package PhpMyAdmin
 */

/**
 * requirements
 */
require_once 'libraries/common.inc.php';

/**
 * Does the common work
 */
require 'libraries/server_common.inc.php';
require 'libraries/server_plugins.lib.php';

$modules = PMA_getServerModules();

/**
 * Displays the page
 */
$response = PMA_Response::getInstance();
$response->addHTML('<div>');
$response->addHTML(PMA_getHtmlForPluginsSubTabs('server_modules.php'));
$response->addHTML(PMA_getModuleTab($modules));
$response->addHTML('</div>');

exit;
