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

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('server_privileges.js');

/**
 * Displays an error message and exits if the user isn't allowed to use this
 * script
 */
if (! $cfg['ShowChgPassword']) {
    $cfg['ShowChgPassword'] = PMA_DBI_select_db('mysql');
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
echo PMA_getHtmlForChangePassword($username, $hostname);
exit;

/**
 * Send the message as an ajax request
 *
 * @param array   $change_password_message
 * @param string  $sql_query
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
        } elseif (strlen($_REQUEST['pma_pw']) > 256) {
            $message = PMA_Message::error(__('Password is too long!'));
            $error = true;
        }
    }
    return array('error' => $error, 'msg' => $message);
}

/**
 * Change the password
 *
 * @param string  $password
 * @param string  $message
 * @param array   $change_password_message
 *
 * @return void
 */
function PMA_changePassword($password, $message, $change_password_message)
{
    // Defines the url to return to in case of error in the sql statement
    $_url_params = array();
    $hashing_function = PMA_changePassHashingFunction();
    $sql_query = 'SET password = '
        . (($password == '') ? '\'\'' : $hashing_function . '(\'***\')');
    PMA_ChangePassUrlParamsAndSubmitQuery(
        $password, $_url_params, $sql_query, $hashing_function
    );

    $new_url_params = PMA_changePassAuthType($_url_params, $password);
    PMA_getChangePassMessage($change_password_message, $sql_query);
    PMA_changePassDisplayPage($message, $sql_query, $new_url_params);
}

/**
 * Generate the hashing function
 *
 * @return string  $hashing_function
 */
function PMA_changePassHashingFunction()
{
    if (PMA_isValid($_REQUEST['pw_hash'], 'identical', 'old')) {
        $hashing_function = 'OLD_PASSWORD';
    } else {
        $hashing_function = 'PASSWORD';
    }
    return $hashing_function;
}

/**
 * Generate the error url and submit the query
 *
 * @param string  $password
 * @param array   $_url_params
 * @param string  $sql_query
 * @param string  $hashing_function
 *
 * @return void
 */
function PMA_ChangePassUrlParamsAndSubmitQuery(
    $password, $_url_params, $sql_query, $hashing_function
) {
    $err_url = 'user_password.php' . PMA_generate_common_url($_url_params);
    $local_query = 'SET password = ' . (($password == '')
        ? '\'\''
        : $hashing_function . '(\'' . PMA_Util::sqlAddSlashes($password) . '\')');
    if (! @PMA_DBI_try_query($local_query)) {
        PMA_Util::mysqlDie(PMA_DBI_getError(), $sql_query, false, $err_url);
    }
}

/**
 * Change password authentication type
 *
 * @param array   $_url_params
 * @param string  $password
 *
 * @return array   $_url_params
 */
function PMA_changePassAuthType($_url_params, $password)
{
    /**
     * Changes password cookie if required
     * Duration = till the browser is closed for password
     * (we don't want this to be saved)
     */

    //    include_once "libraries/plugins/auth/AuthenticationCookie.class.php";
    //    $auth_plugin = new AuthenticationCookie();
    // the $auth_plugin is already defined in common.inc.php when this is used
    global $auth_plugin;

    if ($GLOBALS['cfg']['Server']['auth_type'] == 'cookie') {
        $auth_plugin->storePasswordCookie($password);
    }
    /**
     * For http auth. mode, the "back" link will also enforce new
     * authentication
     */
    if ($GLOBALS['cfg']['Server']['auth_type'] == 'http') {
        $_url_params['old_usr'] = 'relog';
    }
    return $_url_params;
}

/**
 * Display the page
 *
 * @param string  $message
 * @param string  $sql_query
 * @param array   $_url_params
 *
 * @return void
 */
function PMA_changePassDisplayPage($message, $sql_query, $_url_params)
{
    echo '<h1>' . __('Change password') . '</h1>' . "\n\n";
    echo PMA_Util::getMessage(
        $message, $sql_query, 'success'
    );
    echo '<a href="index.php'.PMA_generate_common_url($_url_params)
        .' target="_parent">'. "\n"
        .'<strong>'.__('Back').'</strong></a>';
    exit;
}
?>
