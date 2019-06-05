<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * object the server export page
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Display\Export;
use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

global $db, $table;

require_once ROOT_PATH . 'libraries/common.inc.php';
require_once ROOT_PATH . 'libraries/server_common.inc.php';

PageSettings::showGroup('Export');

$response = Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('export.js');

$export_page_title = __('View dump (schema) of databases') . "\n";

$displayExport = new Export();

$select_item = isset($tmp_select) ? $tmp_select : '';
$multi_values = $displayExport->getHtmlForSelectOptions($select_item);

if (! isset($sql_query)) {
    $sql_query = '';
}
if (! isset($num_tables)) {
    $num_tables = 0;
}
if (! isset($unlim_num_rows)) {
    $unlim_num_rows = 0;
}
$response = Response::getInstance();
$response->addHTML(
    $displayExport->getDisplay(
        'server',
        $db,
        $table,
        $sql_query,
        $num_tables,
        $unlim_num_rows,
        $multi_values
    )
);
