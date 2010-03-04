<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used to run cookie based authentication.
 * Thanks to Piotr Roszatycki <d3xter at users.sourceforge.net> and
 * Dan Wilson who built this patch for the Debian package.
 *
 * @package phpMyAdmin-Auth-Cookie
 * @version $Id$
 */

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Swekey authentication functions.
 */
require './libraries/auth/swekey/swekey.auth.lib.php';

if (function_exists('mcrypt_encrypt')) {
    /**
     * Uses faster mcrypt library if available
     * (as this is not called from anywhere else, put the code in-line
     *  for faster execution)
     */

    /**
     * Initialization
     * Store the initialization vector because it will be needed for
     * further decryption. I don't think necessary to have one iv
     * per server so I don't put the server number in the cookie name.
     */
    if (empty($_COOKIE['pma_mcrypt_iv'])
     || false === ($iv = base64_decode($_COOKIE['pma_mcrypt_iv'], true))) {
        srand((double) microtime() * 1000000);
         $td = mcrypt_module_open(MCRYPT_BLOWFISH, '', MCRYPT_MODE_CBC, '');   
         if ($td === false) {
            trigger_error(PMA_sanitize(sprintf($strCantLoad, 'mcrypt')), E_USER_WARNING);
         }
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        PMA_setCookie('pma_mcrypt_iv', base64_encode($iv));
    }

    /**
     * Encryption using blowfish algorithm (mcrypt)
     *
     * @param   string  original data
     * @param   string  the secret
     *
     * @return  string  the encrypted result
     *
     * @access  public
     *
     * @author  lem9
     */
    function PMA_blowfish_encrypt($data, $secret)
    {
        global $iv;
        return base64_encode(mcrypt_encrypt(MCRYPT_BLOWFISH, $secret, $data, MCRYPT_MODE_CBC, $iv));
    }

    /**
     * Decryption using blowfish algorithm (mcrypt)
     *
     * @param   string  encrypted data
     * @param   string  the secret
     *
     * @return  string  original data
     *
     * @access  public
     *
     * @author  lem9
     */
    function PMA_blowfish_decrypt($encdata, $secret)
    {
        global $iv;
        return trim(mcrypt_decrypt(MCRYPT_BLOWFISH, $secret, base64_decode($encdata), MCRYPT_MODE_CBC, $iv));
    }

} else {
    require_once './libraries/blowfish.php';
    if (!$GLOBALS['cfg']['McryptDisableWarning']) {
        trigger_error(PMA_sanitize(sprintf($strCantLoad, 'mcrypt')), E_USER_WARNING);
    }
}

/**
 * Returns blowfish secret or generates one if needed.
 * @uses    $cfg['blowfish_secret']
 * @uses    $_SESSION['auto_blowfish_secret']
 *
 * @access  public
 */
function PMA_get_blowfish_secret() {
    if (empty($GLOBALS['cfg']['blowfish_secret'])) {
        if (empty($_SESSION['auto_blowfish_secret'])) {
            // this returns 23 characters 
            $_SESSION['auto_blowfish_secret'] = uniqid('', true);
        }
        return $_SESSION['auto_blowfish_secret'];
    } else {
        // apply md5() to work around too long secrets (returns 32 characters)
        return md5($GLOBALS['cfg']['blowfish_secret']);
    }
}

/**
 * Displays authentication form
 *
 * this function MUST exit/quit the application
 *
 * @uses    $GLOBALS['server']
 * @uses    $GLOBALS['PHP_AUTH_USER']
 * @uses    $GLOBALS['pma_auth_server']
 * @uses    $GLOBALS['text_dir']
 * @uses    $GLOBALS['pmaThemeImage']
 * @uses    $GLOBALS['charset']
 * @uses    $GLOBALS['target']
 * @uses    $GLOBALS['db']
 * @uses    $GLOBALS['table']
 * @uses    $GLOBALS['strWelcome']
 * @uses    $GLOBALS['strSecretRequired']
 * @uses    $GLOBALS['strError']
 * @uses    $GLOBALS['strLogin']
 * @uses    $GLOBALS['strLogServer']
 * @uses    $GLOBALS['strLogUsername']
 * @uses    $GLOBALS['strLogPassword']
 * @uses    $GLOBALS['strServerChoice']
 * @uses    $GLOBALS['strGo']
 * @uses    $GLOBALS['strCookiesRequired']
 * @uses    $GLOBALS['strPmaDocumentation']
 * @uses    $GLOBALS['pmaThemeImage']
 * @uses    $cfg['Servers']
 * @uses    $cfg['LoginCookieRecall']
 * @uses    $cfg['Lang']
 * @uses    $cfg['Server']
 * @uses    $cfg['ReplaceHelpImg']
 * @uses    $cfg['blowfish_secret']
 * @uses    $cfg['AllowArbitraryServer']
 * @uses    $_COOKIE
 * @uses    $_REQUEST['old_usr']
 * @uses    PMA_sendHeaderLocation()
 * @uses    PMA_select_language()
 * @uses    PMA_select_server()
 * @uses    file_exists()
 * @uses    sprintf()
 * @uses    count()
 * @uses    htmlspecialchars()
 * @uses    is_array()
 * @global  string    the last connection error
 *
 * @access  public
 */
