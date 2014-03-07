<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * include file for display import : server, database, table
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
require_once './libraries/file_listing.lib.php';
require_once './libraries/plugin_interface.lib.php';
require_once './libraries/display_import.lib.php';
require_once './libraries/display_import_ajax.lib.php';

/* Scan for plugins */
$import_list = PMA_getPlugins(
    "import",
    'libraries/plugins/import/',
    $import_type
);

/* Fail if we didn't find any plugin */
if (empty($import_list)) {
    PMA_Message::error(
        __(
            'Could not load import plugins, please check your installation!'
        )
    )->display();
    exit;
}

$timeout_passed_str = isset($timeout_passed)? $timeout_passed : null;
$offset_str = isset($offset)? $offset : null;
$html = PMA_getHtmlForImport(
    $upload_id,
    $import_type,
    $db,
    $table,
    $max_upload_size,
    $import_list,
    $timeout_passed_str,
    $offset_str
);

$response = PMA_Response::getInstance();
$response->addHTML($html);

?>
