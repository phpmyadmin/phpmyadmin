<?php
/**
 * Database import page
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Display\Import;
use PhpMyAdmin\Response;

if (! defined('PHPMYADMIN')) {
    exit;
}

global $db, $max_upload_size, $table, $tables, $num_tables, $total_num_tables, $is_show_stats;
global $db_is_system_schema, $tooltip_truename, $tooltip_aliasname, $pos, $sub_part;

PageSettings::showGroup('Import');

$response = Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('import.js');

$import = new Import();

/**
 * Gets tables information and displays top links
 */
require ROOT_PATH . 'libraries/db_common.inc.php';

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
) = PhpMyAdmin\Util::getDbInfo($db, $sub_part ?? '');

$response = Response::getInstance();
$response->addHTML(
    $import::get(
        'database',
        $db,
        $table,
        $max_upload_size
    )
);