function PMA_auth()
{
    global $conn_error;

    /* Perform logout to custom URL */
    if (! empty($_REQUEST['old_usr'])
     && ! empty($GLOBALS['cfg']['Server']['LogoutURL'])) {
        PMA_sendHeaderLocation($GLOBALS['cfg']['Server']['LogoutURL']);
        exit;
    }

    /* No recall if blowfish secret is not configured as it would produce garbage */
    if ($GLOBALS['cfg']['LoginCookieRecall'] && !empty($GLOBALS['cfg']['blowfish_secret'])) {
        $default_user   = $GLOBALS['PHP_AUTH_USER'];
        $default_server = $GLOBALS['pma_auth_server'];
        $autocomplete   = '';
    } else {
        $default_user   = '';
        $default_server = '';
        // skip the IE autocomplete feature.
        $autocomplete   = ' autocomplete="off"';
    }

    $cell_align = ($GLOBALS['text_dir'] == 'ltr') ? 'left' : 'right';

    // Defines the charset to be used
    header('Content-Type: text/html; charset=' . $GLOBALS['charset']);
    // Defines the "item" image depending on text direction
    $item_img = $GLOBALS['pmaThemeImage'] . 'item_' . $GLOBALS['text_dir'] . '.png';

    /* HTML header; do not show here the PMA version to improve security */
    $page_title = 'phpMyAdmin ';
    require './libraries/header_meta_style.inc.php';
    ?>
<script type="text/javascript">
//<![CDATA[
// show login form in top frame
if (top != self) {
    window.top.location.href=location;
}
//]]>
</script>
</head>

<body class="loginform">

    <?php
    if (file_exists('./config.header.inc.php')) {
          require './config.header.inc.php';
    }
    ?>

<div class="container">
<a href="http://www.phpmyadmin.net" target="_blank" class="logo"><?php
    $logo_image = $GLOBALS['pmaThemeImage'] . 'logo_right.png';
    if (@file_exists($logo_image)) {
        echo '<img src="' . $logo_image . '" id="imLogo" name="imLogo" alt="phpMyAdmin" border="0" />';
    } else {
        echo '<img name="imLogo" id="imLogo" src="' . $GLOBALS['pmaThemeImage'] . 'pma_logo.png' . '" '
           . 'border="0" width="88" height="31" alt="phpMyAdmin" />';
    }
    ?></a>
<h1>
    <?php
    echo sprintf($GLOBALS['strWelcome'],
        '<bdo dir="ltr" xml:lang="en">' . $page_title . '</bdo>');
    ?>
</h1>
    <?php

    // Show error message
    if (! empty($conn_error)) {
        PMA_Message::rawError($conn_error)->display();
    }

    // Displays the languages form
    if (empty($GLOBALS['cfg']['Lang'])) {
        require_once './libraries/display_select_lang.lib.php';
        // use fieldset, don't show doc link
        PMA_select_language(true, false);
    }

    ?>
<br />
<!-- Login form -->
<form method="post" action="index.php" name="login_form"<?php echo $autocomplete; ?> target="_top" class="login">
    <fieldset>
    <legend>
<?php
    echo $GLOBALS['strLogin'];
    echo '<a href="./Documentation.html" target="documentation" ' .
        'title="' . $GLOBALS['strPmaDocumentation'] . '">';
    if ($GLOBALS['cfg']['ReplaceHelpImg']) {
        echo '<img class="icon" src="' . $GLOBALS['pmaThemeImage'] . 'b_help.png" width="11" height="11" alt="' . $GLOBALS['strPmaDocumentation'] . '" />';
    } else {
        echo '(*)';
    }
    echo '</a>';
?>
</legend>

<?php if ($GLOBALS['cfg']['AllowArbitraryServer']) { ?>
        <div class="item">
            <label for="input_servername" title="<?php echo $GLOBALS['strLogServerHelp']; ?>"><?php echo $GLOBALS['strLogServer']; ?></label>
            <input type="text" name="pma_servername" id="input_servername" value="<?php echo htmlspecialchars($default_server); ?>" size="24" class="textfield" title="<?php echo $GLOBALS['strLogServerHelp']; ?>" />
        </div>
<?php } ?>
        <div class="item">
            <label for="input_username"><?php echo $GLOBALS['strLogUsername']; ?></label>
            <input type="text" name="pma_username" id="input_username" value="<?php echo htmlspecialchars($default_user); ?>" size="24" class="textfield"/>
        </div>
        <div class="item">
            <label for="input_password"><?php echo $GLOBALS['strLogPassword']; ?></label>
            <input type="password" name="pma_password" id="input_password" value="" size="24" class="textfield" />
        </div>
    <?php
    if (count($GLOBALS['cfg']['Servers']) > 1) {
        ?>
        <div class="item">
            <label for="select_server"><?php echo $GLOBALS['strServerChoice']; ?>:</label>
            <select name="server" id="select_server"
        <?php
        if ($GLOBALS['cfg']['AllowArbitraryServer']) {
            echo ' onchange="document.forms[\'login_form\'].elements[\'pma_servername\'].value = \'\'" ';
        }
        echo '>';

        require_once './libraries/select_server.lib.php';
        PMA_select_server(false, false);

        echo '</select></div>';
    } else {
        echo '    <input type="hidden" name="server" value="' . $GLOBALS['server'] . '" />';
    } // end if (server choice)
    ?>
    </fieldset>
    <fieldset class="tblFooters">
        <input value="<?php echo $GLOBALS['strGo']; ?>" type="submit" id="input_go" />
    <?php
    $_form_params = array();
    if (! empty($GLOBALS['target'])) {
        $_form_params['target'] = $GLOBALS['target'];
    }
    if (! empty($GLOBALS['db'])) {
        $_form_params['db'] = $GLOBALS['db'];
    }
    if (! empty($GLOBALS['table'])) {
        $_form_params['table'] = $GLOBALS['table'];
    }
    // do not generate a "server" hidden field as we want the "server"
    // drop-down to have priority
    echo PMA_generate_common_hidden_inputs($_form_params, '', 0, 'server');
    ?>
    </fieldset>
</form>

    <?php

    // BEGIN Swekey Integration
    Swekey_login('input_username', 'input_go');
    // END Swekey Integration

    // show the "Cookies required" message only if cookies are disabled
    // (we previously tried to set some cookies)
    if (empty($_COOKIE)) {
        trigger_error($GLOBALS['strCookiesRequired'], E_USER_NOTICE);
    }
    if ($GLOBALS['error_handler']->hasDisplayErrors()) {
        echo '<div>';
        $GLOBALS['error_handler']->dispErrors();
        echo '</div>';
    }
    ?>
</div>
<script type="text/javascript">
// <![CDATA[
function PMA_focusInput()
{
    var input_username = document.getElementById('input_username');
    var input_password = document.getElementById('input_password');
    if (input_username.value == '') {
        input_username.focus();
    } else {
        input_password.focus();
    }
}

window.setTimeout('PMA_focusInput()', 500);
// ]]>
</script>
    <?php
    if (file_exists('./config.footer.inc.php')) {
         require './config.footer.inc.php';
    }
    ?>
</body>
</html>
    <?php
    exit;
} // end of the 'PMA_auth()' function



