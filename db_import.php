<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Database import page
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Display\Import;
use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

global $db, $table;

require_once ROOT_PATH . 'libraries/common.inc.php';

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
) = PhpMyAdmin\Util::getDbInfo($db, isset($sub_part) ? $sub_part : '');

$response = Response::getInstance();
$response->addHTML(
    $import->get(
        'database',
        $db,
        $table,
        $max_upload_size
    )
);
