<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Server SQL executor
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Response;
use PhpMyAdmin\SqlQueryForm;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

/**
 *
 */
require_once ROOT_PATH . 'libraries/common.inc.php';

PageSettings::showGroup('Sql');

/**
 * Does the common work
 */
$response = Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('makegrid.js');
$scripts->addFile('vendor/jquery/jquery.uitablefilter.js');
$scripts->addFile('sql.js');

require_once ROOT_PATH . 'libraries/server_common.inc.php';

$sqlQueryForm = new SqlQueryForm();

/**
 * Query box, bookmark, insert data from textfile
 */
$response->addHTML($sqlQueryForm->getHtml());