/**
 * Gets advanced authentication settings
 *
 * this function DOES NOT check authentication - it just checks/provides
 * authentication credentials required to connect to the MySQL server
 * usually with PMA_DBI_connect()
 *
 * it returns false if something is missing - which usually leads to
 * PMA_auth() which displays login form
 *
 * it returns true if all seems ok which usually leads to PMA_auth_set_user()
 *
 * it directly switches to PMA_auth_fails() if user inactivity timout is reached
 *
 * @todo    AllowArbitraryServer on does not imply that the user wants an
 *          arbitrary server, or? so we should also check if this is filled and
 *          not only if allowed
 * @uses    $GLOBALS['PHP_AUTH_USER']
 * @uses    $GLOBALS['PHP_AUTH_PW']
 * @uses    $GLOBALS['no_activity']
 * @uses    $GLOBALS['server']
 * @uses    $GLOBALS['from_cookie']
 * @uses    $GLOBALS['pma_auth_server']
 * @uses    $cfg['AllowArbitraryServer']
 * @uses    $cfg['LoginCookieValidity']
 * @uses    $cfg['Servers']
 * @uses    $_REQUEST['old_usr'] from logout link
 * @uses    $_REQUEST['pma_username'] from login form
 * @uses    $_REQUEST['pma_password'] from login form
 * @uses    $_REQUEST['pma_servername'] from login form
 * @uses    $_COOKIE
 * @uses    $_SESSION['last_access_time']
 * @uses    PMA_removeCookie()
 * @uses    PMA_blowfish_decrypt()
 * @uses    PMA_auth_fails()
 * @uses    time()
 *
 * @return  boolean   whether we get authentication settings or not
 *
 * @access  public
 */
