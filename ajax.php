<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Generic AJAX endpoint for getting information about database
 *
 * @package PhpMyAdmin
 */

use PhpMyAdmin\Response;
use PhpMyAdmin\Util;
use PhpMyAdmin\Core;
require_once 'libraries/common.inc.php';

$response = Response::getInstance();

if (empty($_POST['type'])) {
    Core::fatalError(__('Bad type!'));
}

switch ($_POST['type']) {
    case 'list-databases':
        $response->addJSON('databases', $GLOBALS['dblist']->databases);
        break;
    case 'list-tables':
        Util::checkParameters(array('db'));
        $response->addJSON('tables', $GLOBALS['dbi']->getTables($_REQUEST['db']));
        break;
    case 'list-columns':
        Util::checkParameters(array('db', 'table'));
        $response->addJSON('columns', $GLOBALS['dbi']->getColumnNames($_REQUEST['db'], $_REQUEST['table']));
        break;

    default:
        Core::fatalError(__('Bad type!'));
}
