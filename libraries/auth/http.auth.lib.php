<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

// +--------------------------------------------------------------------------+
// | Set of functions used to run http authentication.                        |
// | NOTE: Requires PHP loaded as a Apache module.                            |
// +--------------------------------------------------------------------------+


/**
 * Displays authentication form
 *
 * @global  string    the font face to use in case of failure
 * @global  string    the default font size to use in case of failure
 * @global  string    the big font size to use in case of failure
 *
 * @return  boolean   always true (no return indeed)
 *
 * @access  public
 */
function PMA_auth()
{
    global $right_font_family, $font_size, $font_bigger;

    header('WWW-Authenticate: Basic realm="phpMyAdmin ' . sprintf($GLOBALS['strRunning'], (empty($GLOBALS['cfg']['Server']['verbose']) ? str_replace('\'', '\\\'',$GLOBALS['cfg']['Server']['host']) : str_replace('\'', '\\\'', $GLOBALS['cfg']['Server']['verbose']))) .  '"');
    header('HTTP/1.0 401 Unauthorized');
    header('status: 401 Unauthorized');

    // Defines the charset to be used
    header('Content-Type: text/html; charset=' . $GLOBALS['charset']);
    ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $GLOBALS['available_languages'][$GLOBALS['lang']][2]; ?>" lang="<?php echo $GLOBALS['available_languages'][$GLOBALS['lang']][2]; ?>" dir="<?php echo $GLOBALS['text_dir']; ?>">

<head>
<title><?php echo $GLOBALS['strAccessDenied']; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $GLOBALS['charset']; ?>" />
<style type="text/css">
<!--
body     {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000}
h1       {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_bigger; ?>; font-weight: bold}
//-->
</style>
</head>

<body bgcolor="<?php echo $GLOBALS['cfg']['RightBgColor']; ?>">
<br /><br />
<center>
    <h1><?php echo sprintf($GLOBALS['strWelcome'], ' phpMyAdmin ' . PMA_VERSION); ?></h1>
</center>
<br />
<p><?php echo $GLOBALS['strWrongUser']; ?></p>
</body>

</html>
    <?php
    echo "\n";
    exit();

    return TRUE;
} // end of the 'PMA_auth()' function


/**
 * Gets advanced authentication settings
 *
 * @global  string    the username if register_globals is on
 * @global  string    the password if register_globals is on
 * @global  array     the array of server variables if register_globals is
 *                    off
 * @global  array     the array of environment variables if register_globals
 *                    is off
 * @global  string    the username for the ? server
 * @global  string    the password for the ? server
 * @global  string    the username for the WebSite Professional server
 * @global  string    the password for the WebSite Professional server
 * @global  string    the username of the user who logs out
 *
 * @return  boolean   whether we get authentication settings or not
 *
 * @access  public
 */
