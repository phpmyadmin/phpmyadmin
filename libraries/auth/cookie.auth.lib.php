<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

// +--------------------------------------------------------------------------+
// | Set of functions used to run cookie based authentication.                |
// | Thanks to Piotr Roszatycki <d3xter at users.sourceforge.net> and         |
// | Dan Wilson who built this patch for the Debian package.                  |
// +--------------------------------------------------------------------------+


if (!isset($coming_from_common)) {
   exit;
}

// Gets the default font sizes
PMA_setFontSizes();
// Defines the cookie path and whether the server is using https or not
$pma_uri_parts = parse_url($cfg['PmaAbsoluteUri']);
$cookie_path   = substr($pma_uri_parts['path'], 0, strrpos($pma_uri_parts['path'], '/'));
$is_https      = (isset($pma_uri_parts['scheme']) && $pma_uri_parts['scheme'] == 'https') ? 1 : 0;
$current_time  = time();

// Uses faster mcrypt library if available
// (Note: mcrypt.lib.php needs $cookie_path and $is_https)
// TODO: try to load mcrypt?
if (function_exists('mcrypt_encrypt')) {
    require_once('./libraries/mcrypt.lib.php');
} else {
    require_once('./libraries/blowfish.php');
}

/**
 * Sorts available languages by their true names
 *
 * @param   array   the array to be sorted
 * @param   mixed   a required parameter
 *
 * @return  the sorted array
 *
 * @access  private
 */
function PMA_cookie_cmp(&$a, $b)
{
    return (strcmp($a[1], $b[1]));
} // end of the 'PMA_cmp()' function


/**
 * Displays authentication form
 *
 * @global  string    the font face to use
 * @global  string    the default font size to use
 * @global  string    the big font size to use
 * @global  array     the list of servers settings
 * @global  array     the list of available translations
 * @global  string    the current language
 * @global  integer   the current server id
 * @global  string    the currect charset for MySQL
 * @global  array     the array of cookie variables if register_globals is
 *                    off
 *
 * @return  boolean   always true (no return indeed)
 *
 * @access  public
 */
