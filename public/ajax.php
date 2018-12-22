<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Generic AJAX endpoint for getting information about database
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Response;
use PhpMyAdmin\Util;
use PhpMyAdmin\Core;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

$_GET['ajax_request'] = 'true';

require_once ROOT_PATH . 'libraries/common.inc.php';

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
        Util::checkParameters(['db'], true);
        $response->addJSON('tables', $GLOBALS['dbi']->getTables($_POST['db']));
        break;
    case 'list-columns':
        Util::checkParameters(['db', 'table'], true);
        $response->addJSON('columns', $GLOBALS['dbi']->getColumnNames($_POST['db'], $_POST['table']));
        break;
    case 'config-get':
        Util::checkParameters(['key'], true);
        $response->addJSON('value', $GLOBALS['PMA_Config']->get($_POST['key']));
        break;
    case 'config-set':
        Util::checkParameters(['key', 'value'], true);
        $result = $GLOBALS['PMA_Config']->setUserValue(null, $_POST['key'], json_decode($_POST['value']));
        if ($result !== true) {
            $response = Response::getInstance();
            $response->setRequestStatus(false);
            $response->addJSON('message', $result);
        }
        break;
    default:
        Core::fatalError(__('Bad type!'));
}
