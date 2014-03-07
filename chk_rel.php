<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays status of phpMyAdmin configuration storage
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';
$response = PMA_Response::getInstance();
$response->addHTML(
    PMA_getRelationsParamDiagnostic(PMA_getRelationsParam())
);

?>
