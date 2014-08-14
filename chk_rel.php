<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays status of phpMyAdmin configuration storage
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';

// If request for fixing PMA tables.
if (isset($_REQUEST['fix_pmadb'])) {
    PMA_fixPMATables($GLOBALS['db']);
}

$response = PMA_Response::getInstance();
$response->addHTML(
    PMA_getRelationsParamDiagnostic(PMA_getRelationsParam())
);

?>
