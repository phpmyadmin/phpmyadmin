<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 */

/**
 * Gets some core libraries
 */
require_once './libraries/common.inc.php';

/**
 * Displays an error message and exits if the user isn't allowed to use this
 * script
 */
if (!$cfg['ShowChgPassword']) {
    $cfg['ShowChgPassword'] = PMA_DBI_select_db('mysql');
}
if ($cfg['Server']['auth_type'] == 'config' || !$cfg['ShowChgPassword']) {
    require_once './libraries/header.inc.php';
    echo '<p><b>' . $strError . '</b></p>' . "\n"
       . '<p>&nbsp;&nbsp;&nbsp;&nbsp;' .  $strNoRights . '</p>' . "\n";
    require_once './libraries/footer.inc.php';
} // end if


/**
 * If the "change password" form has been submitted, checks for valid values
 * and submit the query or logout
 */
if (isset($nopass)) {
    // similar logic in server_privileges.php
    $error_msg = '';

    if ($nopass == 0 && isset($pma_pw) && isset($pma_pw2)) {
        if ($pma_pw != $pma_pw2) {
            $error_msg = $strPasswordNotSame;
        }
        if (empty($pma_pw) || empty($pma_pw2)) {
            $error_msg = $strPasswordEmpty;
        }
    } // end if

    // here $nopass could be == 1
    if (empty($error_msg)) {

        // Defines the url to return to in case of error in the sql statement
        $common_url_query = PMA_generate_common_url();

        $err_url          = 'user_password.php?' . $common_url_query;
    $hashing_function = (PMA_MYSQL_INT_VERSION >= 40102 && !empty($pw_hash) && $pw_hash == 'old' ? 'OLD_' : '')
                      . 'PASSWORD';

        $sql_query        = 'SET password = ' . (($pma_pw == '') ? '\'\'' : $hashing_function . '(\'' . preg_replace('@.@s', '*', $pma_pw) . '\')');
        $local_query      = 'SET password = ' . (($pma_pw == '') ? '\'\'' : $hashing_function . '(\'' . PMA_sqlAddslashes($pma_pw) . '\')');
        $result           = @PMA_DBI_try_query($local_query) or PMA_mysqlDie(PMA_DBI_getError(), $sql_query, FALSE, $err_url);

        // Changes password cookie if required
        // Duration = till the browser is closed for password (we don't want this to be saved)
        if ($cfg['Server']['auth_type'] == 'cookie') {

            PMA_setCookie('pmaPass-' . $server, PMA_blowfish_encrypt($pma_pw, $GLOBALS['cfg']['blowfish_secret']));

        } // end if
        // For http auth. mode, the "back" link will also enforce new
        // authentication
        $http_logout = ($cfg['Server']['auth_type'] == 'http')
                     ? '&amp;old_usr=relog'
                     : '';

        // Displays the page
        require_once './libraries/header.inc.php';
        echo '<h1>' . $strChangePassword . '</h1>' . "\n\n";
        $show_query = 'y';
        PMA_showMessage($strUpdateProfileMessage);
        ?>
        <a href="index.php?<?php echo $common_url_query . $http_logout; ?>" target="_parent">
            <b><?php echo $strBack; ?></b></a>
        <?php
        exit();
    } // end if
} // end if


/**
 * If the "change password" form hasn't been submitted or the values submitted
 * aren't valid -> displays the form
 */
// Loads the headers
$js_to_run = 'user_password.js';
require_once './libraries/header.inc.php';
echo '<h1>' . $strChangePassword . '</h1>' . "\n\n";

// Displays an error message if required
if (!empty($error_msg)) {
    echo '<p><b>' . $strError . ':&nbsp;' . $error_msg . '</b></p>' . "\n";
}

require_once './libraries/display_change_password.lib.php';

/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';
?>