function PMA_auth_check()
{
    // Initialization
    /**
     * @global $GLOBALS['pma_auth_server'] the user provided server to connect to
     */
    $GLOBALS['pma_auth_server'] = '';

    $GLOBALS['PHP_AUTH_USER'] = $GLOBALS['PHP_AUTH_PW'] = '';
    $GLOBALS['from_cookie'] = false;

    // BEGIN Swekey Integration
    if (! Swekey_auth_check()) {
        return false;
    }
    // END Swekey Integration

    if (defined('PMA_CLEAR_COOKIES')) {
        foreach($GLOBALS['cfg']['Servers'] as $key => $val) {
            PMA_removeCookie('pmaPass-' . $key);
            PMA_removeCookie('pmaServer-' . $key);
            PMA_removeCookie('pmaUser-' . $key);
        }
        return false;
    }

    if (! empty($_REQUEST['old_usr'])) {
        // The user wants to be logged out
        // -> delete his choices that were stored in session

        // according to the PHP manual we should do this before the destroy:
        //$_SESSION = array();
        // but we still need some parts of the session information
        // in libraries/header_meta_style.inc.php

        session_destroy();
        // -> delete password cookie(s)
        if ($GLOBALS['cfg']['LoginCookieDeleteAll']) {
            foreach($GLOBALS['cfg']['Servers'] as $key => $val) {
                PMA_removeCookie('pmaPass-' . $key);
                if (isset($_COOKIE['pmaPass-' . $key])) {
                    unset($_COOKIE['pmaPass-' . $key]);
                }
            }
        } else {
            PMA_removeCookie('pmaPass-' . $GLOBALS['server']);
            if (isset($_COOKIE['pmaPass-' . $GLOBALS['server']])) {
                unset($_COOKIE['pmaPass-' . $GLOBALS['server']]);
            }
        }
    }

    if (! empty($_REQUEST['pma_username'])) {
        // The user just logged in
        $GLOBALS['PHP_AUTH_USER'] = $_REQUEST['pma_username'];
        $GLOBALS['PHP_AUTH_PW']   = empty($_REQUEST['pma_password']) ? '' : $_REQUEST['pma_password'];
        if ($GLOBALS['cfg']['AllowArbitraryServer'] && isset($_REQUEST['pma_servername'])) {
            $GLOBALS['pma_auth_server'] = $_REQUEST['pma_servername'];
        }
        return true;
    }

    // At the end, try to set the $GLOBALS['PHP_AUTH_USER']
    // and $GLOBALS['PHP_AUTH_PW'] variables from cookies

    // servername
    if ($GLOBALS['cfg']['AllowArbitraryServer']
     && ! empty($_COOKIE['pmaServer-' . $GLOBALS['server']])) {
        $GLOBALS['pma_auth_server'] = $_COOKIE['pmaServer-' . $GLOBALS['server']];
    }

    // username
    if (empty($_COOKIE['pmaUser-' . $GLOBALS['server']])) {
        return false;
    }

    $GLOBALS['PHP_AUTH_USER'] = PMA_blowfish_decrypt(
        $_COOKIE['pmaUser-' . $GLOBALS['server']],
        PMA_get_blowfish_secret());

    // user was never logged in since session start
    if (empty($_SESSION['last_access_time'])) {
        return false;
    }

    // User inactive too long
    if ($_SESSION['last_access_time'] < time() - $GLOBALS['cfg']['LoginCookieValidity']) {
        PMA_cacheUnset('is_create_db_priv', true);
        PMA_cacheUnset('is_process_priv', true);
        PMA_cacheUnset('is_reload_priv', true);
        PMA_cacheUnset('db_to_create', true);
        PMA_cacheUnset('dbs_where_create_table_allowed', true);
        $GLOBALS['no_activity'] = true;
        PMA_auth_fails();
        exit;
    }

    // password
    if (empty($_COOKIE['pmaPass-' . $GLOBALS['server']])) {
        return false;
    }

    $GLOBALS['PHP_AUTH_PW'] = PMA_blowfish_decrypt(
        $_COOKIE['pmaPass-' . $GLOBALS['server']],
        PMA_get_blowfish_secret());

    if ($GLOBALS['PHP_AUTH_PW'] == "\xff(blank)") {
        $GLOBALS['PHP_AUTH_PW'] = '';
    }

    $GLOBALS['from_cookie'] = true;

    return true;
} // end of the 'PMA_auth_check()' function


