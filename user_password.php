<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Gets some core libraries
 */
require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');

/**
 * Displays an error message and exits if the user isn't allowed to use this
 * script
 */
if (!$cfg['ShowChgPassword']) {
    $cfg['ShowChgPassword'] = PMA_DBI_select_db('mysql');
}
if ($cfg['Server']['auth_type'] == 'config' || !$cfg['ShowChgPassword']) {
    require_once('./header.inc.php');
    echo '<p><b>' . $strError . '</b></p>' . "\n"
       . '<p>&nbsp;&nbsp;&nbsp;&nbsp;' .  $strNoRights . '</p>' . "\n";
    require_once('./footer.inc.php');
} // end if


/**
 * If the "change password" form has been submitted, checks for valid values
 * and submit the query or logout
 */
if (isset($nopass)) {
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

            setcookie('pma_cookie_password-' . $server,
               PMA_blowfish_encrypt($pma_pw,
               $GLOBALS['cfg']['blowfish_secret'] . $GLOBALS['current_time']),
               0,
               $GLOBALS['cookie_path'], '',
               $GLOBALS['is_https']);

        } // end if
        // For http auth. mode, the "back" link will also enforce new
        // authentication
        $http_logout = ($cfg['Server']['auth_type'] == 'http')
                     ? '&amp;old_usr=relog'
                     : '';

        // Displays the page
        require_once('./header.inc.php');
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
require_once('./header.inc.php');
echo '<h1>' . $strChangePassword . '</h1>' . "\n\n";

// Displays an error message if required
if (!empty($error_msg)) {
    echo '<p><b>' . $strError . ':&nbsp;' . $error_msg . '</b></p>' . "\n";
}

// loic1: autocomplete feature of IE kills the "onchange" event handler and it
//        must be replaced by the "onpropertychange" one in this case
$chg_evt_handler = (PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER >= 5)
                 ? 'onpropertychange'
                 : 'onchange';

// Displays the form
?>
<form method="post" action="./user_password.php" name="chgPassword" onsubmit="return checkPassword(this)">
    <?php echo PMA_generate_common_hidden_inputs(); ?>
    <table border="0">
    <tr>
        <td colspan="2">
            <input type="radio" name="nopass" value="1" onclick="pma_pw.value = ''; pma_pw2.value = ''; this.checked = true" />
            <?php echo $GLOBALS['strNoPassword'] . "\n"; ?>
        </td>
    </tr>
    <tr>
        <td>
            <input type="radio" name="nopass" value="0" checked="checked " />
            <?php echo $GLOBALS['strPassword']; ?>:&nbsp;
        </td>
        <td>
            <input type="password" name="pma_pw" size="10" class="textfield" <?php echo $chg_evt_handler; ?>="nopass[1].checked = true" />
            &nbsp;&nbsp;
            <?php echo $GLOBALS['strReType']; ?>:&nbsp;
            <input type="password" name="pma_pw2" size="10" class="textfield" <?php echo $chg_evt_handler; ?>="nopass[1].checked = true" />
        </td>
    </tr>
    <?php

if (PMA_MYSQL_INT_VERSION >= 40102) {
    ?>
    <tr>
        <td>
	    <?php echo $strPasswordHashing; ?>:
	</td>
	<td>
	    <input type="radio" name="pw_hash" id="radio_pw_hash_new" value="new" checked="checked" />
	    <label for="radio_pw_hash_new">
	        MySQL&nbsp;4.1
	    </label>
	</td>
    </tr>
    <tr>
        <td>&nbsp;</td>
	<td>
	    <input type="radio" name="pw_hash" id="radio_pw_hash_old" value="old" />
	    <label for="radio_pw_hash_old">
	        <?php echo $strCompatibleHashing; ?>
	    </label>
	</td>
    </tr>
    <?php
}

    ?>
    <tr>
        <td colspan="2">&nbsp;</td>
    </tr>
    <tr>
        <td colspan="2">
            <input type="submit" value="<?php echo($strChange); ?>" />
        </td>
    </tr>
    </table>
</form>

<?php
/**
 * Displays the footer
 */
require_once('./footer.inc.php');
?>
