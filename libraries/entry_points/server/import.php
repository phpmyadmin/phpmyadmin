<?php
/**
 * Server import page
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

global $db, $max_upload_size, $table;

PageSettings::showGroup('Import');

$response = Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('import.js');

/**
 * Does the common work
 */
require ROOT_PATH . 'libraries/server_common.inc.php';

$import = new Import();

$response = Response::getInstance();
$response->addHTML(
    $import::get(
        'server',
        $db,
        $table,
        $max_upload_size
    )
);
