<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Table SQL executor
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
 * Runs common work
 */
$response = Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('makegrid.js');
$scripts->addFile('vendor/jquery/jquery.uitablefilter.js');
$scripts->addFile('sql.js');

require ROOT_PATH . 'libraries/tbl_common.inc.php';
$url_query .= '&amp;goto=tbl_sql.php&amp;back=tbl_sql.php';

$err_url   = 'tbl_sql.php' . $err_url;
// After a syntax error, we return to this script
// with the typed query in the textarea.
$goto = 'tbl_sql.php';
$back = 'tbl_sql.php';

$sqlQueryForm = new SqlQueryForm();

/**
 * Query box, bookmark, insert data from textfile
 */
$response->addHTML(
    $sqlQueryForm->getHtml(
        true,
        false,
        isset($_POST['delimiter'])
        ? htmlspecialchars($_POST['delimiter'])
        : ';'
    )
);
