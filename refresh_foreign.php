<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays form for editing and inserting new table rows
 *
 * @package PhpMyAdmin
 */

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\InsertEdit;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;


if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

global $cfg, $db, $table, $text_dir;

require_once ROOT_PATH . 'libraries/common.inc.php';

$container = Container::getDefaultContainer();
$container->set(Response::class, Response::getInstance());

$response = $container->get(Response::class);

$dbi = $container->get(DatabaseInterface::class);

$insertEdit = new InsertEdit($dbi);

$relation = new Relation($GLOBALS['dbi']);

$foreignData = $relation->getForeigners(
    $_POST['db'],
    $_POST['table']
)['foreign_keys_data'];

echo "Hola";
//$response->addHTML('<br/>');
//echo $foreignData[0]['ref_table_name'];
/*
$response->addHTML($relation->foreignDropdown(
    $foreignData['disp_row'],
    $foreignData['foreign_field'],
    $foreignData['foreign_display'],
    "foreing",
    $GLOBALS['cfg']['ForeignKeyMaxLimit']
));*/
