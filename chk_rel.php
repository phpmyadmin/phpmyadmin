<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays status of phpMyAdmin configuration storage
 *
 * @package PhpMyAdmin
 */
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;

require_once 'libraries/common.inc.php';

// If request for creating the pmadb
if (isset($_REQUEST['create_pmadb'])) {
    if (Relation::createPmaDatabase()) {
        Relation::fixPmaTables('phpmyadmin');
    }
}

// If request for creating all PMA tables.
if (isset($_REQUEST['fixall_pmadb'])) {
    Relation::fixPmaTables($GLOBALS['db']);
}

$cfgRelation = Relation::getRelationsParam();
// If request for creating missing PMA tables.
if (isset($_REQUEST['fix_pmadb'])) {
    Relation::fixPmaTables($cfgRelation['db']);
}

$response = Response::getInstance();
$response->addHTML(
    Relation::getRelationsParamDiagnostic($cfgRelation)
);
