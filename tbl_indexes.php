<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays index edit/creation form and handles it
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Table\IndexesController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Index;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

/** @var DatabaseInterface $dbi */
$dbi = $containerBuilder->get('dbi');

/** @var string $db */
$db = $containerBuilder->getParameter('db');

/** @var string $table */
$table = $containerBuilder->getParameter('table');

if (! isset($_POST['create_edit_table'])) {
    include_once ROOT_PATH . 'libraries/tbl_common.inc.php';
}
if (isset($_POST['index'])) {
    if (is_array($_POST['index'])) {
        // coming already from form
        $index = new Index($_POST['index']);
    } else {
        $index = $dbi->getTable($db, $table)->getIndex($_POST['index']);
    }
} else {
    $index = new Index();
}

/* Define dependencies for the concerned controller */
$containerBuilder->setParameter('index', $index);

/** @var IndexesController $controller */
$controller = $containerBuilder->get(IndexesController::class);
$controller->indexAction();
