<?php
/* $Id$ */

// +--------------------------------------------------------------------------+
// | Set of functions used to run config authentication (ie no                 |
// | authentication).                                                         |
// +--------------------------------------------------------------------------+


if (!defined('PMA_CONFIG_AUTH_INCLUDED')) {
    define('PMA_CONFIG_AUTH_INCLUDED', 1);

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
     * @global  string    the connection type (persitent or not)
     * @global  string    the MySQL server port to use
     * @global  string    the MySQL socket port to use
     * @global  array     the current server settings
     * @global  string    the font face to use in case of failure
     * @global  string    the default font size to use in case of failure
     * @global  string    the big font size to use in case of failure
     *
     * @return  boolean   always true (no return indeed)
     *
     * @access  public
     */
    function PMA_auth_fails()
    {
        global $php_errormsg;
        global $connect_func, $server_port, $server_socket, $cfgServer;
        global $right_font_family, $font_size, $font_bigger;

        if (mysql_error()) {
            $conn_error = mysql_error();
        } else if (isset($php_errormsg)) {
            $conn_error = $php_errormsg;
        } else {
            $conn_error = 'Cannot connect: invalid settings.';
        }
        $local_query    = $connect_func . '('
                        . $cfgServer['host'] . $server_port . $server_socket . ', '
                        . $cfgServer['user'] . ', '
                        . $cfgServer['password'] . ')';

        // Defines the charset to be used
        header('Content-Type: text/html; charset=' . $GLOBALS['charset']);
        ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $GLOBALS['available_languages'][$GLOBALS['lang']][2]; ?>" lang="<?php echo $GLOBALS['available_languages'][$GLOBALS['lang']][2]; ?>" dir="<?php echo $GLOBALS['text_dir']; ?>">

<head>
<title><?php echo $GLOBALS['strAccessDenied']; ?></title>
<style type="text/css">
<!--
body     {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000}
h1       {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_bigger; ?>; font-weight: bold}
//-->
</style>
</head>

<body bgcolor="<?php echo $GLOBALS['cfgRightBgColor']; ?>">
<br /><br />
<center>
    <h1><?php echo sprintf($GLOBALS['strWelcome'], ' phpMyAdmin ' . PMA_VERSION); ?></h1>
</center>
<br />
        <?php
        echo "\n";
        PMA_mysqlDie($conn_error, $local_query, FALSE);

        return TRUE;
    } // end of the 'PMA_auth()' function

} // $__PMA_CONFIG_AUTH_LIB__
?>