/**
 * Set the user and password after last checkings if required
 *
 * @uses    $GLOBALS['PHP_AUTH_USER']
 * @uses    $GLOBALS['PHP_AUTH_PW']
 * @uses    $GLOBALS['server']
 * @uses    $GLOBALS['from_cookie']
 * @uses    $GLOBALS['pma_auth_server']
 * @uses    $cfg['Server']
 * @uses    $cfg['AllowArbitraryServer']
 * @uses    $cfg['LoginCookieStore']
 * @uses    $cfg['PmaAbsoluteUri']
 * @uses    $_SESSION['last_access_time']
 * @uses    PMA_COMING_FROM_COOKIE_LOGIN
 * @uses    PMA_setCookie()
 * @uses    PMA_blowfish_encrypt()
 * @uses    PMA_removeCookie()
 * @uses    PMA_sendHeaderLocation()
 * @uses    time()
 * @uses    define()
 * @return  boolean   always true
 *
 * @access  public
 */
function PMA_auth_set_user()
{
    global $cfg;

    // Ensures valid authentication mode, 'only_db', bookmark database and
    // table names and relation table name are used
    if ($cfg['Server']['user'] != $GLOBALS['PHP_AUTH_USER']) {
        foreach ($cfg['Servers'] as $idx => $current) {
            if ($current['host'] == $cfg['Server']['host']
             && $current['port'] == $cfg['Server']['port']
             && $current['socket'] == $cfg['Server']['socket']
             && $current['ssl'] == $cfg['Server']['ssl']
             && $current['connect_type'] == $cfg['Server']['connect_type']
             && $current['user'] == $GLOBALS['PHP_AUTH_USER']) {
                $GLOBALS['server'] = $idx;
                $cfg['Server']     = $current;
                break;
            }
        } // end foreach
    } // end if

    if ($GLOBALS['cfg']['AllowArbitraryServer']
     && ! empty($GLOBALS['pma_auth_server'])) {
        /* Allow to specify 'host port' */
        $parts = explode(' ', $GLOBALS['pma_auth_server']);
        if (count($parts) == 2) {
            $tmp_host = $parts[0];
            $tmp_port = $parts[1];
        } else {
            $tmp_host = $GLOBALS['pma_auth_server'];
            $tmp_port = '';
        }
        if ($cfg['Server']['host'] != $GLOBALS['pma_auth_server']) {
            $cfg['Server']['host'] = $tmp_host;
            if (!empty($tmp_port)) {
                $cfg['Server']['port'] = $tmp_port;
            }
        }
        unset($tmp_host, $tmp_port, $parts);
    }
    $cfg['Server']['user']     = $GLOBALS['PHP_AUTH_USER'];
    $cfg['Server']['password'] = $GLOBALS['PHP_AUTH_PW'];

    $_SESSION['last_access_time'] = time();

    // Name and password cookies need to be refreshed each time
    // Duration = one month for username
    PMA_setCookie('pmaUser-' . $GLOBALS['server'],
        PMA_blowfish_encrypt($cfg['Server']['user'],
            PMA_get_blowfish_secret()));

    // Duration = as configured
    PMA_setCookie('pmaPass-' . $GLOBALS['server'],
        PMA_blowfish_encrypt(!empty($cfg['Server']['password']) ? $cfg['Server']['password'] : "\xff(blank)",
            PMA_get_blowfish_secret()),
        null,
        $GLOBALS['cfg']['LoginCookieStore']);

    // Set server cookies if required (once per session) and, in this case, force
    // reload to ensure the client accepts cookies
    if (! $GLOBALS['from_cookie']) {
        if ($GLOBALS['cfg']['AllowArbitraryServer']) {
            if (! empty($GLOBALS['pma_auth_server'])) {
                // Duration = one month for servername
                PMA_setCookie('pmaServer-' . $GLOBALS['server'], $cfg['Server']['host']);
            } else {
                // Delete servername cookie
                PMA_removeCookie('pmaServer-' . $GLOBALS['server']);
            }
        }

        // URL where to go:
        $redirect_url = $cfg['PmaAbsoluteUri'] . 'index.php';

        // any parameters to pass?
        $url_params = array();
        if (strlen($GLOBALS['db'])) {
            $url_params['db'] = $GLOBALS['db'];
        }
        if (strlen($GLOBALS['table'])) {
            $url_params['table'] = $GLOBALS['table'];
        }
        // any target to pass?
        if (! empty($GLOBALS['target']) && $GLOBALS['target'] != 'index.php') {
            $url_params['target'] = $GLOBALS['target'];
        }

        /**
         * whether we come from a fresh cookie login
         */
        define('PMA_COMING_FROM_COOKIE_LOGIN', true);
        PMA_sendHeaderLocation($redirect_url . PMA_generate_common_url($url_params, '&'));
        exit();
    } // end if

    return true;
} // end of the 'PMA_auth_set_user()' function


