<?php
/* $Id$ */


/**
 * Misc stuff and functions used by almost all the scripts.
 * Among other things, it contains the advanced authentification work.
 */



if (!defined('__LIB_COMMON__')){
    define('__LIB_COMMON__', 1);

    /**
     * Order of sections for common.lib.php3:
     *
     * in PHP3, functions and constants must be physically defined
     * before they are referenced
     *
     * some functions need the constants of libraries/defines.lib.php3
     *
     * the include of libraries/defines.lib.php3 must be after the connection
     * to db to get the MySql version
     *
     * the auth() function must be before the connection to db
     *
     * the mysql_die() function must be before the connection to db but after
     * mysql extension has been loaded
     *
     * ... so the required order is:
     *
     * - definition of auth()
     * - parsing of the configuration file
     * - first load of the libraries/define.lib.php3 library (won't get the
     *   MySQL release number)
     * - load of mysql extension (if necessary)
     * - definition of mysql_die()
     * - db connection
     * - second load of the libraries/define.lib.php3 library to get the MySQL
     *   release number)
     * - other functions, respecting dependencies 
     */


    /**
     * Avoids undefined variables in PHP3
     */
    if (!isset($use_backquotes)) {
        $use_backquotes   = 0;
    }
    if (!isset($pos)) {
        $pos              = 0;
    }
    if (!isset($cfgProtectBinary)) {
        $cfgProtectBinary = FALSE;
    }


    /**
     * Advanced authentication work
     *
     * Requires Apache loaded as a php module.
     *
     * @access  public
     */
    function auth()
    {
        header('WWW-Authenticate: Basic realm="phpMyAdmin ' . trim($GLOBALS['strRunning']) . ' ' . $GLOBALS['cfgServer']['host'] . '"');
        header('HTTP/1.0 401 Unauthorized');
        header('status: 401 Unauthorized');

        echo '<?xml version="1.0" encoding="' . strtoupper($charset) . '"?>' . "\n";
        ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $available_languages[$lang][2]; ?>" lang="<?php echo $available_languages[$lang][2]; ?>">

<head>
<title><?php echo $GLOBALS['strAccessDenied']; ?></title>
</head>

<body bgcolor="#FFFFFF">
<br /><br />
<center>
    <h1><?php echo $GLOBALS['strWrongUser']; ?></h1>
</center>
</body>

</html>
        <?php
        echo "\n";
        exit();
    } // end of the 'auth()' function


    /**
     * Parses the configuration file and gets some constants used to define
     * versions of phpMyAdmin/php/mysql...
     */
    include('./config.inc.php3');
    // For compatibility with old config.inc.php3
    if (!isset($cfgTextareaCols)) {
        $cfgTextareaCols = 40;
    }
    if (!isset($cfgTextareaRows)) {
        $cfgTextareaRows = 7;
    }
    // Adds a trailing slash et the end of the phpMyAdmin uri if it does not
    // exist
    if ($cfgPmaAbsoluteUri != '' && substr($cfgPmaAbsoluteUri, -1) != '/') {
        $cfgPmaAbsoluteUri .= '/';
    }
    // Gets some constants
    include('./libraries/defines.lib.php3');
    // If zlib output compression is set in the php configuration file, no
    // output buffering should be run
    if (PHP_INT_VERSION < 40000
        || (PHP_INT_VERSION >= 40005 && @ini_get('zlib.output_compression'))) {
        $cfgOBGzip = FALSE;
    }



    /**
     * Loads the mysql extensions if it is not loaded yet
     * staybyte - 26. June 2001
     */
    if (((PHP_INT_VERSION >= 40000 && !@ini_get('safe_mode'))
        || (PHP_INT_VERSION > 30009 && !@get_cfg_var('safe_mode')))
        && @function_exists('dl')) {
        if (PHP_INT_VERSION < 40000) {
            $extension = 'MySQL';
        } else {
            $extension = 'mysql';
        }
        if (PMA_WINDOWS) {
            $suffix = '.dll';
        } else {
            $suffix = '.so';
        }
        if (!@extension_loaded($extension)) {
            @dl($extension.$suffix);
        }
        if (!@extension_loaded($extension)) {
            echo $strCantLoadMySQL;
            exit();
        }
    } // end load mysql extension


    /**
     * Displays a MySQL error message in the right frame.
     *
     * @param   string   the error mesage
     * @param   string   the sql query that failed
     * @param   boolean  whether to show a "modify" link or not
     * @param   string   the "back" link url (full path is not required)
     *
     * @access  public
     */
    function mysql_die($error_message = '', $the_query = '',
                       $is_modify_link = TRUE, $back_url = '')
    {
        if (!$error_message) {
            $error_message = mysql_error();
        }
        if (!$the_query && !empty($GLOBALS['sql_query'])) {
            $the_query = $GLOBALS['sql_query'];
        }

        echo '<b>'. $GLOBALS['strError'] . '</b>' . "\n";
        // if the config password is wrong, or the MySQL server does not
        // respond, do not show the query that would reveal the
        // username/password
        if (!empty($the_query) && !strstr($the_query, 'connect')) {
            $query_base = htmlspecialchars($the_query);
            $query_base = ereg_replace("((\015\012)|(\015)|(\012)){3,}", "\n\n", $query_base);
            echo '<p>' . "\n";
            echo '    ' . $GLOBALS['strSQLQuery'] . '&nbsp;:&nbsp;' . "\n";
            if ($is_modify_link) {
                echo '    ['
                     . '<a href="db_details.php3?lang=' . $GLOBALS['lang'] . '&server=' . urlencode($GLOBALS['server']) . '&db=' . urlencode($GLOBALS['db']) . '&sql_query=' . urlencode($the_query) . '&show_query=y">' . $GLOBALS['strEdit'] . '</a>'
                     . ']' . "\n";
            } // end if
            echo '<pre>' . "\n" . $query_base . "\n" . '</pre>' . "\n";
            echo '</p>' . "\n";
        } // end if
        if (!empty($error_message)) {
            $error_message = htmlspecialchars($error_message);
            $error_message = ereg_replace("((\015\012)|(\015)|(\012)){3,}", "\n\n", $error_message);
        }
        echo '<p>' . "\n";
        echo '    ' . $GLOBALS['strMySQLSaid'] . '<br />' . "\n";
        echo '<pre>' . "\n" . $error_message . "\n" . '</pre>' . "\n";
        echo '</p>' . "\n";
        if (!empty($back_url)) {
            echo '<a href="' . $back_url . '">' . $GLOBALS['strBack'] . '</a>';
        }
        echo "\n";

        include('./footer.inc.php3');
        exit();
    } // end of the 'mysql_die()' function


    /**
     * Use mysql_connect() or mysql_pconnect()?
     */
    $connect_func = ($cfgPersistentConnections) ? 'mysql_pconnect' : 'mysql_connect';
    $dblist       = array();


    /**
     * Gets the valid servers list and parameters
     */
    reset($cfgServers);
    while (list($key, $val) = each($cfgServers)) {
        // Don't use servers with no hostname
        if (empty($val['host'])) {
            unset($cfgServers[$key]);
        }
    }
 
    if (empty($server) || !isset($cfgServers[$server]) || !is_array($cfgServers[$server])) {
        $server = $cfgServerDefault;
    }


    /**
     * If no server is selected, make sure that $cfgServer is empty (so that
     * nothing will work), and skip server authentication.
     * We do NOT exit here, but continue on without logging into any server.
     * This way, the welcome page will still come up (with no server info) and
     * present a choice of servers in the case that there are multiple servers
     * and '$cfgServerDefault = 0' is set.
     */
    if ($server == 0) {
        $cfgServer = array();
    }

    /**
     * Otherwise, set up $cfgServer and do the usual login stuff.
     */
    else if (isset($cfgServers[$server])) {
        $cfgServer = $cfgServers[$server];

        // Check how the config says to connect to the server
        $server_port   = (empty($cfgServer['port']))
                       ? ''
                       : ':' . $cfgServer['port'];
        if (strtolower($cfgServer['connect_type']) == 'tcp') {
            $cfgServer['socket'] = '';
        }
        $server_socket = (empty($cfgServer['socket']) || PHP_INT_VERSION < 30010)
                       ? ''
                       : ':' . $cfgServer['socket'];

        // The user can work with only some databases
        if (isset($cfgServer['only_db']) && !empty($cfgServer['only_db'])) {
            if (is_array($cfgServer['only_db'])) {
                $dblist   = $cfgServer['only_db'];
            } else {
                $dblist[] = $cfgServer['only_db'];
            }
        }

        // Advanced authentication is required
        if ($cfgServer['adv_auth']) {
            // Grabs the $PHP_AUTH_USER variable whatever are the values of the
            // 'register_globals' and the 'variables_order' directives
            if (empty($PHP_AUTH_USER)) {
                if (!empty($HTTP_SERVER_VARS) && isset($HTTP_SERVER_VARS['PHP_AUTH_USER'])) {
                    $PHP_AUTH_USER = $HTTP_SERVER_VARS['PHP_AUTH_USER'];
                }
                else if (isset($REMOTE_USER)) {
                    $PHP_AUTH_USER = $REMOTE_USER;
                }
                else if (!empty($HTTP_ENV_VARS) && isset($HTTP_ENV_VARS['REMOTE_USER'])) {
                    $PHP_AUTH_USER = $HTTP_ENV_VARS['REMOTE_USER'];
                }
                else if (@getenv('REMOTE_USER')) {
                    $PHP_AUTH_USER = getenv('REMOTE_USER');
                }
                // Fix from Matthias Fichtner for WebSite Professional - Part 1
                else if (isset($AUTH_USER)) {
                    $PHP_AUTH_USER = $AUTH_USER;
                }
                else if (!empty($HTTP_ENV_VARS) && isset($HTTP_ENV_VARS['AUTH_USER'])) {
                    $PHP_AUTH_USER = $HTTP_ENV_VARS['AUTH_USER'];
                }
                else if (@getenv('AUTH_USER')) {
                    $PHP_AUTH_USER = getenv('AUTH_USER');
                }
            }
            // Grabs the $PHP_AUTH_PW variable whatever are the values of the
            // 'register_globals' and the 'variables_order' directives
            if (empty($PHP_AUTH_PW)) {
                if (!empty($HTTP_SERVER_VARS) && isset($HTTP_SERVER_VARS['PHP_AUTH_PW'])) {
                    $PHP_AUTH_PW = $HTTP_SERVER_VARS['PHP_AUTH_PW'];
                }
                else if (isset($REMOTE_PASSWORD)) {
                    $PHP_AUTH_PW = $REMOTE_PASSWORD;
                }
                else if (!empty($HTTP_ENV_VARS) && isset($HTTP_ENV_VARS['REMOTE_PASSWORD'])) {
                    $PHP_AUTH_PW = $HTTP_ENV_VARS['REMOTE_PASSWORD'];
                }
                else if (@getenv('REMOTE_PASSWORD')) {
                    $PHP_AUTH_PW = getenv('REMOTE_PASSWORD');
                }
                // Fix from Matthias Fichtner for WebSite Professional - Part 2
                else if (isset($AUTH_PASSWORD)) {
                    $PHP_AUTH_PW = $AUTH_PASSWORD;
                }
                else if (!empty($HTTP_ENV_VARS) && isset($HTTP_ENV_VARS['AUTH_PASSWORD'])) {
                    $PHP_AUTH_USER = $HTTP_ENV_VARS['AUTH_PASSWORD'];
                }
                else if (@getenv('AUTH_PASSWORD')) {
                    $PHP_AUTH_USER = getenv('AUTH_PASSWORD');
                }
            }
            // Grabs the $old_usr variable whatever are the values of the
            // 'register_globals' and the 'variables_order' directives
            if (empty($old_usr) && !empty($HTTP_GET_VARS) && isset($HTTP_GET_VARS['old_usr'])) {
                $old_usr = $HTTP_GET_VARS['old_usr'];
            }

            // First load -> checks if authentication is required
            if (!isset($old_usr)) {
                if (empty($PHP_AUTH_USER)) {
                    $do_auth = TRUE;
                } else {
                    $do_auth = FALSE;
                }
            }
            // Else ensure the username is not the same
            else {
                // force user to enter a different username
                if (isset($PHP_AUTH_USER) && $old_usr == $PHP_AUTH_USER) {
                    $do_auth = TRUE;
                } else {
                    $do_auth = FALSE;
                }
            }

            // Calls the authentication window or validates user's login
            if ($do_auth) {
                auth();
            } else {
                $bkp_track_err   = (PHP_INT_VERSION >= 40000) ? @ini_set('track_errors', 1) : '';
                $dbh             = @$connect_func(
                                     $cfgServer['host'] . $server_port . $server_socket,
                                     $cfgServer['stduser'],
                                     $cfgServer['stdpass']
                                 );
                if ($dbh == FALSE) {
                    if (mysql_error()) {
                        $conn_error = mysql_error();
                    } else if (isset($php_errormsg)) {
                        $conn_error = $php_errormsg;
                    } else {
                        $conn_error = 'Cannot connect: invalid settings.';
                    }
                    if (PHP_INT_VERSION >= 40000) {
                        @ini_set('track_errors', $bkp_track_err);
                    }
                    $local_query    = $connect_func . '('
                                    . $cfgServer['host'] . $server_port . $server_socket . ', '
                                    . $cfgServer['stduser'] . ', '
                                    . $cfgServer['stdpass'] . ')';
                    mysql_die($conn_error, $local_query, FALSE);
                } else if (PHP_INT_VERSION >= 40000) {
                    @ini_set('track_errors', $bkp_track_err);
                }

                $PHP_AUTH_USER = str_replace('\'', '\\\'', $PHP_AUTH_USER);
                $PHP_AUTH_PW   = str_replace('\'', '\\\'', $PHP_AUTH_PW);
                $auth_query = 'SELECT User, Password, Select_priv '
                            . 'FROM mysql.user '
                            . 'WHERE '
                            .    'User = \'' . $PHP_AUTH_USER . '\' '
                            .    'AND Password = PASSWORD(\'' . $PHP_AUTH_PW . '\')';
                $rs         = mysql_query($auth_query, $dbh) or mysql_die('', $auth_query, FALSE);

                // Invalid login -> relog
                if (@mysql_numrows($rs) <= 0) {
                    auth();
                }
                // Seems to be a valid login...
                else {
                    $row = mysql_fetch_array($rs);
                    mysql_free_result($rs);
                    // Correction uva 19991215
                    // Previous code assumed database "mysql" admin table "db"
                    // column "db" contains literal name of user database, and
                    // works if so.
                    // Mysql usage generally (and uva usage specifically)
                    // allows this column to contain regular expressions (we
                    // have all databases owned by a given
                    // student/faculty/staff beginning with user i.d. and
                    // governed by default by a single set of privileges with
                    // regular expression as key). This breaks previous code.
                    // This maintenance is to fix code to work correctly for
                    // regular expressions.
                    if ($row['Select_priv'] != 'Y') {
                        // lem9: User can be blank (anonymous user)
                        $local_query = 'SELECT DISTINCT Db FROM mysql.db WHERE Select_priv = \'Y\' AND (User = \'' . $PHP_AUTH_USER . '\' OR User = \'\')';
                        $rs          = mysql_query($local_query) or mysql_die('', $local_query, FALSE);
                        if (@mysql_numrows($rs) <= 0) {
                            $local_query = 'SELECT DISTINCT Db FROM mysql.tables_priv WHERE Table_priv LIKE \'%Select%\' AND User = \'' . $PHP_AUTH_USER . '\'';
                            $rs          = mysql_query($local_query) or mysql_die('', $local_query, FALSE);
                            if (@mysql_numrows($rs) <= 0) {
                                auth();
                            } else {
                                while ($row = mysql_fetch_array($rs)) {
                                    $dblist[] = $row['Db'];
                                }
                                mysql_free_result($rs);
                            }
                        } else {
                            // Will use as associative array of the following 2
                            // code lines:
                            //   the 1st is the only line intact from before
                            //     correction,
                            //   the 2nd replaces $dblist[] = $row['Db'];
                            $uva_mydbs = array();
                            // Code following those 2 lines in correction
                            // continues populating $dblist[], as previous code
                            // did. But it is now populated with actual
                            // database names instead of with regular
                            // expressions.
                            while ($row = mysql_fetch_array($rs)) {
                                $uva_mydbs[$row['Db']] = 1;
                            }
                            mysql_free_result($rs);
                            $uva_alldbs = mysql_list_dbs();
                            while ($uva_row = mysql_fetch_array($uva_alldbs)) {
                                $uva_db = $uva_row[0];
                                if (isset($uva_mydbs[$uva_db]) && 1 == $uva_mydbs[$uva_db]) {
                                    $dblist[]           = $uva_db;
                                    $uva_mydbs[$uva_db] = 0;
                                } else {
                                    reset($uva_mydbs);
                                    while (list($uva_matchpattern, $uva_value) = each($uva_mydbs)) {
                                        $uva_regex = ereg_replace('%', '.+', $uva_matchpattern);
                                        // Fixed db name matching
                                        // 2000-08-28 -- Benjamin Gandon
                                        if (ereg('^' . $uva_regex . '$', $uva_db)) {
                                            $dblist[] = $uva_db;
                                            break;
                                        }
                                    } // end while
                                } // end if ... else ....
                            } // end while
                            mysql_free_result($uva_alldbs);
                        } // end else
                    } // end if
                } // end else
            }

            // Validation achived -> store user's login/password
            $cfgServer['user']     = $PHP_AUTH_USER;
            $cfgServer['password'] = $PHP_AUTH_PW;
        } // end Advanced authentication

        // Do connect to the user's database
        $bkp_track_err   = (PHP_INT_VERSION >= 40000) ? @ini_set('track_errors', 1) : '';
        $link            = @$connect_func(
                             $cfgServer['host'] . $server_port . $server_socket,
                             $cfgServer['user'],
                             $cfgServer['password']
                         );
        if ($link == FALSE) {
            if (mysql_error()) {
                $conn_error = mysql_error();
            } else if (isset($php_errormsg)) {
                $conn_error = $php_errormsg;
            } else {
                $conn_error = 'Cannot connect: invalid settings.';
            }
            if (PHP_INT_VERSION >= 40000) {
                @ini_set('track_errors', $bkp_track_err);
            }
            $local_query    = $connect_func . '('
                            . $cfgServer['host'] . $server_port . $server_socket . ', '
                            . $cfgServer['user'] . ', '
                            . $cfgServer['password'] . ')';
            mysql_die($conn_error, $local_query, FALSE);
        } else if (PHP_INT_VERSION >= 40000) {
            @ini_set('track_errors', $bkp_track_err);
        }
    } // end server connecting

    /**
     * Missing server hostname
     */
    else {
        echo $strHostEmpty;
    }


    /**
     * Gets constants that defines the PHP, MySQL... releases.
     * This include must be located physically before any code that needs to
     * reference the constants, else PHP 3.0.16 won't be happy; and must be
     * located after we are connected to db to get the MySql version.
     */
    include('./libraries/defines.lib.php3');



    /* ----------------------- Set of misc functions ----------------------- */

    /**
     * Determines the font sizes to use depending on the os and browser of the
     * user.
     *
     * This function is based on an article from phpBuilder (see
     * http://www.phpbuilder.net/columns/tim20000821.php3).
     *
     * @return  boolean    always true
     *
     * @global  string     the standard font size
     * @global  string     the font size for titles
     * @global  string     the small font size
     * @global  string     the smallest font size
     *
     * @access  public
     *
     * @version 1.1
     */
    function set_font_sizes()
    {
        global $font_size, $font_bigger, $font_smaller, $font_smallest;

        // IE (<6)/Opera for win case: needs smaller fonts than anyone else
        if (USR_OS == 'Win'
            && (USR_BROWSER_AGENT == 'IE' || USR_BROWSER_AGENT == 'OPERA')) {
            $font_size     = 'x-small';
            $font_bigger   = 'large';
            $font_smaller  = (USR_BROWSER_AGENT == 'IE' && USR_BROWSER_VER < 5.5)
                           ? '80%'
                           : '90%';
            $font_smallest = '7pt';
        }
        // IE6 and other browsers for win case
        else if (USR_OS == 'Win') {
            $font_size     = 'small';
            $font_bigger   = 'large ';
            $font_smaller  = 'x-small';
            $font_smallest = 'x-small';
        }
        // Mac browsers: need bigger fonts
        else if (USR_OS == 'Mac') {
            $font_size     = 'medium';
            $font_bigger   = 'x-large ';
            $font_smaller  = 'small';
            $font_smallest = 'x-small';
        }
        // Other cases
        else {
            $font_size     = 'small';
            $font_bigger   = 'large ';
            $font_smaller  = 'x-small';
            $font_smallest = 'x-small';
        }

        return true;
    } // end of the 'set_font_sizes()' function


    /**
     * Adds backquotes on both sides of a database, table or field name.
     * Since MySQL 3.23.6 this allows to use non-alphanumeric characters in
     * these names.
     *
     * @param   string   the database, table or field name to "backquote"
     * @param   boolean  a flag to bypass this function (used by dump functions)
     *
     * @return  string   the "backquoted" database, table or field name if the
     *                   current MySQL release is >= 3.23.6, the original one
     *                   else
     *
     * @access  public
     */
    function backquote($a_name, $do_it = TRUE)
    {
        if ($do_it
            && MYSQL_INT_VERSION >= 32306
            && !empty($a_name) && $a_name != '*') {
            return '`' . $a_name . '`';
        } else {
            return $a_name;
        }
    } // end of the 'backquote()' function


    /**
     * Add slashes before "'" and "\" characters so a value containing them can
     * be used in a sql comparison.
     *
     * @param   string   the string to slash
     * @param   boolean  whether the string will be used in a 'LIKE' clause
     *                   (it then requires two more escaped sequences) or not
     *
     * @return  string   the slashed string
     *
     * @access  public
     */
    function sql_addslashes($a_string = '', $is_like = FALSE)
    {
        if ($is_like) {
            $a_string = str_replace('\\', '\\\\\\\\', $a_string);
        } else {
            $a_string = str_replace('\\', '\\\\', $a_string);
        }
        $a_string = str_replace('\'', '\\\'', $a_string);
    
        return $a_string;
    } // end of the 'sql_addslashes()' function


    /**
     * Format a string so it can be passed to a javascript function.
     * This function is used to displays a javascript confirmation box for
     * "DROP/DELETE/ALTER" queries.
     *
     * @param   string   the string to format
     * @param   boolean  whether to add backquotes to the string or not
     *
     * @return  string   the formated string
     *
     * @access  public
     */
    function js_format($a_string = '', $add_backquotes = TRUE)
    {
        $a_string = str_replace('"', '&quot;', $a_string);
        $a_string = str_replace('#', '\\#', addslashes($a_string));
        $a_string = str_replace("\012", '\\\\n', $a_string);
        $a_string = str_replace("\015", '\\\\r', $a_string);

        return (($add_backquotes) ? backquote($a_string) : $a_string);
    } // end of the 'sql_addslashes()' function


    /**
     * Defines the <CR><LF> value depending on the user OS.
     *
     * @return  string   the <CR><LF> value to use
     *
     * @access  public
     */
    function which_crlf()
    {
        $the_crlf = "\n";

        // The 'USR_OS' constant is defined in "./libraries/defines.lib.php3"
        // Win case
        if (USR_OS == 'Win') {
            $the_crlf = "\r\n";
        }
        // Mac case
        else if (USR_OS == 'Mac') {
            $the_crlf = "\r";
        }
        // Others
        else {
            $the_crlf = "\n";
        }

        return $the_crlf;
    } // end of the 'which_crlf()' function


    /**
     * Counts and displays the number of records in a table
     *
     * Last revision 13 July 2001: Patch for limiting dump size from
     * vinay@sanisoft.com & girish@sanisoft.com
     *
     * @param   string   the current database name
     * @param   string   the current table name
     * @param   boolean  whether to retain or to displays the result
     *
     * @return  mixed    the number of records if retain is required, true else
     *
     * @access  public
     */
    function count_records($db, $table, $ret = FALSE)
    {
        $result = mysql_query('SELECT COUNT(*) AS num FROM ' . backquote($db) . '.' . backquote($table));
        $num    = mysql_result($result, 0, 'num');
        mysql_free_result($result);
        if ($ret) {
            return $num;
        } else {
            echo number_format($num, 0, $GLOBALS['number_decimal_separator'], $GLOBALS['number_thousands_separator']);
            return TRUE;
        }
    } // end of the 'count_records()' function


    /**
     * Displays a message at the top of the "main" (right) frame
     *
     * @param   string  the message to display
     *
     * @access  public
     */
    function show_message($message)
    {
        // Reloads the navigation frame via JavaScript if required
        if (isset($GLOBALS['reload']) && $GLOBALS['reload']) {
            echo "\n";
            $reload_url = './left.php3'
                        . '?lang=' . $GLOBALS['lang']
                        . '&server=' . $GLOBALS['server']
                        . ((!empty($GLOBALS['db'])) ? '&db=' . urlencode($GLOBALS['db']) : '');
            ?>
<script type="text/javascript" language="javascript1.2">
<!--
window.parent.frames['nav'].location.replace('<?php echo $reload_url; ?>');
//-->
</script>
            <?php
        }
        echo "\n";
        ?>
<div align="left">
    <table border="<?php echo $GLOBALS['cfgBorder']; ?>" cellpadding="5">
    <tr>
        <td bgcolor="<?php echo $GLOBALS['cfgThBgcolor']; ?>">
            <b><?php echo stripslashes($message); ?></b><br />
        </td>
    </tr>
        <?php
        if ($GLOBALS['cfgShowSQL'] == TRUE && !empty($GLOBALS['sql_query'])) {
            echo "\n";
            ?>
    <tr>
        <td bgcolor="<?php echo $GLOBALS['cfgBgcolorOne']; ?>">
            <?php
            echo "\n";
            // The nl2br function isn't used because its result isn't a valid
            // xhtml1.0 statement before php4.0.5 ("<br>" and not "<br />")
            $new_line   = '<br />' . "\n" . '            ';
            $query_base = htmlspecialchars($GLOBALS['sql_query']);
            $query_base = ereg_replace("((\015\012)|(\015)|(\012))+", $new_line, $query_base);
            if (!isset($GLOBALS['show_query']) || $GLOBALS['show_query'] != 'y') {
                if (!isset($GLOBALS['goto'])) {
                    $edit_target = (isset($GLOBALS['table'])) ? 'tbl_properties.php3' : 'db_details.php3';
                } else if ($GLOBALS['goto'] != 'main.php3') {
                    $edit_target = $GLOBALS['goto'];
                } else {
                    $edit_target = '';
                }
                if ($edit_target == 'tbl_properties.php3') {
                    $edit_link = '<a href="tbl_properties.php3?lang=' . $GLOBALS['lang'] . '&server=' . urlencode($GLOBALS['server']) . '&db=' . urlencode($GLOBALS['db']) . '&table=' . urlencode($GLOBALS['table']) . '&sql_query=' . urlencode($GLOBALS['sql_query']) . '&show_query=y">' . $GLOBALS['strEdit'] . '</a>';
                } else if ($edit_target != '') {
                    $edit_link = '<a href="db_details.php3?lang=' . $GLOBALS['lang'] . '&server=' . urlencode($GLOBALS['server']) . '&db=' . urlencode($GLOBALS['db']) . '&sql_query=' . urlencode($GLOBALS['sql_query']) . '&show_query=y">' . $GLOBALS['strEdit'] . '</a>';
                }
            }
            if (!empty($edit_target)) {
                echo '            ' . $GLOBALS['strSQLQuery'] . '&nbsp;:&nbsp;[' . $edit_link . ']<br />' . "\n";
            } else {
                echo '            ' . $GLOBALS['strSQLQuery'] . '&nbsp;:<br />' . "\n";
            }
            echo '            ' . $query_base;
            // If a 'LIMIT' clause has been programatically added to the query
            // displays it
            if (!empty($GLOBALS['sql_limit_to_append'])) {
                echo $GLOBALS['sql_limit_to_append'];
            }
            echo "\n";
            ?>
        </td>
    </tr>
           <?php
        }
        echo "\n";
        ?>
    </table>
</div><br />
        <?php
    } // end of the 'show_message()' function


    /**
     * Displays a link to the official MySQL documentation
     *
     * @param   string  an anchor to move to
     *
     * @return  string  the html link
     *
     * @access  public
     */
    function show_docu($link)
    {
        if (!empty($GLOBALS['cfgManualBase'])) {
            return '[<a href="' . $GLOBALS['cfgManualBase'] . '/' . $link .'" target="mysql_doc">' . $GLOBALS['strDocu'] . '</a>]';
        }
    } // end of the 'show_docu()' function


    /**
     * Formats $value to byte view
     *
     * @param    double   the value to format
     * @param    integer  the sensitiveness
     * @param    integer  the number of decimals to retain
     *
     * @return   array    the formatted value and its unit
     *
     * @access  public
     *
     * @author   staybyte
     * @version  1.1 - 07 July 2001
     */
    function format_byte_down($value, $limes = 6, $comma = 0)
    {
        $dh           = pow(10, $comma);
        $li           = pow(10, $limes);
        $return_value = $value;
        $unit         = $GLOBALS['byteUnits'][0];

        if ($value >= $li*1000000) {
            $value = round($value/(1073741824/$dh))/$dh;
            $unit  = $GLOBALS['byteUnits'][3];
        }
        else if ($value >= $li*1000) {
            $value = round($value/(1048576/$dh))/$dh;
            $unit  = $GLOBALS['byteUnits'][2];
        }
        else if ($value >= $li) {
            $value = round($value/(1024/$dh))/$dh;
            $unit  = $GLOBALS['byteUnits'][1];
        }
        if ($unit != $GLOBALS['byteUnits'][0]) {
            $return_value = number_format($value, $comma, $GLOBALS['number_decimal_separator'], $GLOBALS['number_thousands_separator']);
        } else {
            $return_value = number_format($value, 0, $GLOBALS['number_decimal_separator'], $GLOBALS['number_thousands_separator']);
        }

        return array($return_value, $unit);
    } // end of the 'format_byte_down' function


    /**
     * Ensures a database/table/field's name is not a reserved word (for MySQL
     * releases < 3.23.6) 
     *
     * @param    string   the name to check
     * @param    string   the url to go back in case of error
     *
     * @return   boolean  true if the name is valid (no return else)
     *
     * @access  public
     *
     * @author   Dell'Aiera Pol; Olivier Blin
     */
    function check_reserved_words($the_name, $error_url)
    {
        // The name contains caracters <> a-z, A-Z and "_" -> not a reserved
        // word
        if (!ereg('^[a-zA-Z_]+$', $the_name)) {
            return true;
        }
        
        // Else do the work
        $filename = 'badwords.txt';
        if (file_exists($filename)) {
            // Builds the reserved words array
            $fd        = fopen($filename, 'r');
            $contents  = fread($fd, filesize($filename) - 1);
            fclose ($fd);
            $word_list = explode("\n", $contents);

            // Do the checking
            $word_cnt  = count($word_list);
            for ($i = 0; $i < $word_cnt; $i++) {
                if (strtolower($the_name) == $word_list[$i]) {
                    mysql_die(sprintf($GLOBALS['strInvalidName'], $the_name), '', FALSE, $error_url);
                } // end if
            } // end for
        } // end if
    } // end of the 'check_reserved_words' function


    /**
     * Writes localised date
     *
     * @param   string   the current timestamp
     *
     * @return  string   the formatted date
     *
     * @access  public
     */
    function localised_date($timestamp = -1)
    {
        global $datefmt, $month, $day_of_week;

        if ($timestamp == -1) {
            $timestamp = time();
        }

        $date = ereg_replace('%[aA]', $day_of_week[(int)strftime('%w',$timestamp)], $datefmt);
        $date = ereg_replace('%[bB]', $month[(int)strftime('%m',$timestamp)], $date);

        return strftime($date, $timestamp);
    } // end of the 'localised_date()' function

} // $__LIB_COMMON__
?>
