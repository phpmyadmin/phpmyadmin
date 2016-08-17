<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * "Echo" service to allow force downloading of exported charts (png or svg)
 * and server status monitor settings
 *
 * @package PhpMyAdmin
 */

define('PMA_MINIMUM_COMMON', true);
require_once 'libraries/common.inc.php';

if (isset($_REQUEST['monitorconfig'])) {
    /* For monitor chart config export */
    PMA_downloadHeader('monitor.cfg', 'application/json; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');

    echo urldecode($_REQUEST['monitorconfig']);

} else if (isset($_REQUEST['import'])) {
    /* For monitor chart config import */
    header('Content-Type: application/json; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');

    if (!file_exists($_FILES['file']['tmp_name'])) {
        exit();
    }
    echo file_get_contents($_FILES['file']['tmp_name']);
}
