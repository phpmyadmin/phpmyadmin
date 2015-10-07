<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * object the server plugin page
 *
 * @package PhpMyAdmin
 */
use PMA\libraries\Response;

/**
 * requirements
 */
require_once 'libraries/common.inc.php';

/**
 * JS includes
 */
$response = Response::getInstance();
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
$response->addHTML(PMA_getHtmlForSubPageHeader('plugins'));
$response->addHTML(PMA_getPluginTab($plugins));

exit;
