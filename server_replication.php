<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Server replications
 *
 * @package PhpMyAdmin
 */

/**
 * include files
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/server_common.inc.php';

require_once 'libraries/replication.inc.php';
require_once 'libraries/replication_gui.lib.php';

/**
 * Does the common work
 */
$response = PMA\libraries\Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('server_privileges.js');
$scripts->addFile('replication.js');

/**
 * Checks if the user is allowed to do what he tries to...
 */
if (! $is_superuser) {
    $html  = PMA_getHtmlForSubPageHeader('replication');
    $html .= PMA\libraries\Message::error(__('No Privileges'))->getDisplay();
    $response->addHTML($html);
    exit;
}

//change $GLOBALS['url_params'] with $_REQUEST['url_params']
if (isset($_REQUEST['url_params'])) {
    $GLOBALS['url_params'] = $_REQUEST['url_params'];
}
/**
 * Handling control requests
 */
PMA_handleControlRequest();

/**
 * start output
 */
$response->addHTML('<div id="replication">');
$response->addHTML(PMA_getHtmlForSubPageHeader('replication'));

// Display error messages
$response->addHTML(PMA_getHtmlForErrorMessage());

if ($GLOBALS['replication_info']['master']['status']) {
    $response->addHTML(PMA_getHtmlForMasterReplication());
} elseif (! isset($_REQUEST['mr_configure'])
    && ! isset($_REQUEST['repl_clear_scr'])
) {
    $response->addHTML(PMA_getHtmlForNotServerReplication());
}

if (isset($_REQUEST['mr_configure'])) {
    // Render the 'Master configuration' section
    $response->addHTML(PMA_getHtmlForMasterConfiguration());
    exit;
}

$response->addHTML('</div>');

if (! isset($_REQUEST['repl_clear_scr'])) {
    // Render the 'Slave configuration' section
    $response->addHTML(
        PMA_getHtmlForSlaveConfiguration(
            $GLOBALS['replication_info']['slave']['status'],
            $server_slave_replication
        )
    );
}
if (isset($_REQUEST['sl_configure'])) {
    $response->addHTML(PMA_getHtmlForReplicationChangeMaster("slave_changemaster"));
}
