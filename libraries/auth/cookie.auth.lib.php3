<?php
/* $Id$ */

// +--------------------------------------------------------------------------+
// | Set of functions used to run cookie based authentication.                |
// | Thanks to Piotr Roszatycki <d3xter at users.sourceforge.net> and         |
// | Dan Wilson who builds this patch for the Debian package.                 |
// +--------------------------------------------------------------------------+


if (!defined('PMA_COOKIE_AUTH_INCLUDED')) {
    define('PMA_COOKIE_AUTH_INCLUDED', 1);

    // Gets the default font sizes
    PMA_setFontSizes();
    // Defines the cookie path and whether the server is using https or not
    $pma_uri_parts = parse_url($cfg['PmaAbsoluteUri']);
    $cookie_path   = substr($pma_uri_parts['path'], 0, strrpos($pma_uri_parts['path'], '/'));
    $is_https      = ($pma_uri_parts['scheme'] == 'https') ? 1 : 0;


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
        global $lang, $server;
        global $HTTP_COOKIE_VARS;

        // Tries to get the username from cookie whatever are the values of the
        // 'register_globals' and the 'variables_order' directives if last login
        // should be recalled, else skip the IE autocomplete feature.
        if ($cfg['LoginCookieRecall']) {
            if (!empty($GLOBALS['pma_cookie_username'])) {
                $default_user = $GLOBALS['pma_cookie_username'];
            }
            else if (!empty($_COOKIE) && isset($_COOKIE['pma_cookie_username'])) {
                $default_user = $_COOKIE['pma_cookie_username'];
            }
            else if (!empty($HTTP_COOKIE_VARS) && isset($HTTP_COOKIE_VARS['pma_cookie_username'])) {
                $default_user = $HTTP_COOKIE_VARS['pma_cookie_username'];
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

        // Title
        ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $GLOBALS['available_languages'][$GLOBALS['lang']][2]; ?>" lang="<?php echo $GLOBALS['available_languages'][$GLOBALS['lang']][2]; ?>" dir="<?php echo $GLOBALS['text_dir']; ?>">

<head>
<title>phpMyAdmin <?php echo PMA_VERSION; ?></title>
<style type="text/css">
<!--
body            {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000}
td              {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000}
h1              {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_bigger; ?>; font-weight: bold}
select          {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; background-color:#ffffff; color:#000000}
input.textfield {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; background-color:#ffffff; color:#000000}
.warning        {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; font-weight: bold; color: #FF0000}
//-->
</style>
</head>

<body bgcolor="<?php echo $cfg['RightBgColor']; ?>">
<center>
<h1><?php echo sprintf($GLOBALS['strWelcome'], ' phpMyAdmin ' . PMA_VERSION . ' - ' . $GLOBALS['strLogin']); ?></h1>
<br />

        <?php
        // Displays the languages form
        if (empty($cfg['Lang'])) {
            echo "\n";
            ?>
<!-- Language selection -->
<form method="post" action="index.php3">
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <b>Language:&nbsp;</b>
    <select name="lang" dir="ltr" onchange="this.form.submit();">
            <?php
            echo "\n";

            uasort($available_languages, 'PMA_cookie_cmp');
            reset($available_languages);
            while (list($id, $tmplang) = each($available_languages)) {
                $lang_name = ucfirst(substr(strstr($tmplang[0], '|'), 1));
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
</form>
<br />
            <?php
        }
        echo "\n\n";

        // Displays the warning message and the login form
        ?>
<p class="warning"><?php echo $GLOBALS['strCookiesRequired']; ?></p>
<br />


<!-- Login form -->
<form method="post" action="index.php3" name="login_form"<?php echo $autocomplete; ?>>
    <table cellpadding="5">
    <tr>
        <td align="<?php echo $cell_align; ?>"><b><?php echo $GLOBALS['strLogUsername']; ?>&nbsp;</b></td>
        <td align="<?php echo $cell_align; ?>">
            <input type="text" name="pma_username" value="<?php echo (isset($default_user) ? $default_user : ''); ?>" size="24" class="textfield" onfocus="this.select()" />
        </td>
    </tr>
    <tr>
        <td align="<?php echo $cell_align; ?>"><b><?php echo $GLOBALS['strLogPassword']; ?>&nbsp;</b></td>
        <td align="<?php echo $cell_align; ?>">
            <input type="password" name="pma_password" value="" size="24" class="textfield" onfocus="this.select()" />
        </td>
    </tr>
        <?php
        if (count($cfg['Servers']) > 1) {
            echo "\n";
            ?>
    <tr>
        <td align="<?php echo $cell_align; ?>"><b><?php echo $GLOBALS['strServerChoice']; ?>&nbsp;:&nbsp;</b></td>
        <td align="<?php echo $cell_align; ?>">
            <select name="server">
            <?php
            echo "\n";
            // Displays the MySQL servers choice
            reset($cfg['Servers']);
            while (list($key, $val) = each($cfg['Servers'])) {
                if (!empty($val['host'])) {
                    echo '                <option value="' . $key . '"';
                    if (!empty($server) && ($server == $key)) {
                        echo ' selected="selected"';
                    }
                    echo '>';
                    if ($val['verbose'] != '') {
                        echo $val['verbose'];
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
        ?>
    <tr>
        <td colspan="2" align="center">
        <?php
        if (count($cfg['Servers']) == 1) {
            echo '    <input type="hidden" name="server" value="' . $server . '" />';
        }
        echo "\n";
        ?>
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="submit" value="<?php echo $GLOBALS['strLogin']; ?>" />
        </td>
    </tr>
    </table>
</form>
</center>

<script type="text/javascript" language="javascript">
var uname = document.forms['login_form'].elements['pma_username'];
var pword = document.forms['login_form'].elements['pma_password'];
if (uname.value == '') {
    uname.focus();
} else {
    pword.focus();
}
</script>
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
        global $PHP_AUTH_USER, $PHP_AUTH_PW;
        global $HTTP_COOKIE_VARS;
        global $pma_username, $pma_password, $old_usr;
        global $from_cookie;

        // Initialization
        $PHP_AUTH_USER = $PHP_AUTH_PW = '';
        $from_cookie   = FALSE;
        $from_form     = FALSE;

        // The user wants to be logged out -> delete password cookie
        if (!empty($old_usr)) {
            setcookie('pma_cookie_password', '', 0, $GLOBALS['cookie_path'], '' , $GLOBALS['is_https']);
        }

        // The user just logged in
        else if (!empty($pma_username)) {
            $PHP_AUTH_USER = $pma_username;
            $PHP_AUTH_PW   = (empty($pma_password)) ? '' : $pma_password;
            $from_form     = TRUE;
        }

        // At the end, try to set the $PHP_AUTH_USER & $PHP_AUTH_PW variables
        // from cookies whatever are the values of the 'register_globals' and
        // the 'variables_order' directives
        else {
            if (!empty($pma_cookie_username)) {
                $PHP_AUTH_USER = $pma_cookie_username;
            }
            else if (!empty($_COOKIE) && isset($_COOKIE['pma_cookie_username'])) {
                $PHP_AUTH_USER = $_COOKIE['pma_cookie_username'];
            }
            else if (!empty($HTTP_COOKIE_VARS) && isset($HTTP_COOKIE_VARS['pma_cookie_username'])) {
                $PHP_AUTH_USER = $HTTP_COOKIE_VARS['pma_cookie_username'];
            }
            if (!empty($pma_cookie_password)) {
                $PHP_AUTH_PW   = $pma_cookie_password;
                $from_cookie   = TRUE;
            }
            else if (!empty($_COOKIE) && isset($_COOKIE['pma_cookie_password'])) {
                $PHP_AUTH_PW   = $_COOKIE['pma_cookie_password'];
                $from_cookie   = TRUE;
            }
            else if (!empty($HTTP_COOKIE_VARS) && isset($HTTP_COOKIE_VARS['pma_cookie_password'])) {
                $PHP_AUTH_PW   = $HTTP_COOKIE_VARS['pma_cookie_password'];
                $from_cookie   = TRUE;
            }
        }

        // Returns whether we get authentication settings or not
        if (!$from_cookie && !$from_form) {
            return FALSE;
        } else {
            if (get_magic_quotes_gpc()) {
                $PHP_AUTH_USER = stripslashes($PHP_AUTH_USER);
                $PHP_AUTH_PW   = stripslashes($PHP_AUTH_PW);
            }
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
        global $PHP_AUTH_USER, $PHP_AUTH_PW;
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

        $cfg['Server']['user']     = $PHP_AUTH_USER;
        $cfg['Server']['password'] = $PHP_AUTH_PW;

        // Set cookies if required (once per session) and, in this case, force
        // reload to ensure the client accepts cookies
        if (!$from_cookie) {
            // Duration = one month for username
            setcookie('pma_cookie_username',
                $cfg['Server']['user'],
                time() + (60 * 60 * 24 * 30),
                $GLOBALS['cookie_path'], '' ,
                $GLOBALS['is_https']);
            // Duration = till the browser is closed for password
            setcookie('pma_cookie_password',
                $cfg['Server']['password'],
                0,
                $GLOBALS['cookie_path'], '',
                $GLOBALS['is_https']);

            header('Location: ' . $cfg['PmaAbsoluteUri'] . 'index.php3?lang=' . $GLOBALS['lang'] . '&server=' . $server);
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
        // Deletes password cookie and displays the login form
        setcookie('pma_cookie_password', '', 0, $GLOBALS['cookie_path'], '' , $GLOBALS['is_https']);
        PMA_auth();

        return TRUE;
    } // end of the 'PMA_auth()' function

} // $__PMA_COOKIE_AUTH_LIB__
?>
