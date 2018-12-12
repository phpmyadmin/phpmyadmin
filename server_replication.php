<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Server replications
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Message;
use PhpMyAdmin\ReplicationGui;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;

/**
 * include files
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/server_common.inc.php';
require_once 'libraries/replication.inc.php';

/**
 * Does the common work
 */
$response = Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('server_privileges.js');
$scripts->addFile('replication.js');
$scripts->addFile('vendor/zxcvbn.js');

$template = new Template();

/**
 * Checks if the user is allowed to do what he tries to...
 */
if (! $GLOBALS['dbi']->isSuperuser()) {
    $html = $template->render('server/sub_page_header', [
        'type' => 'replication',
    ]);
    $html .= Message::error(__('No Privileges'))->getDisplay();
    $response->addHTML($html);
    exit;
}

// change $GLOBALS['url_params'] with $_POST['url_params']
// only if it is an array
if (isset($_POST['url_params']) && is_array($_POST['url_params'])) {
    $GLOBALS['url_params'] = $_POST['url_params'];
}

$replicationGui = new ReplicationGui();

/**
 * Handling control requests
 */
$replicationGui->handleControlRequest();

/**
 * start output
 */
$response->addHTML('<div id="replication">');
$response->addHTML($template->render('server/sub_page_header', [
    'type' => 'replication',
]));

// Display error messages
$response->addHTML($replicationGui->getHtmlForErrorMessage());

if ($GLOBALS['replication_info']['master']['status']) {
    $response->addHTML($replicationGui->getHtmlForMasterReplication());
} elseif (! isset($_POST['mr_configure'])
    && ! isset($_POST['repl_clear_scr'])
) {
    $response->addHTML($replicationGui->getHtmlForNotServerReplication());
}

if (isset($_POST['mr_configure'])) {
    // Render the 'Master configuration' section
    $response->addHTML($replicationGui->getHtmlForMasterConfiguration());
    exit;
}

$response->addHTML('</div>');

if (! isset($_POST['repl_clear_scr'])) {
    // Render the 'Slave configuration' section
    $response->addHTML(
        $replicationGui->getHtmlForSlaveConfiguration(
            $GLOBALS['replication_info']['slave']['status'],
            $server_slave_replication
        )
    );
}
if (isset($_POST['sl_configure'])) {
    $response->addHTML($replicationGui->getHtmlForReplicationChangeMaster("slave_changemaster"));
}