function PMA_auth()
{
    global $right_font_family, $font_size, $font_bigger;
    global $cfg, $available_languages;
    global $lang, $server, $convcharset;
    global $conn_error;

    // Tries to get the username from cookie whatever are the values of the
    // 'register_globals' and the 'variables_order' directives if last login
    // should be recalled, else skip the IE autocomplete feature.
    if ($cfg['LoginCookieRecall']) {
        // username
        // do not try to use pma_cookie_username as it was encoded differently
        // in previous versions and would produce an undefined offset in blowfish
        if (!empty($_COOKIE) && isset($_COOKIE['pma_cookie_username-' . $server])) {
            $default_user = $_COOKIE['pma_cookie_username-' . $server];
        }
        $decrypted_user = isset($default_user) ? PMA_blowfish_decrypt($default_user, $GLOBALS['cfg']['blowfish_secret']) : '';
        $pos = strrpos($decrypted_user, ':');
        $default_user = substr($decrypted_user, 0, $pos);
        // server name
        if (!empty($GLOBALS['pma_cookie_servername'])) {
            $default_server = $GLOBALS['pma_cookie_servername'];
        }
        else if (!empty($_COOKIE) && isset($_COOKIE['pma_cookie_servername-' . $server])) {
            $default_server = $_COOKIE['pma_cookie_servername-' . $server];
        }
        if (isset($default_server) && get_magic_quotes_gpc()) {
            $default_server = stripslashes($default_server);
        }

        $autocomplete     = '';
    }
    else {
        $default_user     = '';
        $autocomplete     = ' autocomplete="off"';
    }

    $cell_align = ($GLOBALS['text_dir'] == 'ltr') ? 'left' : 'right';

    // Defines the charset to be used
    header('Content-Type: text/html; charset=' . $GLOBALS['charset']);

    require_once('./libraries/select_theme.lib.php');
    // Defines the "item" image depending on text direction
    $item_img = $GLOBALS['pmaThemeImage'] . 'item_ltr.png';

    // Title
    ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $GLOBALS['available_languages'][$GLOBALS['lang']][2]; ?>" lang="<?php echo $GLOBALS['available_languages'][$GLOBALS['lang']][2]; ?>" dir="<?php echo $GLOBALS['text_dir']; ?>">

<head>
<title>phpMyAdmin <?php echo PMA_VERSION; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $GLOBALS['charset']; ?>" />
<script language="JavaScript" type="text/javascript">
<!--
    /* added 2004-06-10 by Michael Keck
     *       we need this for Backwards-Compatibility and resolving problems
     *       with non DOM browsers, which may have problems with css 2 (like NC 4)
    */
    var isDOM      = (typeof(document.getElementsByTagName) != 'undefined'
                      && typeof(document.createElement) != 'undefined')
                   ? 1 : 0;
    var isIE4      = (typeof(document.all) != 'undefined'
                      && parseInt(navigator.appVersion) >= 4)
                   ? 1 : 0;
    var isNS4      = (typeof(document.layers) != 'undefined')
                   ? 1 : 0;
    var capable    = (isDOM || isIE4 || isNS4)
                   ? 1 : 0;
    // Uggly fix for Opera and Konqueror 2.2 that are half DOM compliant
    if (capable) {
        if (typeof(window.opera) != 'undefined') {
            var browserName = ' ' + navigator.userAgent.toLowerCase();
            if ((browserName.indexOf('konqueror 7') == 0)) {
                capable = 0;
            }
        } else if (typeof(navigator.userAgent) != 'undefined') {
            var browserName = ' ' + navigator.userAgent.toLowerCase();
            if ((browserName.indexOf('konqueror') > 0) && (browserName.indexOf('konqueror/3') == 0)) {
                capable = 0;
            }
        } // end if... else if...
    } // end if
    document.writeln('<link rel="stylesheet" type="text/css" href="<?php echo defined('PMA_PATH_TO_BASEDIR') ? PMA_PATH_TO_BASEDIR : './'; ?>css/phpmyadmin.css.php?lang=<?php echo $GLOBALS['available_languages'][$GLOBALS['lang']][2]; ?>&amp;js_frame=right&amp;js_isDOM=' + isDOM + '" />');
//-->
</script>
<noscript>
    <link rel="stylesheet" type="text/css" href="<?php echo defined('PMA_PATH_TO_BASEDIR') ? PMA_PATH_TO_BASEDIR : './'; ?>css/phpmyadmin.css.php?lang=<?php echo $GLOBALS['available_languages'][$GLOBALS['lang']][2]; ?>&amp;js_frame=right" />
</noscript>

<base href="<?php echo $cfg['PmaAbsoluteUri']; ?>" />
<script language="javascript" type="text/javascript">
<!--
// show login form in top frame
if (top != self) {
    window.top.location.href=location;
}
//-->
</script>
</head>

<body bgcolor="<?php echo $cfg['RightBgColor']; ?>">

<?php include('./config.header.inc.php'); ?>

<center>
<a href="http://www.phpmyadmin.net" target="_blank"><?php
    $logo_image = $GLOBALS['pmaThemeImage'] . 'logo_right.png';
    if (@file_exists($logo_image)) {
        echo '<img src="' . $logo_image . '" id="imLogo" name="imLogo" alt="phpMyAdmin" border="0" />';
    } else {
        echo '<img name="imLogo" id="imLogo" src="' . $GLOBALS['pmaThemeImage'] . 'pma_logo.png' . '" '
           . 'border="0" width="88" height="31" alt="phpMyAdmin" />';
    }
?></a>
<h2><?php echo sprintf($GLOBALS['strWelcome'], ' phpMyAdmin ' . PMA_VERSION); ?></h2>
    <?php
    // Displays the languages form
    if (empty($cfg['Lang'])) {
        echo "\n";
        ?>
<!-- Language selection -->
<form method="post" action="index.php" target="_top">
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <table border="0" cellpadding="3" cellspacing="0">
        <tr>
            <td><b>Language:&nbsp;</b></td>
            <td>
    <select name="lang" dir="ltr" onchange="this.form.submit();">
        <?php
        echo "\n";

        uasort($available_languages, 'PMA_cookie_cmp');
        foreach ($available_languages AS $id => $tmplang) {
            $lang_name = ucfirst(substr(strrchr($tmplang[0], '|'), 1));
            if ($lang == $id) {
                $selected = ' selected="selected"';
            } else {
                $selected = '';
            }
            echo '        ';
            echo '<option value="' . $id . '"' . $selected . '>' . $lang_name . ' (' . $id . ')</option>' . "\n";
        } // end while
        ?>
    </select>
    <input type="submit" value="<?php echo $GLOBALS['strGo']; ?>" />
            </td>
        </tr>
        <?php
    }
    echo "\n\n";

    // Displays the warning message and the login form

    if ($GLOBALS['cfg']['blowfish_secret']=='') {
    ?>
        <tr><td colspan="2" height="5"></td></tr>
        <tr>
            <th colspan="2" align="left" class="tblHeadError">
                <div class="errorhead"><?php echo $GLOBALS['strError']; ?></div>
            </th>
        </tr>
        <tr>
            <td class="tblError" colspan="2" align="left"><?php echo $GLOBALS['strSecretRequired']; ?></td>
        </tr>
<?php
        include('./config.footer.inc.php');
        echo '        </table>' . "\n"
           . '    </form>' . "\n"
           . '    </body>' . "\n"
           . '</html>';
        exit();
    }
?>
    </table>
</form>
<br />
<!-- Login form -->
<form method="post" action="index.php" name="login_form"<?php echo $autocomplete; ?> target="_top">
    <table cellpadding="3" cellspacing="0">
      <tr>
        <th align="left" colspan="2" class="tblHeaders" style="font-size: 14px; font-weight: bold;"><?php echo $GLOBALS['strLogin']; ?></th>
    </tr>
    <tr>
        <td align="center" colspan="2" bgcolor="<?php echo $GLOBALS['cfg']['BgcolorOne']; ?>"><?php echo '(' . $GLOBALS['strCookiesRequired'] . ')'; ?></td>
    </tr>
<?php if ($GLOBALS['cfg']['AllowArbitraryServer']) { ?>
    <tr>
        <td align="right" bgcolor="<?php echo $GLOBALS['cfg']['BgcolorOne']; ?>"><b><?php echo $GLOBALS['strLogServer']; ?>:&nbsp;</b></td>
        <td align="<?php echo $cell_align; ?>" bgcolor="<?php echo $GLOBALS['cfg']['BgcolorOne']; ?>">
            <input type="text" name="pma_servername" value="<?php echo (isset($default_server) ? $default_server : ''); ?>" size="24" class="textfield" onfocus="this.select()" />
        </td>
    </tr>
<?php } ?>
    <tr>
        <td align="right" bgcolor="<?php echo $GLOBALS['cfg']['BgcolorOne']; ?>"><b><?php echo $GLOBALS['strLogUsername']; ?>&nbsp;</b></td>
        <td align="<?php echo $cell_align; ?>" bgcolor="<?php echo $GLOBALS['cfg']['BgcolorOne']; ?>">
            <input type="text" name="pma_username" value="<?php echo (isset($default_user) ? $default_user : ''); ?>" size="24" class="textfield" onfocus="this.select()" />
        </td>
    </tr>
    <tr>
        <td align="right" bgcolor="<?php echo $GLOBALS['cfg']['BgcolorOne']; ?>"><b><?php echo $GLOBALS['strLogPassword']; ?>&nbsp;</b></td>
        <td align="<?php echo $cell_align; ?>" bgcolor="<?php echo $GLOBALS['cfg']['BgcolorOne']; ?>">
            <input type="password" name="pma_password" value="" size="24" class="textfield" onfocus="this.select()" />
        </td>
    </tr>
    <?php
    if (count($cfg['Servers']) > 1) {
        echo "\n";
        ?>
    <tr>
        <td align="right" bgcolor="<?php echo $GLOBALS['cfg']['BgcolorOne']; ?>"><b><?php echo $GLOBALS['strServerChoice']; ?>:&nbsp;</b></td>
        <td align="<?php echo $cell_align; ?>" bgcolor="<?php echo $GLOBALS['cfg']['BgcolorOne']; ?>">
            <select name="server"
            <?php
            if ($GLOBALS['cfg']['AllowArbitraryServer']) {
                echo ' onchange="document.forms[\'login_form\'].elements[\'pma_servername\'].value = \'\'" ';
            }
            ?>
            >
        <?php
        echo "\n";
        // Displays the MySQL servers choice
        foreach ($cfg['Servers'] AS $key => $val) {
            if (!empty($val['host']) || $val['auth_type'] == 'arbitrary') {
                echo '                <option value="' . $key . '"';
                if (!empty($server) && ($server == $key)) {
                    echo ' selected="selected"';
                }
                echo '>';
                if ($val['verbose'] != '') {
                    echo $val['verbose'];
                } elseif ($val['auth_type'] == 'arbitrary') {
                    echo $GLOBALS['strArbitrary'];
                } else {
                    echo $val['host'];
                    if (!empty($val['port'])) {
                        echo ':' . $val['port'];
                    }
                    // loic1: skip this because it's not a so good idea to
                    //        display sockets used to everybody
                    // if (!empty($val['socket']) && PMA_PHP_INT_VERSION >= 30010) {
                    //     echo ':' . $val['socket'];
                    // }
                }
                // loic1: if 'only_db' is an array and there is more than one
                //        value, displaying such informations may not be a so
                //        good idea
                if (!empty($val['only_db'])) {
                    echo ' - ' . (is_array($val['only_db']) ? implode(', ', $val['only_db']) : $val['only_db']);
                }
                if (!empty($val['user']) && ($val['auth_type'] == 'basic')) {
                    echo '  (' . $val['user'] . ')';
                }
                echo '&nbsp;</option>' . "\n";
            } // end if (!empty($val['host']))
        } // end while
        ?>
            </select>
        </td>
    </tr>
        <?php
    } // end if (server choice)
    echo "\n";
    if (!empty($conn_error)) {
        echo '<tr><td colspan="2" height="5"></td></tr>';
        echo '<tr><th colspan="2" align="left" class="tblHeadError"><div class="errorhead">' . $GLOBALS['strError'] . '</div></th></tr>' . "\n";
        echo '<tr><td colspan="2" align="left" class="tblError">'. $conn_error . '</td></tr>' . "\n";
    }
    ?>
    <tr>
        <td colspan="2" align="right">
    <?php
    if (count($cfg['Servers']) == 1) {
        echo '    <input type="hidden" name="server" value="' . $server . '" />';
    }
    echo "\n";
    ?>
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="convcharset" value="<?php echo $convcharset; ?>" />
    <?php
    if (isset($GLOBALS['db'])) {
        echo '            <input type="hidden" name="db" value="' . htmlspecialchars($GLOBALS['db']) . '" />' . "\n";
    }
    ?>
            <input type="submit" value="<?php echo $GLOBALS['strLogin']; ?>" id="buttonYes" />
        </td>
    </tr>
    </table>
</form>
</center>

<script type="text/javascript" language="javascript">
<!--
var uname = document.forms['login_form'].elements['pma_username'];
var pword = document.forms['login_form'].elements['pma_password'];
if (uname.value == '') {
    uname.focus();
} else {
    pword.focus();
}
//-->
</script>

<?php include('./config.footer.inc.php'); ?>

</body>

</html>
    <?php
    exit();

    return TRUE;
} // end of the 'PMA_auth()' function


