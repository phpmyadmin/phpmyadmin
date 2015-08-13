<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Server import page
 *
 * @package PhpMyAdmin
 */

/**
 *
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/config/page_settings.class.php';

PMA_PageSettings::showGroup('Import');

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('import.js');

/**
 * Does the common work
 */
require 'libraries/server_common.inc.php';

require 'libraries/display_import.lib.php';
$response = PMA_Response::getInstance();
$response->addHTML(
    PMA_getImportDisplay(
        'server', $db, $table, $max_upload_size
    )
);