/**
 * User is not allowed to login to MySQL -> authentication failed
 *
 * prepares error message and switches to PMA_auth() which display the error
 * and the login form
 *
 * this function MUST exit/quit the application,
 * currently doen by call to PMA_auth()
 *
 * @uses    $GLOBALS['server']
 * @uses    $GLOBALS['allowDeny_forbidden']
 * @uses    $GLOBALS['strAccessDenied']
 * @uses    $GLOBALS['strNoActivity']
 * @uses    $GLOBALS['strCannotLogin']
 * @uses    $GLOBALS['no_activity']
 * @uses    $cfg['LoginCookieValidity']
 * @uses    PMA_removeCookie()
 * @uses    PMA_getenv()
 * @uses    PMA_DBI_getError()
 * @uses    PMA_sanitize()
 * @uses    PMA_auth()
 * @uses    sprintf()
 * @uses    basename()
 * @access  public
 */
function PMA_auth_fails()
{
    global $conn_error;

    // Deletes password cookie and displays the login form
    PMA_removeCookie('pmaPass-' . $GLOBALS['server']);

    if (! empty($GLOBALS['login_without_password_is_forbidden'])) {
        $conn_error = $GLOBALS['strLoginWithoutPassword'];
    } elseif (! empty($GLOBALS['allowDeny_forbidden'])) {
        $conn_error = $GLOBALS['strAccessDenied'];
    } elseif (! empty($GLOBALS['no_activity'])) {
        $conn_error = sprintf($GLOBALS['strNoActivity'], $GLOBALS['cfg']['LoginCookieValidity']);
        // Remember where we got timeout to return on same place
        if (PMA_getenv('SCRIPT_NAME')) {
            $GLOBALS['target'] = basename(PMA_getenv('SCRIPT_NAME'));
            // avoid "missing parameter: field" on re-entry
            if ('tbl_alter.php' == $GLOBALS['target']) {
                $GLOBALS['target'] = 'tbl_structure.php';
            }
        }
    } elseif (PMA_DBI_getError()) {
        $conn_error = '#' . $GLOBALS['errno'] . ' ' . $GLOBALS['strCannotLogin']; 
    } else {
        $conn_error = $GLOBALS['strCannotLogin'];
    }

    // needed for PHP-CGI (not need for FastCGI or mod-php)
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');

    PMA_auth();
} // end of the 'PMA_auth_fails()' function

?>