/**
 * Gets advanced authentication settings
 *
 * @global  string    the username if register_globals is on
 * @global  string    the password if register_globals is on
 * @global  array     the array of cookie variables if register_globals is
 *                    off
 * @global  string    the servername sent by the login form
 * @global  string    the username sent by the login form
 * @global  string    the password sent by the login form
 * @global  string    the username of the user who logs out
 * @global  boolean   whether the login/password pair is grabbed from a
 *                    cookie or not
 *
 * @return  boolean   whether we get authentication settings or not
 *
 * @access  public
 */
function PMA_auth_check()
{
    global $PHP_AUTH_USER, $PHP_AUTH_PW, $pma_auth_server;
    global $pma_servername, $pma_username, $pma_password, $old_usr, $server;
    global $from_cookie;

    // Initialization
    $PHP_AUTH_USER = $PHP_AUTH_PW = '';
    $from_cookie   = FALSE;
    $from_form     = FALSE;

    // The user wants to be logged out -> delete password cookie
    if (!empty($old_usr)) {
        setcookie('pma_cookie_password-' . $server, '', 0, $GLOBALS['cookie_path'], '' , $GLOBALS['is_https']);
    }

    // The user just logged in
    else if (!empty($pma_username)) {
        $PHP_AUTH_USER = $pma_username;
        $PHP_AUTH_PW   = (empty($pma_password)) ? '' : $pma_password;
        if ($GLOBALS['cfg']['AllowArbitraryServer']) {
            $pma_auth_server = $pma_servername;
        }
        $from_form     = TRUE;
    }

    // At the end, try to set the $PHP_AUTH_USER & $PHP_AUTH_PW variables
    // from cookies whatever are the values of the 'register_globals' and
    // the 'variables_order' directives
    else {
        if ($GLOBALS['cfg']['AllowArbitraryServer']) {
            // servername
            if (!empty($pma_cookie_servername)) {
                $pma_auth_server = $pma_cookie_servername;
                $from_cookie   = TRUE;
            }
            else if (!empty($_COOKIE) && isset($_COOKIE['pma_cookie_servername-' . $server])) {
                $pma_auth_server = $_COOKIE['pma_cookie_servername-' . $server];
                $from_cookie   = TRUE;
            }
        }

        // username
        if (!empty($_COOKIE) && isset($_COOKIE['pma_cookie_username-' . $server])) {
            $PHP_AUTH_USER = $_COOKIE['pma_cookie_username-' . $server];
            $from_cookie   = TRUE;
        }
        $decrypted_user = PMA_blowfish_decrypt($PHP_AUTH_USER, $GLOBALS['cfg']['blowfish_secret']);
        $pos = strrpos($decrypted_user, ':');
        $PHP_AUTH_USER = substr($decrypted_user, 0, $pos);
        $decrypted_time = (int)substr($decrypted_user, $pos + 1);

        // User inactive too long
        if ($decrypted_time > 0 && $decrypted_time < $GLOBALS['current_time'] - $GLOBALS['cfg']['LoginCookieValidity']) {
            // Display an error message only if the inactivity has lasted
            // less than 4 times the timeout value. This is to avoid
            // alerting users with a error after "much" time has passed,
            // for example next morning.
            if ($decrypted_time > $GLOBALS['current_time'] - ($GLOBALS['cfg']['LoginCookieValidity'] * 4)) {
                $GLOBALS['no_activity'] = TRUE;
                PMA_auth_fails();
            }
            return FALSE;
        }

        // password
        if (!empty($pma_cookie_password)) {
            $PHP_AUTH_PW   = $pma_cookie_password;
        }
        else if (!empty($_COOKIE) && isset($_COOKIE['pma_cookie_password-' . $server])) {
            $PHP_AUTH_PW   = $_COOKIE['pma_cookie_password-' . $server];
        }
        else {
            $from_cookie   = FALSE;
        }
        $PHP_AUTH_PW = PMA_blowfish_decrypt($PHP_AUTH_PW, $GLOBALS['cfg']['blowfish_secret'] . $decrypted_time);

        if ($PHP_AUTH_PW == "\xff(blank)") {
            $PHP_AUTH_PW   = '';
        }
    }

    // Returns whether we get authentication settings or not
    if (!$from_cookie && !$from_form) {
        return FALSE;
    } elseif ($from_cookie) {
        return TRUE;
    } else {
        // we don't need to strip here, it is done in grab_globals
        return TRUE;
    }
} // end of the 'PMA_auth_check()' function


