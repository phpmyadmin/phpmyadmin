<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

// +--------------------------------------------------------------------------+
// | Set of functions used to run config authentication (ie no                |
// | authentication).                                                         |
// +--------------------------------------------------------------------------+


/**
 * Displays authentication form
 *
 * @return  boolean   always true
 *
 * @access  public
 */
function PMA_auth()
{
    return TRUE;
} // end of the 'PMA_auth()' function


/**
 * Gets advanced authentication settings
 *
 * @return  boolean   always true
 *
 * @access  public
 */
function PMA_auth_check()
{
    return TRUE;
} // end of the 'PMA_auth_check()' function


/**
 * Set the user and password after last checkings if required
 *
 * @return  boolean   always true
 *
 * @access  public
 */
function PMA_auth_set_user()
{
    return TRUE;
} // end of the 'PMA_auth_set_user()' function 


/**
 * User is not allowed to login to MySQL -> authentication failed
 *
 * @global  string    the MySQL error message PHP returns
 * @global  string    the connection type (persistent or not)
 * @global  string    the MySQL server port to use
 * @global  string    the MySQL socket port to use
 * @global  array     the current server settings
 * @global  string    the font face to use in case of failure
 * @global  string    the default font size to use in case of failure
 * @global  string    the big font size to use in case of failure
 * @global  boolean   tell the "PMA_mysqlDie()" function headers have been
 *                    sent
 *
 * @return  boolean   always true (no return indeed)
 *
 * @access  public
 */
function PMA_auth_fails()
{
    global $php_errormsg, $cfg;
    global $right_font_family, $font_size, $font_bigger;
    if (PMA_DBI_getError()) {
        $conn_error = PMA_DBI_getError();
    } else if (isset($php_errormsg)) {
        $conn_error = $php_errormsg;
    } else {
        $conn_error = $GLOBALS['strConnectionError'];
    }

    // Defines the charset to be used
    header('Content-Type: text/html; charset=' . $GLOBALS['charset']);
    // Defines the theme to be used
    require_once('./libraries/select_theme.lib.php');
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

</head>

<body bgcolor="<?php echo $cfg['RightBgColor']; ?>">
<br /><br />
<center>
    <h1><?php echo sprintf($GLOBALS['strWelcome'], ' phpMyAdmin ' . PMA_VERSION); ?></h1>
</center>
<br />
<table border="0" cellpadding="0" cellspacing="3" align="center" width="80%">
    <tr>
        <td>
    <?php
    echo "\n";
    $GLOBALS['is_header_sent'] = TRUE;

    // if we display the "Server not responding" error, do not confuse users
    // by telling them they have a settings problem 
    // (note: it's true that they could have a badly typed host name, but
    //  anyway the current $strAccessDeniedExplanation tells that the server
    //  rejected the connection, which is not really what happened)
    // 2002 is the error given by mysqli
    // 2003 is the error given by mysql

    if (isset($GLOBALS['allowDeny_forbidden']) && $GLOBALS['allowDeny_forbidden']) {
        echo '<p>' . $GLOBALS['strAccessDenied'] . '</p>' . "\n";
    } else {
        if (!isset($GLOBALS['errno']) || (isset($GLOBALS['errno']) && $GLOBALS['errno'] != 2002) && $GLOBALS['errno'] != 2003) {
            echo '<p>' . $GLOBALS['strAccessDeniedExplanation'] . '</p>' . "\n";
        }
        PMA_mysqlDie($conn_error, '');
    }
?>
        </td>
    </tr>
</table>
<?php
    require_once('./footer.inc.php');
    return TRUE;
} // end of the 'PMA_auth_fails()' function

?>
