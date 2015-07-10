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
 * JS includes
 */
$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('jquery/jquery.tablesorter.js');
$scripts->addFile('server_modules.js');

/**
 * Does the common work
 */
require 'libraries/server_common.inc.php';
require 'libraries/server_plugins.lib.php';

$modules = PMA_getServerModules();

/**
 * Displays the page
 */
$response->addHTML('<div>');
$response->addHTML(PMA_getHtmlForPluginsSubTabs('server_modules.php'));
$response->addHTML(PMA_getModuleTab($modules));
$response->addHTML('</div>');

exit;
