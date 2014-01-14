<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * display the binary logs and the content of the selected
 *
 * @package PhpMyAdmin
 */

/**
 * requirements
 */
require_once 'libraries/common.inc.php';

/**
 * Does the common work
 */
require_once 'libraries/server_common.inc.php';

require_once 'libraries/server_bin_log.lib.php';

/**
 * array binary log files
 */
$binary_logs = PMA_DRIZZLE
    ? null
    : $GLOBALS['dbi']->fetchResult(
        'SHOW MASTER LOGS',
        'Log_name',
        null,
        null,
        PMA_DatabaseInterface::QUERY_STORE
    );

if (! isset($_REQUEST['log'])
    || ! array_key_exists($_REQUEST['log'], $binary_logs)
) {
    $_REQUEST['log'] = '';
} else {
    $url_params['log'] = $_REQUEST['log'];
}

if (!empty($_REQUEST['dontlimitchars'])) {
    $url_params['dontlimitchars'] = 1;
}

$response = PMA_Response::getInstance();

$response->addHTML(PMA_getHtmlForSubPageHeader('binlog'));
$response->addHTML(PMA_getLogSelector($binary_logs, $url_params));
$response->addHTML(PMA_getLogInfo($url_params));

exit;

?>
