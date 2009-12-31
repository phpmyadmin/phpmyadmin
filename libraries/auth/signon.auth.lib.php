<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used to run single signon authentication.
 *
 * @package phpMyAdmin-Auth-Signon
 * @version $Id$
 */


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
function PMA_auth() {
    if (empty($GLOBALS['cfg']['Server']['SignonURL'])) {
        PMA_fatalError('You must set SignonURL!');
    } elseif (!empty($_REQUEST['old_usr']) && !empty($GLOBALS['cfg']['Server']['LogoutURL'])) {
        /* Perform logout to custom URL */
        PMA_sendHeaderLocation($GLOBALS['cfg']['Server']['LogoutURL']);
    } else {
        PMA_sendHeaderLocation($GLOBALS['cfg']['Server']['SignonURL']);
    }
    exit();
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

    /* Session name */
    $session_name = $GLOBALS['cfg']['Server']['SignonSession'];

    /* Current host */
    $single_signon_host = $GLOBALS['cfg']['Server']['host'];

    /* Current port */
    $single_signon_port = $GLOBALS['cfg']['Server']['port'];

    /* Are we requested to do logout? */
    $do_logout = !empty($_REQUEST['old_usr']);

    /* Does session exist? */
    if (isset($_COOKIE[$session_name])) {
        /* End current session */
        $old_session = session_name();
        $old_id = session_id();
        session_write_close();

        /* Load single signon session */
        session_name($session_name);
        session_id($_COOKIE[$session_name]);
        session_start();

        /* Grab credentials if they exist */
        if (isset($_SESSION['PMA_single_signon_user'])) {
            if ($do_logout) {
                $PHP_AUTH_USER = '';
            } else {
                $PHP_AUTH_USER = $_SESSION['PMA_single_signon_user'];
            }
        }
        if (isset($_SESSION['PMA_single_signon_password'])) {
            if ($do_logout) {
                $PHP_AUTH_PW = '';
            } else {
                $PHP_AUTH_PW = $_SESSION['PMA_single_signon_password'];
            }
        }
        if (isset($_SESSION['PMA_single_signon_host'])) {
	        $single_signon_host = $_SESSION['PMA_single_signon_host'];
        }

        if (isset($_SESSION['PMA_single_signon_port'])) {
            $single_signon_port = $_SESSION['PMA_single_signon_port'];
        }


        /* Also get token as it is needed to access subpages */
        if (isset($_SESSION['PMA_single_signon_token'])) {
            /* No need to care about token on logout */
            $pma_token = $_SESSION['PMA_single_signon_token'];
        }

        /* End single signon session */
        session_write_close();

        /* Restart phpMyAdmin session */
        session_name($old_session);
        if (!empty($old_id)) {
            session_id($old_id);
        }
        session_start();

        /* Set the single signon host */
        $GLOBALS['cfg']['Server']['host']=$single_signon_host;

       /* Set the single signon port */
       $GLOBALS['cfg']['Server']['port'] = $single_signon_port;
        /* Restore our token */
        if (!empty($pma_token)) {
            $_SESSION[' PMA_token '] = $pma_token;
        }
    }

    // Returns whether we get authentication settings or not
    if (empty($PHP_AUTH_USER)) {
        return false;
    } else {
        return true;
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
    global $cfg;
    global $PHP_AUTH_USER, $PHP_AUTH_PW;

    $cfg['Server']['user']     = $PHP_AUTH_USER;
    $cfg['Server']['password'] = $PHP_AUTH_PW;

    return true;
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
    $error = PMA_DBI_getError();
    if ($error && $GLOBALS['errno'] != 1045) {
        PMA_fatalError($error);
    } else {
        PMA_auth();
        return true;
    }

} // end of the 'PMA_auth_fails()' function

?>
