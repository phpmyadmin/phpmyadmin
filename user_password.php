<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * displays and handles the form where the user can change his password
 * linked from index.php
 *
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once './libraries/common.inc.php';

/**
 * Libraries needed for some functions
 */
require_once './libraries/server_privileges.lib.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('server_privileges.js');

/**
 * Displays an error message and exits if the user isn't allowed to use this
 * script
 */
if (! $GLOBALS['cfg']['ShowChgPassword']) {
    $GLOBALS['cfg']['ShowChgPassword'] = $GLOBALS['dbi']->selectDb('mysql');
}
if ($cfg['Server']['auth_type'] == 'config' || ! $cfg['ShowChgPassword']) {
    PMA_Message::error(
        __('You don\'t have sufficient privileges to be here right now!')
    )->display();
    exit;
} // end if

/**
 * If the "change password" form has been submitted, checks for valid values
 * and submit the query or logout
 */
if (isset($_REQUEST['nopass'])) {
    if ($_REQUEST['nopass'] == '1') {
        $password = '';
    } else {
        $password = $_REQUEST['pma_pw'];
    }
    $change_password_message = PMA_setChangePasswordMsg();
    $msg = $change_password_message['msg'];
    if (! $change_password_message['error']) {
        PMA_changePassword($password, $msg, $change_password_message);
    } else {
        PMA_getChangePassMessage($change_password_message);
    }
}

/**
 * If the "change password" form hasn't been submitted or the values submitted
 * aren't valid -> displays the form
 */

// Displays an error message if required
if (isset($msg)) {
    $msg->display();
    unset($msg);
}

require_once './libraries/display_change_password.lib.php';

echo PMA_getHtmlForChangePassword('change_pw', $username, $hostname);
exit;

/**
 * Send the message as an ajax request
 *
 * @param array  $change_password_message Message to display
 * @param string $sql_query               SQL query executed
 *
 * @return void
 */
function PMA_getChangePassMessage($change_password_message, $sql_query = '')
{
    if ($GLOBALS['is_ajax_request'] == true) {
        /**
         * If in an Ajax request, we don't need to show the rest of the page
         */
        $response = PMA_Response::getInstance();
        if ($change_password_message['error']) {
            $response->addJSON('message', $change_password_message['msg']);
            $response->isSuccess(false);
        } else {
            $sql_query = PMA_Util::getMessage(
                $change_password_message['msg'],
                $sql_query,
                'success'
            );
            $response->addJSON('message', $sql_query);
        }
        exit;
    }
}

/**
 * Generate the message
 *
 * @return array   error value and message
 */
function PMA_setChangePasswordMsg()
{
    $error = false;
    $message = PMA_Message::success(__('The profile has been updated.'));

    if (($_REQUEST['nopass'] != '1')) {
        if (empty($_REQUEST['pma_pw']) || empty($_REQUEST['pma_pw2'])) {
            $message = PMA_Message::error(__('The password is empty!'));
            $error = true;
        } elseif ($_REQUEST['pma_pw'] != $_REQUEST['pma_pw2']) {
            $message = PMA_Message::error(__('The passwords aren\'t the same!'));
            $error = true;
        }
    }
    return array('error' => $error, 'msg' => $message);
}

/**
 * Change the password
 *
 * @param string $password                New password
 * @param string $message                 Message
 * @param array  $change_password_message Message to show
 *
 * @return void
 */
function PMA_changePassword($password, $message, $change_password_message)
{
    global $auth_plugin;

    $hashing_function = PMA_changePassHashingFunction();

    $orig_auth_plugin = null;

    $row = $GLOBALS['dbi']->fetchSingleRow('SELECT CURRENT_USER() as user');
    $curr_user = $row['user'];
    list($username, $hostname) = explode('@', $curr_user);

    $serverType = PMA_Util::getServerType();

    if (isset($_REQUEST['authentication_plugin'])
        && ! empty($_REQUEST['authentication_plugin'])
    ) {
        $orig_auth_plugin = $_REQUEST['authentication_plugin'];
    } else {
        $orig_auth_plugin = PMA_getCurrentAuthenticationPlugin(
            'change', $username, $hostname
        );
    }

    $sql_query = 'SET password = '
        . (($password == '') ? '\'\'' : $hashing_function . '(\'***\')');

    if ($serverType == 'MySQL'
        && PMA_MYSQL_INT_VERSION >= 50706
    ) {
        $sql_query = 'ALTER USER \'' . $username . '\'@\'' . $hostname
            . '\' IDENTIFIED WITH ' . $orig_auth_plugin . ' BY '
            . (($password == '') ? '\'\'' : '\'***\'');
    } else if (($serverType == 'MySQL'
        && PMA_MYSQL_INT_VERSION >= 50507)
        || ($serverType == 'MariaDB'
        && PMA_MYSQL_INT_VERSION >= 50200)
    ) {
        // For MySQL versions 5.5.7+ and MariaDB versions 5.2+,
        // explicitly set value of `old_passwords` so that
        // it does not give an error while using
        // the PASSWORD() function
        if ($orig_auth_plugin == 'sha256_password') {
            $value = 2;
        } else {
            $value = 0;
        }
        $GLOBALS['dbi']->tryQuery('SET `old_passwords` = ' . $value . ';');
    }

    PMA_changePassUrlParamsAndSubmitQuery(
        $username, $hostname, $password,
        $sql_query, $hashing_function, $orig_auth_plugin
    );

    $auth_plugin->handlePasswordChange($password);
    PMA_getChangePassMessage($change_password_message, $sql_query);
    PMA_changePassDisplayPage($message, $sql_query);
}

