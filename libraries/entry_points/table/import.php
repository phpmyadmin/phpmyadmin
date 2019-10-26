<?php
/**
 * Table import
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Display\Import;
use PhpMyAdmin\Response;
use PhpMyAdmin\Url;

if (! defined('PHPMYADMIN')) {
    exit;
}

global $db, $max_upload_size, $table, $url_query, $url_params;

PageSettings::showGroup('Import');

$response = Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('import.js');

$import = new Import();

/**
 * Gets tables information and displays top links
 */
require_once ROOT_PATH . 'libraries/tbl_common.inc.php';

$url_params['goto'] = Url::getFromRoute('/table/import');
$url_params['back'] = Url::getFromRoute('/table/import');
$url_query .= Url::getCommon($url_params, '&');

$response->addHTML(
    $import::get(
        'table',
        $db,
        $table,
        $max_upload_size
    )
);