/**
 * Set the user and password after last checkings if required
 *
 * @global  array     the valid servers settings
 * @global  integer   the id of the current server
 * @global  array     the current server settings
 * @global  string    the current username
 * @global  string    the current password
 * @global  boolean   whether the login/password pair has been grabbed from
 *                    a cookie or not
 *
 * @return  boolean   always true
 *
 * @access  public
 */
function PMA_auth_set_user()
{
    global $cfg, $server;
    global $PHP_AUTH_USER, $PHP_AUTH_PW, $pma_auth_server;
    global $from_cookie;

    // Ensures valid authentication mode, 'only_db', bookmark database and
    // table names and relation table name are used
    if ($cfg['Server']['user'] != $PHP_AUTH_USER) {
        $servers_cnt = count($cfg['Servers']);
        for ($i = 1; $i <= $servers_cnt; $i++) {
            if (isset($cfg['Servers'][$i])
                && ($cfg['Servers'][$i]['host'] == $cfg['Server']['host'] && $cfg['Servers'][$i]['user'] == $PHP_AUTH_USER)) {
                $server        = $i;
                $cfg['Server'] = $cfg['Servers'][$i];
                break;
            }
        } // end for
    } // end if

    $pma_server_changed = FALSE;
    if ($GLOBALS['cfg']['AllowArbitraryServer']
            && isset($pma_auth_server) && !empty($pma_auth_server)
            && ($cfg['Server']['host'] != $pma_auth_server)
            ) {
        $cfg['Server']['host'] = $pma_auth_server;
        $pma_server_changed = TRUE;
    }
    $cfg['Server']['user']     = $PHP_AUTH_USER;
    $cfg['Server']['password'] = $PHP_AUTH_PW;

    // Name and password cookies needs to be refreshed each time
    // Duration = one month for username
    setcookie('pma_cookie_username-' . $server,
        PMA_blowfish_encrypt($cfg['Server']['user'] . ':' . $GLOBALS['current_time'],
            $GLOBALS['cfg']['blowfish_secret']),
        time() + (60 * 60 * 24 * 30),
        $GLOBALS['cookie_path'], '',
        $GLOBALS['is_https']);

    // Duration = till the browser is closed for password (we don't want this to be saved)
    setcookie('pma_cookie_password-' . $server,
        PMA_blowfish_encrypt(!empty($cfg['Server']['password']) ? $cfg['Server']['password'] : "\xff(blank)",
            $GLOBALS['cfg']['blowfish_secret'] . $GLOBALS['current_time']),
        0,
        $GLOBALS['cookie_path'], '',
        $GLOBALS['is_https']);

    // Set server cookies if required (once per session) and, in this case, force
    // reload to ensure the client accepts cookies
    if (!$from_cookie) {
        if ($GLOBALS['cfg']['AllowArbitraryServer']) {
            if (isset($pma_auth_server) && !empty($pma_auth_server) && $pma_server_changed) {
                // Duration = one month for serverrname
                setcookie('pma_cookie_servername-' . $server,
                    $cfg['Server']['host'],
                    time() + (60 * 60 * 24 * 30),
                    $GLOBALS['cookie_path'], '',
                    $GLOBALS['is_https']);
            } else {
                // Delete servername cookie
                setcookie('pma_cookie_servername-' . $server, '', 0, $GLOBALS['cookie_path'], '' , $GLOBALS['is_https']);
            }
        }

        // loic1: workaround against a IIS 5.0 bug
        // lem9: here, PMA_sendHeaderLocation() has not yet been defined,
        //       so use the workaround
        if (empty($GLOBALS['SERVER_SOFTWARE'])) {
            if (isset($_SERVER) && !empty($_SERVER['SERVER_SOFTWARE'])) {
                $GLOBALS['SERVER_SOFTWARE'] = $_SERVER['SERVER_SOFTWARE'];
            }
        } // end if
        if (!empty($GLOBALS['SERVER_SOFTWARE']) && $GLOBALS['SERVER_SOFTWARE'] == 'Microsoft-IIS/5.0') {
            header('Refresh: 0; url=' . $cfg['PmaAbsoluteUri'] . 'index.php?' . PMA_generate_common_url('', '', '&'));
        }
        else {
            header('Location: ' . $cfg['PmaAbsoluteUri'] . 'index.php?' . PMA_generate_common_url('', '', '&'));
        }
        exit();
    } // end if

    return TRUE;
} // end of the 'PMA_auth_set_user()' function


/**
 * User is not allowed to login to MySQL -> authentication failed
 *
 * @return  boolean   always true (no return indeed)
 *
 * @access  public
 */
function PMA_auth_fails()
{
global $conn_error, $server;

    // Deletes password cookie and displays the login form
    setcookie('pma_cookie_password-' . $server, '', 0, $GLOBALS['cookie_path'], '' , $GLOBALS['is_https']);

    if (isset($GLOBALS['allowDeny_forbidden']) && $GLOBALS['allowDeny_forbidden']) {
        $conn_error = $GLOBALS['strAccessDenied'];
    } else if (isset($GLOBALS['no_activity']) && $GLOBALS['no_activity']) {
        $conn_error = sprintf($GLOBALS['strNoActivity'],$GLOBALS['cfg']['LoginCookieValidity']);  
    } else if (PMA_DBI_getError()) {
        $conn_error = PMA_DBI_getError();
    } else if (isset($php_errormsg)) {
        $conn_error = $php_errormsg;
    } else {
        $conn_error = $GLOBALS['strCannotLogin'];
    }

    PMA_auth();

    return TRUE;
} // end of the 'PMA_auth_fails()' function

?>