/**
 * Generate the hashing function
 *
 * @return string  $hashing_function
 */
function PMA_changePassHashingFunction()
{
    if (PMA_isValid(
        $_REQUEST['authentication_plugin'], 'identical', 'mysql_old_password'
    )) {
        $hashing_function = 'OLD_PASSWORD';
    } else {
        $hashing_function = 'PASSWORD';
    }
    return $hashing_function;
}

/**
 * Changes password for a user
 *
 * @param string $username              Username
 * @param string $hostname              Hostname
 * @param string $password              Password
 * @param string $sql_query             SQL query
 * @param string $hashing_function      Hashing function
 * @param string $orig_auth_plugin      Original Authentication Plugin
 *
 * @return void
 */
function PMA_changePassUrlParamsAndSubmitQuery(
    $username, $hostname, $password, $sql_query, $hashing_function, $orig_auth_plugin
) {
    $err_url = 'user_password.php' . PMA_URL_getCommon();
    $serverType = PMA_Util::getServerType();

    if ($serverType == 'MySQL' && PMA_MYSQL_INT_VERSION >= 50706) {
        $local_query = 'ALTER USER \'' . $username . '\'@\'' . $hostname . '\''
            . ' IDENTIFIED with ' . $orig_auth_plugin . ' BY '
            . (($password == '')
            ? '\'\''
            : '\'' . PMA_Util::sqlAddSlashes($password) . '\'');
    } else if ($serverType == 'MariaDB'
        && PMA_MYSQL_INT_VERSION >= 50200
        && PMA_MYSQL_INT_VERSION < 100100
    ) {
        if ($orig_auth_plugin == 'mysql_native_password') {
            // Set the hashing method used by PASSWORD()
            // to be 'mysql_native_password' type
            $GLOBALS['dbi']->tryQuery('SET old_passwords = 0;');
        } else if ($orig_auth_plugin == 'sha256_password') {
            // Set the hashing method used by PASSWORD()
            // to be 'sha256_password' type
            $GLOBALS['dbi']->tryQuery('SET `old_passwords` = 2;');
        }

        $hashedPassword = PMA_getHashedPassword($_POST['pma_pw']);

        $local_query = "UPDATE `mysql`.`user` SET"
            . " `authentication_string` = '" . $hashedPassword
            . "', `Password` = '', "
            . " `plugin` = '" . $orig_auth_plugin . "'"
            . " WHERE `User` = '" . $username . "' AND Host = '"
            . $hostname . "';";

        $GLOBALS['dbi']->tryQuery("FLUSH PRIVILEGES;");
    } else {
        $local_query = 'SET password = ' . (($password == '')
            ? '\'\''
            : $hashing_function . '(\'' . PMA_Util::sqlAddSlashes($password)
                . '\')');
    }
    if (! @$GLOBALS['dbi']->tryQuery($local_query)) {
        PMA_Util::mysqlDie($GLOBALS['dbi']->getError(), $sql_query, false, $err_url);
    }
}

/**
 * Display the page
 *
 * @param string $message   Message
 * @param string $sql_query SQL query
 *
 * @return void
 */
function PMA_changePassDisplayPage($message, $sql_query)
{
    echo '<h1>' . __('Change password') . '</h1>' . "\n\n";
    echo PMA_Util::getMessage(
        $message, $sql_query, 'success'
    );
    echo '<a href="index.php' . PMA_URL_getCommon()
        . ' target="_parent">' . "\n"
        . '<strong>' . __('Back') . '</strong></a>';
    exit;
}
