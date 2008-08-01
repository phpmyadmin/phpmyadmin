<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * session handling
 *
 * @version $Id$
 * @todo    add failover or warn if sessions are not configured properly
 * @todo    add an option to use mm-module for session handler
 * @see     http://www.php.net/session
 * @uses    session_name()
 * @uses    session_start()
 * @uses    ini_set()
 * @uses    version_compare()
 * @uses    PHP_VERSION
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

// verify if PHP supports session, die if it does not

if (!@function_exists('session_name')) {
    PMA_fatalError('strCantLoad', 'session');
} elseif (ini_get('session.auto_start') == true && session_name() != 'phpMyAdmin') {
    // Do not delete the existing session, it might be used by other 
    // applications; instead just close it.
    session_write_close();
}

// disable starting of sessions before all settings are done
// does not work, besides how it is written in php manual
//ini_set('session.auto_start', 0);

// session cookie settings
session_set_cookie_params(0, PMA_Config::getCookiePath() . '; HttpOnly',
    '', PMA_Config::isHttps());

// cookies are safer (use @ini_set() in case this function is disabled)
@ini_set('session.use_cookies', true);

// but not all user allow cookies
@ini_set('session.use_only_cookies', false);
@ini_set('session.use_trans_sid', true);
@ini_set('url_rewriter.tags',
    'a=href,frame=src,input=src,form=fakeentry,fieldset=');
//ini_set('arg_separator.output', '&amp;');

// delete session/cookies when browser is closed
@ini_set('session.cookie_lifetime', 0);

// warn but dont work with bug
@ini_set('session.bug_compat_42', false);
@ini_set('session.bug_compat_warn', true);

// use more secure session ids (with PHP 5)
if (version_compare(PHP_VERSION, '5.0.0', 'ge')) {
    @ini_set('session.hash_function', 1);
}

// some pages (e.g. stylesheet) may be cached on clients, but not in shared
// proxy servers
session_cache_limiter('private');

// start the session
// on some servers (for example, sourceforge.net), we get a permission error
// on the session data directory, so I add some "@"

// See bug #1538132. This would block normal behavior on a cluster
//ini_set('session.save_handler', 'files');

$session_name = 'phpMyAdmin';
@session_name($session_name);
// strictly, PHP 4 since 4.4.2 would not need a verification
if (version_compare(PHP_VERSION, '5.1.2', 'lt')
 && isset($_COOKIE[$session_name])
 && eregi("\r|\n", $_COOKIE[$session_name])) {
    die('attacked');
}

if (! isset($_COOKIE[$session_name])) {
    // on first start of session we will check for errors
    // f.e. session dir cannot be accessed - session file not created
    ob_start();
    $old_display_errors = ini_get('display_errors');
    $old_error_reporting = error_reporting(E_ALL);
    @ini_set('display_errors', 1);
    $r = session_start();
    @ini_set('display_errors', $old_display_errors);
    error_reporting($old_error_reporting);
    unset($old_display_errors, $old_error_reporting);
    $session_error = ob_get_contents();
    ob_end_clean();
    if ($r !== true || ! empty($session_error)) {
        setcookie($session_name, '', 1);
        PMA_fatalError('strSessionStartupErrorGeneral');
    }
} else {
    @session_start();
}

/**
 * Token which is used for authenticating access queries.
 * (we use "space PMA_token space" to prevent overwriting)
 */
if (!isset($_SESSION[' PMA_token '])) {
    $_SESSION[' PMA_token '] = md5(uniqid(rand(), true));
}

/**
 * tries to secure session from hijacking and fixation
 * should be called before login and after successfull login
 * (only required if sensitive information stored in session)
 *
 * @uses    session_regenerate_id() to secure session from fixation
 * @uses    session_id()            to set new session id
 * @uses    strip_tags()            to prevent XSS attacks in SID
 * @uses    function_exists()       for session_regenerate_id()
 */
function PMA_secureSession()
{
    // prevent session fixation and XSS
    if (function_exists('session_regenerate_id')) {
        session_regenerate_id(true);
    } else {
        session_id(strip_tags(session_id()));
    }
}
?>
