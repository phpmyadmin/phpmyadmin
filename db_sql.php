<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Database SQL executor
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Response;
use PhpMyAdmin\SqlQueryForm;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
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

require ROOT_PATH . 'libraries/db_common.inc.php';

$sqlQueryForm = new SqlQueryForm();

// After a syntax error, we return to this script
// with the typed query in the textarea.
$goto = 'db_sql.php';
$back = 'db_sql.php';

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
