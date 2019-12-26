<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Schema export handler
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Export;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Util;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

/**
 * get all variables needed for exporting relational schema
 * in $cfgRelation
 */
/** @var Relation $relation */
$relation = $containerBuilder->get('relation');
$cfgRelation = $relation->getRelationsParam();

if (! isset($_POST['export_type'])) {
    Util::checkParameters(['export_type']);
}

/**
 * Include the appropriate Schema Class depending on $export_type
 * default is PDF
 */
/** @var Export $export */
$export = $containerBuilder->get('export');
$export->processExportSchema($_POST['export_type']);
