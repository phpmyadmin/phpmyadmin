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

/* For chart exporting */
if (isset($_REQUEST['filename']) && isset($_REQUEST['image'])) {
    $allowed = array(
        'image/png'     => 'png',
        'image/svg+xml' => 'svg',
    );

    /* Check whether MIME type is allowed */
    if (! isset($allowed[$_REQUEST['type']])) {
        PMA_fatalError(__('Invalid export type'));
    }

    /*
     * Check file name to match mime type and not contain new lines
     * to prevent response splitting.
     */
    $extension = $allowed[$_REQUEST['type']];
    $valid_match = '/^[^\n\r]*\.' . $extension . '$/';
    if (! preg_match($valid_match, $_REQUEST['filename'])) {
        if (! preg_match('/^[^\n\r]*$/', $_REQUEST['filename'])) {
            /* Filename is unsafe, discard it */
            $filename = 'download.' . $extension;
        } else {
            /* Add extension */
            $filename = $_REQUEST['filename'] . '.' . $extension;
        }
    } else {
        /* Filename from request should be safe here */
        $filename = $_REQUEST['filename'];
    }

    /* Decode data */
    if ($extension != 'svg') {
        $data = mb_substr(
            $_REQUEST['image'],
            mb_strpos($_REQUEST['image'], ',') + 1
        );
        $data = base64_decode($data);
    } else {
        $data = $_REQUEST['image'];
    }

    /* Send download header */
    PMA_downloadHeader(
        $filename,
        $_REQUEST['type'],
        mb_strlen($data)
    );

    /* Send data */
    echo $data;

} else if (isset($_REQUEST['monitorconfig'])) {
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