function PMA_auth_check()
{
    global $PHP_AUTH_USER, $PHP_AUTH_PW;
    global $REMOTE_USER, $AUTH_USER, $REMOTE_PASSWORD, $AUTH_PASSWORD;
    global $HTTP_AUTHORIZATION;
    global $old_usr;

    // Grabs the $PHP_AUTH_USER variable whatever are the values of the
    // 'register_globals' and the 'variables_order' directives
    // loic1 - 2001/25/11: use the new globals arrays defined with php 4.1+
    if (empty($PHP_AUTH_USER)) {
        if (!empty($_SERVER) && isset($_SERVER['PHP_AUTH_USER'])) {
            $PHP_AUTH_USER = $_SERVER['PHP_AUTH_USER'];
        }
        else if (isset($REMOTE_USER)) {
            $PHP_AUTH_USER = $REMOTE_USER;
        }
        else if (!empty($_ENV) && isset($_ENV['REMOTE_USER'])) {
            $PHP_AUTH_USER = $_ENV['REMOTE_USER'];
        }
        else if (@getenv('REMOTE_USER')) {
            $PHP_AUTH_USER = getenv('REMOTE_USER');
        }
        // Fix from Matthias Fichtner for WebSite Professional - Part 1
        else if (isset($AUTH_USER)) {
            $PHP_AUTH_USER = $AUTH_USER;
        }
        else if (!empty($_ENV) && isset($_ENV['AUTH_USER'])) {
            $PHP_AUTH_USER = $_ENV['AUTH_USER'];
        }
        else if (@getenv('AUTH_USER')) {
            $PHP_AUTH_USER = getenv('AUTH_USER');
        }
    }
    // Grabs the $PHP_AUTH_PW variable whatever are the values of the
    // 'register_globals' and the 'variables_order' directives
    // loic1 - 2001/25/11: use the new globals arrays defined with php 4.1+
    if (empty($PHP_AUTH_PW)) {
        if (!empty($_SERVER) && isset($_SERVER['PHP_AUTH_PW'])) {
            $PHP_AUTH_PW = $_SERVER['PHP_AUTH_PW'];
        }
        else if (isset($REMOTE_PASSWORD)) {
            $PHP_AUTH_PW = $REMOTE_PASSWORD;
        }
        else if (!empty($_ENV) && isset($_ENV['REMOTE_PASSWORD'])) {
            $PHP_AUTH_PW = $_ENV['REMOTE_PASSWORD'];
        }
        else if (@getenv('REMOTE_PASSWORD')) {
            $PHP_AUTH_PW = getenv('REMOTE_PASSWORD');
        }
        // Fix from Matthias Fichtner for WebSite Professional - Part 2
        else if (isset($AUTH_PASSWORD)) {
            $PHP_AUTH_PW = $AUTH_PASSWORD;
        }
        else if (!empty($_ENV) && isset($_ENV['AUTH_PASSWORD'])) {
            $PHP_AUTH_PW = $_ENV['AUTH_PASSWORD'];
        }
        else if (@getenv('AUTH_PASSWORD')) {
            $PHP_AUTH_PW = getenv('AUTH_PASSWORD');
        }
    }
    // Gets authenticated user settings with IIS
    if (empty($PHP_AUTH_USER) && empty($PHP_AUTH_PW)
        && function_exists('base64_decode')) {
        if (!empty($HTTP_AUTHORIZATION)
            && substr($HTTP_AUTHORIZATION, 0, 6) == 'Basic ') {
            list($PHP_AUTH_USER, $PHP_AUTH_PW) = explode(':', base64_decode(substr($HTTP_AUTHORIZATION, 6)));
        }
        else if (!empty($_ENV)
             && isset($_ENV['HTTP_AUTHORIZATION'])
             && substr($_ENV['HTTP_AUTHORIZATION'], 0, 6) == 'Basic ') {
            list($PHP_AUTH_USER, $PHP_AUTH_PW) = explode(':', base64_decode(substr($_ENV['HTTP_AUTHORIZATION'], 6)));
        }
        else if (@getenv('HTTP_AUTHORIZATION')
                 && substr(getenv('HTTP_AUTHORIZATION'), 0, 6) == 'Basic ') {
            list($PHP_AUTH_USER, $PHP_AUTH_PW) = explode(':', base64_decode(substr(getenv('HTTP_AUTHORIZATION'), 6)));
        }
    } // end IIS

    // User logged out -> ensure the new username is not the same
    if (!empty($old_usr)
        && (isset($PHP_AUTH_USER) && $old_usr == $PHP_AUTH_USER)) {
        $PHP_AUTH_USER = '';
    }

    // Returns whether we get authentication settings or not
    if (empty($PHP_AUTH_USER)) {
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
 *
 * @return  boolean   always true
 *
 * @access  public
 */
function PMA_auth_set_user()
{
    global $cfg, $server;
    global $PHP_AUTH_USER, $PHP_AUTH_PW;

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
    PMA_auth();

    return TRUE;
} // end of the 'PMA_auth_fails()' function

?>
