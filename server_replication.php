<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
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
$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('server_privileges.js');
$scripts->addFile('replication.js');

/**
 * Checks if the user is allowed to do what he tries to...
 */
if (! $is_superuser) {
    $html  = PMA_getSubPageHeader('replication');
    $html .= PMA_Message::error(__('No Privileges'))->getDisplay();
    $response->addHTML($html);
    exit;
}

/**
 * Sets globals from $_REQUEST
 */
$request_params = array(
    'hostname',
    'mr_adduser',
    'mr_configure',
    'pma_pw',
    'port',
    'repl_clear_scr',
    'repl_data',
    'sl_configure',
    'slave_changemaster',
    'sr_skip_errors_count',
    'sr_slave_action',
    'sr_slave_control_parm',
    'sr_slave_server_control',
    'sr_slave_skip_error',
    'sr_take_action',
    'url_params',
    'username'
);

foreach ($request_params as $one_request_param) {
    if (isset($_REQUEST[$one_request_param])) {
        $GLOBALS[$one_request_param] = $_REQUEST[$one_request_param];
    }
}

/**
 * Handling control requests
 */
if (isset($GLOBALS['sr_take_action'])) {
    $refresh = false;
    if (isset($GLOBALS['slave_changemaster'])) {
        $_SESSION['replication']['m_username'] = $sr['username'] = PMA_Util::sqlAddSlashes($GLOBALS['username']);
        $_SESSION['replication']['m_password'] = $sr['pma_pw']   = PMA_Util::sqlAddSlashes($GLOBALS['pma_pw']);
        $_SESSION['replication']['m_hostname'] = $sr['hostname'] = PMA_Util::sqlAddSlashes($GLOBALS['hostname']);
        $_SESSION['replication']['m_port']     = $sr['port']     = PMA_Util::sqlAddSlashes($GLOBALS['port']);
        $_SESSION['replication']['m_correct']  = '';
        $_SESSION['replication']['sr_action_status'] = 'error';
        $_SESSION['replication']['sr_action_info'] = __('Unknown error');

        // Attempt to connect to the new master server
        $link_to_master = PMA_Replication_connectToMaster(
            $sr['username'], $sr['pma_pw'], $sr['hostname'], $sr['port']
        );

        if (! $link_to_master) {
            $_SESSION['replication']['sr_action_status'] = 'error';
            $_SESSION['replication']['sr_action_info'] = sprintf(
                __('Unable to connect to master %s.'),
                htmlspecialchars($sr['hostname'])
            );
        } else {
            // Read the current master position
            $position = PMA_Replication_Slave_binLogMaster($link_to_master);

            if (empty($position)) {
                $_SESSION['replication']['sr_action_status'] = 'error';
                $_SESSION['replication']['sr_action_info'] = 
                    __('Unable to read master log position. Possible privilege problem on master.');
            } else {
                $_SESSION['replication']['m_correct']  = true;

                if (! PMA_Replication_Slave_changeMaster(
                          $sr['username'], 
                          $sr['pma_pw'], 
                          $sr['hostname'], 
                          $sr['port'], 
                          $position, 
                          true, 
                          false)) {
                    $_SESSION['replication']['sr_action_status'] = 'error';
                    $_SESSION['replication']['sr_action_info'] = __('Unable to change master');
                } else {
                    $_SESSION['replication']['sr_action_status'] = 'success';
                    $_SESSION['replication']['sr_action_info'] = sprintf(
                        __('Master server changed successfully to %s'),
                        htmlspecialchars($sr['hostname'])
                    );
                }
            }
        }
    } elseif (isset($GLOBALS['sr_slave_server_control'])) {
        if ($GLOBALS['sr_slave_action'] == 'reset') {
            PMA_Replication_Slave_control("STOP");
            $GLOBALS['dbi']->tryQuery("RESET SLAVE;");
            PMA_Replication_Slave_control("START");
        } else {
            PMA_Replication_Slave_control(
                $GLOBALS['sr_slave_action'],
                $GLOBALS['sr_slave_control_parm']
            );
        }
        $refresh = true;

    } elseif (isset($GLOBALS['sr_slave_skip_error'])) {
        $count = 1;
        if (isset($GLOBALS['sr_skip_errors_count'])) {
            $count = $GLOBALS['sr_skip_errors_count'] * 1;
        }
        PMA_Replication_Slave_control("STOP");
        $GLOBALS['dbi']->tryQuery("SET GLOBAL SQL_SLAVE_SKIP_COUNTER = ".$count.";");
        PMA_Replication_Slave_control("START");

    }

    if ($refresh) {
        Header("Location: server_replication.php" . PMA_generate_common_url($GLOBALS['url_params']));
    }
    unset($refresh);
}

/**
 * start output
 */
$response->addHTML('<div id="replication">');
$response->addHTML(PMA_getSubPageHeader('replication'));

// Display error messages
$response->addHTML(PMA_getHtmlForErrorMessage());

if ($server_master_status) {
    $response->addHTML(PMA_getHtmlForMasterReplication());
} elseif (! isset($GLOBALS['mr_configure']) && ! isset($GLOBALS['repl_clear_scr'])) {
    $response->addHTML(PMA_getHtmlForNotServerReplication());
}

if (isset($GLOBALS['mr_configure'])) {
    // Render the 'Master configuration' section
    $response->addHTML(PMA_getHtmlForMasterConfiguration());
    exit;
}

$response->addHTML('</div>');

if (! isset($GLOBALS['repl_clear_scr'])) {
    // Render the 'Slave configuration' section
    $response->addHTML(PMA_getHtmlForSlaveConfiguration($server_slave_status, $server_slave_replication));
}
if (isset($GLOBALS['sl_configure'])) {
    $response->addHTML(PMA_GetHtmlForReplication_changemaster("slave_changemaster"));
}
?>
