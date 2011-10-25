<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * displays and handles the form where the user can change his password
 * linked from main.php
 *
 * @package PhpMyAdmin
 */

/**
 * no need for variables importing
 * @ignore
 */
if (! defined('PMA_NO_VARIABLES_IMPORT')) {
    define('PMA_NO_VARIABLES_IMPORT', true);
}

/**
 * Gets some core libraries
 */
require_once './libraries/common.inc.php';

$GLOBALS['js_include'][] = 'server_privileges.js';

/**
 * Displays an error message and exits if the user isn't allowed to use this
 * script
 */
if (!$cfg['ShowChgPassword']) {
    $cfg['ShowChgPassword'] = PMA_DBI_select_db('mysql');
}
if ($cfg['Server']['auth_type'] == 'config' || !$cfg['ShowChgPassword']) {
    include_once './libraries/header.inc.php';
    PMA_Message::error(__('You don\'t have sufficient privileges to be here right now!'))->display();
    include './libraries/footer.inc.php';
} // end if


/**
 * If the "change password" form has been submitted, checks for valid values
 * and submit the query or logout
 */
if (isset($_REQUEST['nopass'])) {
    // similar logic in server_privileges.php
    $_error = false;

    if ($_REQUEST['nopass'] == '1') {
        $password = '';
    } elseif (empty($_REQUEST['pma_pw']) || empty($_REQUEST['pma_pw2'])) {
        $message = PMA_Message::error(__('The password is empty!'));
        $_error = true;
    } elseif ($_REQUEST['pma_pw'] != $_REQUEST['pma_pw2']) {
        $message = PMA_Message::error(__('The passwords aren\'t the same!'));
        $_error = true;
    } else {
        $password = $_REQUEST['pma_pw'];
    }

    if ($GLOBALS['is_ajax_request'] == true && $_error == true) {
        /**
         * If in an Ajax request, we don't need to show the rest of the page
         */
        PMA_ajaxResponse($message, false);
    }

    if (! $_error) {

        // Defines the url to return to in case of error in the sql statement
        $_url_params = array();

        $err_url          = 'user_password.php' . PMA_generate_common_url($_url_params);
        if (PMA_isValid($_REQUEST['pw_hash'], 'identical', 'old')) {
            $hashing_function = 'OLD_PASSWORD';
        } else {
            $hashing_function = 'PASSWORD';
        }

        $sql_query        = 'SET password = ' . (($password == '') ? '\'\'' : $hashing_function . '(\'***\')');
        $local_query      = 'SET password = ' . (($password == '') ? '\'\'' : $hashing_function . '(\'' . PMA_sqlAddSlashes($password) . '\')');
        $result           = @PMA_DBI_try_query($local_query)
            or PMA_mysqlDie(PMA_DBI_getError(), $sql_query, false, $err_url);

        // Changes password cookie if required
        // Duration = till the browser is closed for password (we don't want this to be saved)
        if ($cfg['Server']['auth_type'] == 'cookie') {
            $GLOBALS['PMA_Config']->setCookie('pmaPass-' . $server,
                PMA_blowfish_encrypt($password, $GLOBALS['cfg']['blowfish_secret']));
        } // end if

        // For http auth. mode, the "back" link will also enforce new
        // authentication
        if ($cfg['Server']['auth_type'] == 'http') {
            $_url_params['old_usr'] = 'relog';
        }

        $message = PMA_Message::success(__('The profile has been updated.'));

        if ($GLOBALS['is_ajax_request'] == true) {
            $extra_data['sql_query'] = PMA_showMessage($message, $sql_query, 'success');
            PMA_ajaxResponse($message, true, $extra_data);
        }

        // Displays the page
        include_once './libraries/header.inc.php';
        echo '<h1>' . __('Change password') . '</h1>' . "\n\n";
        PMA_showMessage($message, $sql_query, 'success');
        ?>
        <a href="index.php<?php echo PMA_generate_common_url($_url_params); ?>" target="_parent">
            <strong><?php echo __('Back'); ?></strong></a>
        <?php
        include './libraries/footer.inc.php';
    } // end if
} // end if


/**
 * If the "change password" form hasn't been submitted or the values submitted
 * aren't valid -> displays the form
 */
// Loads the headers
require_once './libraries/header.inc.php';

echo '<h1>' . __('Change password') . '</h1>' . "\n\n";

// Displays an error message if required
if (isset($message)) {
    $message->display();
}

require_once './libraries/display_change_password.lib.php';

/**
 * Displays the footer
 */
require './libraries/footer.inc.php';
?>
