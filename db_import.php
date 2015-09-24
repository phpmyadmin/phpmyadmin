<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Database import page
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';
require_once 'libraries/config/page_settings.class.php';

PMA_PageSettings::showGroup('Import');

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('import.js');

/**
 * Gets tables information and displays top links
 */
require 'libraries/db_common.inc.php';

list(
    $tables,
    $num_tables,
    $total_num_tables,
    $sub_part,
    $is_show_stats,
    $db_is_system_schema,
    $tooltip_truename,
    $tooltip_aliasname,
    $pos
) = PMA_Util::getDbInfo($db, isset($sub_part) ? $sub_part : '');

require 'libraries/display_import.lib.php';
$response = PMA_Response::getInstance();
$response->addHTML(
    PMA_getImportDisplay(
        'database', $db, $table, $max_upload_size
    )
);
