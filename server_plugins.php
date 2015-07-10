<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * object the server plugin page
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
$scripts->addFile('server_plugins.js');

/**
 * Does the common work
 */
require 'libraries/server_common.inc.php';
require 'libraries/server_plugins.lib.php';

$plugins = PMA_getServerPlugins();

/**
 * Displays the page
 */
$response->addHTML('<div>');
$response->addHTML(PMA_getHtmlForPluginsSubTabs('server_plugins.php'));
$response->addHTML(PMA_getPluginTab($plugins));
$response->addHTML('</div>');

exit;
