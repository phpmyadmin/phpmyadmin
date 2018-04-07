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

$_GET['ajax_request'] = 'true';

require_once 'libraries/common.inc.php';

$response = Response::getInstance();
$response->setAJAX(true);

if (empty($_POST['type'])) {
    Core::fatalError(__('Bad type!'));
}

switch ($_POST['type']) {
    case 'list-databases':
        $response->addJSON('databases', $GLOBALS['dblist']->databases);
        break;
    case 'list-tables':
        Util::checkParameters(array('db'), true);
        $response->addJSON('tables', $GLOBALS['dbi']->getTables($_REQUEST['db']));
        break;
    case 'list-columns':
        Util::checkParameters(array('db', 'table'), true);
        $response->addJSON('columns', $GLOBALS['dbi']->getColumnNames($_REQUEST['db'], $_REQUEST['table']));
        break;
    case 'config-get':
        Util::checkParameters(array('key'), true);
        $response->addJSON('value', $GLOBALS['PMA_Config']->get($_REQUEST['key']));
        break;
    case 'config-set':
        Util::checkParameters(array('key', 'value'), true);
        $result = $GLOBALS['PMA_Config']->setUserValue(null, $_REQUEST['key'], json_decode($_REQUEST['value']));
        if ($result !== true) {
            $response = Response::getInstance();
            $response->setRequestStatus(false);
            $response->addJSON('message', $result);
        }
        break;
    default:
        Core::fatalError(__('Bad type!'));
}
