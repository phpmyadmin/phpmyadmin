<?php
/* $Id$ */


if (!defined('__LIB_INC__')){
    define('__LIB_INC__', 1);

    /**
     * Order of sections for lib.inc.php3:
     *
     * in PHP3, functions and constants must be physically defined
     * before they are referenced
     *
     * some functions need the constants of defines.inc.php3
     *
     * the include of defines.inc.php3 must be after the connection to db to
     * get the MySql version
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
     * - first load of the define.lib.php3 library (won't get the MySQL
     *   release number)
     * - load of mysql extension (if necessary)
     * - definition of mysql_die()
     * - db connection
     * - second load of the define.lib.php3 library to get the MySQL release
     *   number)
     * - other functions, respecting dependencies 
     */


    /**
     * Avoids undefined variables in PHP3
     */
    if (!isset($use_backquotes)) {
        $use_backquotes = 0;
    }
    if (!isset($pos)) {
        $pos            = 0;
    }
    if (!isset($cfgProtectBlob)) {
        $cfgProtectBlob = FALSE;
    }


    /**
     * Advanced authentication work
     * Requires Apache loaded as a php module.
     */
    function auth()
    {
        header('WWW-Authenticate: Basic realm="phpMyAdmin ' . trim($GLOBALS['strRunning']) . ' ' . $GLOBALS['cfgServer']['host'] . '"');
        header('HTTP/1.0 401 Unauthorized');
        header('status: 401 Unauthorized');
        ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html>

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
    // If zlib output compression is set in the php configuration file, no
    // output buffering should be run
    if (@function_exists('ini_get') && @ini_get('zlib.output_compression')) {
        $cfgOBGzip = FALSE;
    }
    // Gets some constants
    include('./defines.inc.php3');



    /**
     * Loads the mysql extensions if it is not loaded yet
     * staybyte - 26. June 2001
     */
    if (PHP_INT_VERSION > 30009
        && (!@get_cfg_var('safe_mode') && @function_exists('dl'))) {
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
     * @param   boolean  whether to show a "back" link or not
     */
    function mysql_die($error_message = '', $the_query = '',
                       $is_modify_link = TRUE, $is_back_link = TRUE)
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
        if ($is_back_link) {
            $hist = (isset($GLOBALS['btnDrop'])) ? -2 : -1;
            echo '<a href="#" onclick="window.history.go(' . $hist . '); return false">' . $GLOBALS['strBack'] . '</a>';
        }
        echo "\n";

        include('./footer.inc.php3');
        exit();
    } // end of the 'mysql_die()' function


    /**
     * Use mysql_connect() or mysql_pconnect()?
     */
    $connect_func = ($cfgPersistentConnections) ? 'mysql_pconnect' : 'mysql_connect';
    $dblist = array();


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
                    mysql_die($conn_error, $local_query, FALSE, FALSE);
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
                $rs         = mysql_query($auth_query, $dbh) or mysql_die('', $auth_query, FALSE, FALSE);

                // Invalid login -> relog
                if (@mysql_numrows($rs) <= 0) {
                    auth();
                }
                // Seems to be a valid login...
                else {
                    $row = mysql_fetch_array($rs);
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
                        $local_query = 'SELECT DISTINCT Db FROM mysql.db WHERE Select_priv = \'Y\' AND User = \'' . $PHP_AUTH_USER . '\'';
                        $rs          = mysql_query($local_query) or mysql_die('', $local_query, FALSE, FALSE);
                        if (@mysql_numrows($rs) <= 0) {
                            $local_query = 'SELECT DISTINCT Db FROM mysql.tables_priv WHERE Table_priv LIKE \'%Select%\' AND User = \'' . $PHP_AUTH_USER . '\'';
                            $rs          = mysql_query($local_query) or mysql_die('', $local_query, FALSE, FALSE);
                            if (@mysql_numrows($rs) <= 0) {
                                auth();
                            } else {
                                while ($row = mysql_fetch_array($rs)) {
                                    $dblist[] = $row['Db'];
                                }
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
            mysql_die($conn_error, $local_query, FALSE, FALSE);
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
    include('./defines.inc.php3');



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
     * @version 1.0
     *
     * @access  public
     */
    function set_font_sizes()
    {
        global $font_size, $font_bigger, $font_smaller, $font_smallest;

        // IE for win case: needs smaller fonts than anyone else
        if (USR_OS == 'Win'
            && (USR_BROWSER_AGENT == 'IE' || USR_BROWSER_AGENT == 'OPERA')) {
            $font_size     = 'x-small';
            $font_bigger   = 'large ';
            // Unreadable
            // $font_smaller  = 'xx-small';
            $font_smaller  = '90%';
            $font_smallest = '7pt';
        }
        // Other browsers for win case
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
     */
    function js_format($a_string = '', $add_backquotes = TRUE)
    {
        $a_string = str_replace('"', '&quot;', $a_string);
        $a_string = str_replace('#', '\\#', addslashes($a_string));
        return (($add_backquotes) ? backquote($a_string) : $a_string);
    } // end of the 'sql_addslashes()' function


    /**
     * Defines the <CR><LF> value depending on the user OS that may be grabbed
     * from the 'HTTP_USER_AGENT' variable.
     *
     * @return  string   the <CR><LF> value to use
     */
    function which_crlf()
    {
        $the_crlf = "\n";
        
        // Gets the 'HTTP_USER_AGENT' variable
        if (!isset($GLOBALS['HTTP_USER_AGENT'])) {
            if (!empty($GLOBALS['HTTP_SERVER_VARS']) && isset($GLOBALS['HTTP_SERVER_VARS']['HTTP_USER_AGENT'])) {
                $GLOBALS['HTTP_USER_AGENT'] = $GLOBALS['HTTP_SERVER_VARS']['HTTP_USER_AGENT'];
            } else {
                $GLOBALS['HTTP_USER_AGENT'] = @getenv('HTTP_USER_AGENT');
            }
        } // end if
        
        // Searches for the word 'win'
        if (!empty($GLOBALS['HTTP_USER_AGENT'])
            && ereg('[^(]*\((.*)\)[^)]*', $GLOBALS['HTTP_USER_AGENT'], $regs)) {
            if (eregi('Win', $regs[1])) {
                $the_crlf = "\r\n";
            }
        } // end if

    
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
     */
    function count_records($db, $table, $ret = FALSE)
    {
        $result = mysql_query('select count(*) as num from ' . backquote($db) . '.' . backquote($table));
        $num    = mysql_result($result,0,"num");
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
     * @param  string  the message to display
     */
    function show_message($message)
    {
        // Reloads the navigation frame via JavaScript if required
        if (!empty($GLOBALS['reload']) && ($GLOBALS['reload'] == 'true')) {
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
    <table border="<?php echo $GLOBALS['cfgBorder']; ?>">
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
                if (isset($GLOBALS['goto']) && $GLOBALS['goto'] == 'tbl_properties.php3') {
                    $edit_link = '<a href="tbl_properties.php3?lang=' . $GLOBALS['lang'] . '&server=' . urlencode($GLOBALS['server']) . '&db=' . urlencode($GLOBALS['db']) . '&table=' . urlencode($GLOBALS['table']) . '&sql_query=' . urlencode($GLOBALS['sql_query']) . '&show_query=y">' . $GLOBALS['strEdit'] . '</a>';
                } else {
                    $edit_link = '<a href="db_details.php3?lang=' . $GLOBALS['lang'] . '&server=' . urlencode($GLOBALS['server']) . '&db=' . urlencode($GLOBALS['db']) . '&sql_query=' . urlencode($GLOBALS['sql_query']) . '&show_query=y">' . $GLOBALS['strEdit'] . '</a>';
                }
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
            // loic1 : this was far to be optimal
            // $is_append_limit = (isset($GLOBALS['pos'])
            //                     && (eregi('^SELECT', $GLOBALS['sql_query']) && !eregi('^SELECT COUNT\((.*\.+)?\*\) FROM ', $GLOBALS['sql_query']))
            //                     && !eregi('LIMIT[ 0-9,]+$', $GLOBALS['sql_query']));
            // if ($is_append_limit) {
            //     echo ' LIMIT ' . $GLOBALS['pos'] . ', ' . $GLOBALS['cfgMaxRows'];
            // }
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
     *
     * @return   boolean  true if the name is valid (no return else)
     *
     * @author   Dell'Aiera Pol; Olivier Blin
     */
    function check_reserved_words($the_name)
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
                    mysql_die(sprintf($GLOBALS['strInvalidName'], $the_name), '', FALSE, TRUE);
                } // end if
            } // end for
        } // end if
    } // end of the 'check_reserved_words' function


    /**
     * Writes localised date
     *
     * @param   timestamp   time
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



    /* ----- Functions used to display records returned by a sql query ----- */

    /**
     * Defines the display mode to use for the results of a sql query
     *
     * It uses a syntetic string that contains all the required informations.
     * In this string:
     *   - the first two characters stand for the the action to do while
     *     clicking on the "edit" link (eg 'ur' for update a row, 'nn' for no
     *     edit link...);
     *   - the next two characters stand for the the action to do while
     *     clicking on the "delete" link (eg 'kp' for kill a process, 'nn' for
     *     no delete link...);
     *   - the next characters are boolean values (1/0) and respectively stand
     *     for sorting links, navigation bar, "insert a new row" link, the
     *     bookmark feature and the expand/collapse text/blob fields button.
     *     Of course '0'/'1' means the feature won't/will be enabled.
     *
     * @param   string   the synthetic value for display_mode (see §1 a few
     *                   lines below for explanations)
     * @global  integer  the total number of rows returned by the sql query
     *                   without any programmatically appended "LIMIT" clause
     *                   that may be computed inside this function
     *
     * @return  array    an array with explicit indexes for all the display
     *                   elements
     *
     * @global  string   the database name
     * @global  string   the table name
     * @global  integer  the total number of rows returned by the sql query
     *                   without any programmatically appended "LIMIT" clause
     * @global  array    the properties of the fields returned by the query
     *
     * @access	private
     *
     * @see display_table()
     */
    function set_display_mode(&$the_disp_mode, &$the_total)
    {
        global $db, $table;
        global $unlim_num_rows, $fields_meta;

        // 1. Initializes the $do_display array
        $do_display              = array();
        $do_display['edit_lnk']  = $the_disp_mode[0] . $the_disp_mode[1];
        $do_display['del_lnk']   = $the_disp_mode[2] . $the_disp_mode[3];
        $do_display['sort_lnk']  = (string) $the_disp_mode[4];
        $do_display['nav_bar']   = (string) $the_disp_mode[5];
        $do_display['ins_row']   = (string) $the_disp_mode[6];
        $do_display['bkm_form']  = (string) $the_disp_mode[7];
        $do_display['text_btn']  = (string) $the_disp_mode[8];

        // 2. Display mode is not "false for all elements" -> updates the
        // display mode
        if ($the_disp_mode != 'nnnn00000') {
            // 2.1 Statement is a "SELECT COUNT", 
            //     "CHECK/ANALYZE/REPAIR/OPTIMIZE" or an "EXPLAIN"
            if ($GLOBALS['is_count'] || $GLOBALS['is_maint'] || $GLOBALS['is_explain']) {
                $do_display['edit_lnk']  = 'nn'; // no edit link
                $do_display['del_lnk']   = 'nn'; // no delete link
                $do_display['sort_lnk']  = (string) '0';
                $do_display['nav_bar']   = (string) '0';
                $do_display['ins_row']   = (string) '0';
                $do_display['bkm_form']  = (string) '1';
                $do_display['text_btn']  = (string) '0';
            }
            // 2.2 Statement is a "SHOW..."
            else if ($GLOBALS['is_show']) {
                // 2.2.1 TODO : defines edit/delete links depending on show statement
                $tmp = eregi('^SHOW[[:space:]]+(VARIABLES|PROCESSLIST|STATUS|TABLE|GRANTS|CREATE|LOGS)', $GLOBALS['sql_query'], $which);
                if (strtoupper($which[1]) == 'PROCESSLIST') {
                    $do_display['edit_lnk'] = 'nn'; // no edit link
                    $do_display['del_lnk']  = 'kp'; // "kill process" type edit link
                }
                else {
                    // Default case -> no links
                    $do_display['edit_lnk'] = 'nn'; // no edit link
                    $do_display['del_lnk']  = 'nn'; // no delete link
                }
                // 2.2.2 Other settings
                $do_display['sort_lnk']  = (string) '0';
                $do_display['nav_bar']   = (string) '0';
                $do_display['ins_row']   = (string) '0';
                $do_display['bkm_form']  = (string) '1';
                $do_display['text_btn']  = (string) '0';
            }
            // 2.3 Other statements (ie "SELECT" ones) -> updates
            //     $do_display['edit_lnk'], $do_display['del_lnk'] and
            //     $do_display['text_btn'] (keeps other default values)
            else {
                $prev_table = $fields_meta[0]->table;
                for ($i = 0; $i < $GLOBALS['fields_cnt']; $i++) {
                    $is_link = ($do_display['edit_lnk'] != 'nn'
                                || $do_display['del_lnk'] != 'nn'
                                || $do_display['sort_lnk'] != '0'
                                || $do_display['ins_row'] != '0');
                    // 2.3.1 Displays text cut/expand button?
                    if ($do_display['text_btn'] == '0' && eregi('BLOB', $fields_meta[$i]->type)) {
                        $do_display['text_btn'] = (string) '1';
                        if (!$is_link) {
                            break;
                        }
                    } // end if (2.3.1)
                    // 2.3.2 Displays edit/delete/sort/insert links?
                    if ($is_link
                        && ($fields_meta[$i]->table == '' || $fields_meta[$i]->table != $prev_table)) {
                        $do_display['edit_lnk'] = 'nn'; // don't display links
                        $do_display['del_lnk']  = 'nn';
                        // TODO: May be problematic with same fields names in
                        //       two joined table.
                        // $do_display['sort_lnk'] = (string) '0';
                        $do_display['ins_row']   = (string) '0';
                        if ($do_display['text_btn'] == '1') {
                            break;
                        }
                    } // end if (2.3.2)
                    $prev_table = $fields_meta[$i]->table;
                } // end for
            } // end if..elseif...else (2.1 -> 2.3)
        } // end if (2)

        // 3. Gets the total number of rows if it is unknown
        if (isset($unlim_num_rows) && $unlim_num_rows != '') {
            $the_total = $unlim_num_rows;
        }
        else if (($do_display['nav_bar'] == '1' || $do_display['sort_lnk'] == '1')
                 && (!empty($db) && !empty($table))) {
            $local_query = 'SELECT COUNT(*) AS total FROM ' . backquote($db) . '.' . backquote($table);
            $result      = mysql_query($local_query) or mysql_die('', $local_query);
            $the_total   = mysql_result($result, 0, 'total');
        }
        
        // 4. If navigation bar or sorting fields names urls should be
        //    displayed but there is only one row, change these settings to
        //    false
        if ($do_display['nav_bar'] == '1' || $do_display['sort_lnk'] == '1') {
            if (isset($unlim_num_rows) && $unlim_num_rows < 2) {
                $do_display['nav_bar']  = (string) '0';
                $do_display['sort_lnk'] = (string) '0';
            }
        } // end if (3)

        // 5. Updates the synthetic var
        $the_disp_mode = join('', $do_display);

        return $do_display;
    } // end of the 'set_display_mode()' function


    /**
     * Displays a navigation bar to browse among the results of a sql query
     *
     * @param   integer  the offset for the "next" page
     * @param   integer  the offset for the "previous" page
     * @param   string   the url-encoded query
     *
     * @global  string   the current language
     * @global  integer  the server to use (refers to the number in the
     *                   configuration file)
     * @global  string   the database name
     * @global  string   the table name
     * @global  string   the url to go back in case of errors
     * @global  integer  the total number of rows returned by the sql query
     * @global  integer  the total number of rows returned by the sql query
     *                   without any programmatically appended "LIMIT" clause
     * @global  integer  the current position in results
     * @global  integer  the maximum number of rows per page
     * @global  boolean  whether to limit the number of displayed characters of
     *                   text type fields or not
     *
     * @access	private
     *
     * @see display_table()
     */
    function display_table_navigation($pos_next, $pos_prev, $encoded_query)
    {
        global $lang, $server, $db, $table;
        global $goto;
        global $num_rows, $unlim_num_rows, $pos, $sessionMaxRows;
        global $dontlimitchars;
        ?>

<!-- Navigation bar -->
<table border="0">
<tr>
        <?php
        // Move to the beginning or to the previous page
        if ($pos > 0) {
            ?>
    <td>
        <form action="sql.php3" method="post">
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
            <input type="hidden" name="sql_query" value="<?php echo $encoded_query; ?>" />
            <input type="hidden" name="pos" value="0" />
            <input type="hidden" name="sessionMaxRows" value="<?php echo $sessionMaxRows; ?>" />
            <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
            <input type="hidden" name="dontlimitchars" value="<?php echo $dontlimitchars; ?>" />
            <input type="submit" name="navig" value="<?php echo $GLOBALS['strPos1'] . ' &lt;&lt;'; ?>" />
        </form>
    </td>
    <td>
        <form action="sql.php3" method="post">
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
            <input type="hidden" name="sql_query" value="<?php echo $encoded_query; ?>" />
            <input type="hidden" name="pos" value="<?php echo $pos_prev; ?>" />
            <input type="hidden" name="sessionMaxRows" value="<?php echo $sessionMaxRows; ?>" />
            <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
            <input type="hidden" name="dontlimitchars" value="<?php echo $dontlimitchars; ?>" />
            <input type="submit" name="navig" value="<?php echo $GLOBALS['strPrevious'] . ' &lt;'; ?>" />
        </form>
    </td>
            <?php
        } // end move back
        echo "\n";
        ?>
    <td>
        &nbsp;&nbsp;&nbsp;
    </td>
    <td>
        <form action="sql.php3" method="post"
            onsubmit="return (checkFormElementInRange(this, 'sessionMaxRows', 1) && checkFormElementInRange(this, 'pos', 0, <?php echo $unlim_num_rows - 1; ?>))">
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
            <input type="hidden" name="sql_query" value="<?php echo $encoded_query; ?>" />
            <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
            <input type="hidden" name="dontlimitchars" value="<?php echo $dontlimitchars; ?>" />
            <input type="submit" name="navig" value="<?php echo $GLOBALS['strShow']; ?>&nbsp;:" />
            <input type="text" name="sessionMaxRows" size="3" value="<?php echo $sessionMaxRows; ?>" />
            <?php echo $GLOBALS['strRowsFrom'] . "\n"; ?>
            <input type="text" name="pos" size="3" value="<?php echo (($pos_next >= $unlim_num_rows) ? 0 : $pos_next); ?>" />
        </form>
    </td>
    <td>
        &nbsp;&nbsp;&nbsp;
    </td>
        <?php
        // Move to the next page or to the last one
        if (($pos + $sessionMaxRows < $unlim_num_rows) && $num_rows >= $sessionMaxRows) {
            ?>
    <td>
        <form action="sql.php3" method="post">
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
            <input type="hidden" name="sql_query" value="<?php echo $encoded_query; ?>" />
            <input type="hidden" name="pos" value="<?php echo $pos_next; ?>" />
            <input type="hidden" name="sessionMaxRows" value="<?php echo $sessionMaxRows; ?>" />
            <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
            <input type="hidden" name="dontlimitchars" value="<?php echo $dontlimitchars; ?>" />
            <input type="submit" name="navig" value="<?php echo '&gt; ' . $GLOBALS['strNext']; ?>" />
        </form>
    </td>
    <td>
        <form action="sql.php3" method="post"
            onsubmit="return <?php echo (($pos + $sessionMaxRows < $unlim_num_rows && $num_rows >= $sessionMaxRows) ? 'true' : 'false'); ?>">
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
            <input type="hidden" name="sql_query" value="<?php echo $encoded_query; ?>" />
            <input type="hidden" name="pos" value="<?php echo $unlim_num_rows - $sessionMaxRows; ?>" />
            <input type="hidden" name="sessionMaxRows" value="<?php echo $sessionMaxRows; ?>" />
            <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
            <input type="hidden" name="dontlimitchars" value="<?php echo $dontlimitchars; ?>" />
            <input type="submit" name="navig" value="<?php echo '&gt;&gt; ' . $GLOBALS['strEnd']; ?>" />
        </form>
    </td>
            <?php
        } // end move toward
        echo "\n";
        ?>
</tr>
</table>

        <?php
    } // end of the 'display_table_navigation()' function


    /**
     * Displays the headers of the results table
     *
     * @param   array    which elements to display
     * @param   array    the list of fields properties
     * @param   integer  the total number of fields returned by the sql query
     * @param   string   the url-encoded sql query
     *
     * @return  boolean  always true
     *
     * @global  string   the current language
     * @global  integer  the server to use (refers to the number in the
     *                   configuration file)
     * @global  string   the database name
     * @global  string   the table name
     * @global  string   the sql query
     * @global  string   the url to go back in case of errors
     * @global  integer  the total number of rows returned by the sql query
     * @global  integer  the current position in results
     * @global  integer  the maximum number of rows per page
     * @global  boolean  whether to limit the number of displayed characters of
     *                   text type fields or not
     *
     * @access	private
     *
     * @see display_table()
     */
    function display_table_headers(&$is_display, &$fields_meta, $fields_cnt = 0, $encoded_query = '')
    {
        global $lang, $server, $db, $table;
        global $goto;
        global $sql_query, $num_rows, $pos, $sessionMaxRows;
        global $dontlimitchars;

        ?>
<!-- Results table headers -->
<tr>
        <?php
        echo "\n";
        
        // 1. Displays the full/partial text button (part 1)...
        $colspan  = ($is_display['edit_lnk'] != 'nn' && $is_display['del_lnk'] != 'nn')
                  ? ' colspan="2"'
                  : '';
        $text_url = 'sql.php3'
                  . '?lang=' . $lang
                  . '&server=' . $server
                  . '&db=' . urlencode($db)
                  . '&table=' . urlencode($table)
                  . '&sql_query=' . $encoded_query
                  . '&pos=' . $pos
                  . '&sessionMaxRows=' . $sessionMaxRows
                  . '&pos=' . $pos
                  . '&goto=' . $goto
                  . '&dontlimitchars=' . (($dontlimitchars) ? 0 : 1);

        //     ... before the result table
        if (($is_display['edit_lnk'] == 'nn' && $is_display['del_lnk'] == 'nn')
            && $is_display['text_btn'] == '1') {
            ?>
    <td colspan="<?php echo $fields_cnt; ?>" align="center">
        <a href="<?php echo $text_url; ?>">
            <img src="./images/<?php echo (($dontlimitchars) ? 'partialtext' : 'fulltext'); ?>.png" border="0" width="50" height="20" alt="<?php echo (($dontlimitchars) ? $GLOBALS['strPartialText'] : $GLOBALS['strFullText']); ?>" /></a>
    </td>
</tr>

<tr>
            <?php
        }
        //     ... at the left column of the result table header if possible
        //     and required
        else if ($GLOBALS['cfgModifyDeleteAtLeft'] && $is_display['text_btn'] == '1') {
            echo "\n";
            ?>
    <td<?php echo $colspan; ?>" align="center">
        <a href="<?php echo $text_url; ?>">
            <img src="./images/<?php echo (($dontlimitchars) ? 'partialtext' : 'fulltext'); ?>.png" border="0" width="50" height="20" alt="<?php echo (($dontlimitchars) ? $GLOBALS['strPartialText'] : $GLOBALS['strFullText']); ?>" /></a>
    </td>
            <?php
        }
        //     ... else if no button, displays empty(ies) col(s) if required
        else if ($GLOBALS['cfgModifyDeleteAtLeft']
                 && ($is_display['edit_lnk'] != 'nn' || $is_display['del_lnk'] != 'nn')) {
            echo "\n";
            ?>
    <td<?php echo $colspan; ?>></td>
            <?php
        }
        echo "\n";

        // 2. Displays the fields' name
        // 2.0 If sorting links should be used, checks if the query is a "JOIN"
        //     statement (see 2.1.3)
        if ($is_display['sort_lnk'] == '1') {
            $is_join = eregi('(.*)[[:space:]]+FROM[[:space:]]+.*[[:space:]]+JOIN', $sql_query, $select_stt);
        } else {
            $is_join    = FALSE;
        }
        for ($i = 0; $i < $fields_cnt; $i++) {

            // 2.1 Results can be sorted
            if ($is_display['sort_lnk'] == '1') {
                // Defines the url used to append/modify a sorting order
                // 2.1.1 Checks if an hard coded 'order by' clause exists
                if (eregi('(.*)( ORDER BY (.*))', $sql_query, $regs1)) {
                    if (eregi('((.*)( ASC| DESC)( |$))(.*)', $regs1[2], $regs2)) {
                        $unsorted_sql_query = trim($regs1[1] . ' ' . $regs2[5]);
                        $sql_order          = trim($regs2[1]);
                    }
                    else if (eregi('((.*)) (LIMIT (.*)|PROCEDURE (.*)|FOR UPDATE|LOCK IN SHARE MODE)', $regs1[2], $regs3)) {
                        $unsorted_sql_query = trim($regs1[1] . ' ' . $regs3[3]);
                        $sql_order          = trim($regs3[1]) . ' ASC';
                    } else {
                        $unsorted_sql_query = trim($regs1[1]);
                        $sql_order          = trim($regs1[2]) . ' ASC';
                    }
                } else {
                    $unsorted_sql_query     = $sql_query;
                }
                // 2.1.2 Checks if the current column is used to sort the
                //       results
                if (empty($sql_order)) {
                    $is_in_sort = FALSE;
                } else {
                    $is_in_sort = eregi(' (`?)' . str_replace('\\', '\\\\', $fields_meta[$i]->name) . '(`?)[ ,$]', $sql_order);
                }
                // 2.1.3 Checks if the table name is required (it's the case
                //       for a query with a "JOIN" statement and if the column
                //       isn't aliased)
                if ($is_join
                    && !eregi('([^[:space:],]|`[^`]`)[[:space:]]+(as[[:space:]]+)?' . $fields_meta[$i]->name, $select_stt[1], $parts)) {
                    $sort_tbl = backquote($fields_meta[$i]->table) . '.';
                } else {
                    $sort_tbl = '';
                }
                // 2.1.4 Do define the sorting url
                if (!$is_in_sort) {
                    // loic1: patch #455484 ("Smart" order)
                    $cfgOrder     = strtoupper($GLOBALS['cfgOrder']);
                    if ($cfgOrder == 'SMART') {
                        $cfgOrder = (eregi('time|date', $fields_meta[$i]->type)) ? 'DESC' : 'ASC';
                    }
                    $sort_order = ' ORDER BY ' . $sort_tbl . backquote($fields_meta[$i]->name) . ' ' . $cfgOrder;
                    $order_img  = '';
                }
                else if (substr($sql_order, -3) == 'ASC' && $is_in_sort) {
                    $sort_order = ' ORDER BY ' . $sort_tbl . backquote($fields_meta[$i]->name) . ' DESC';
                    $order_img  = '&nbsp;<img src="./images/asc_order.gif" border="0" width="7" height="7" alt="ASC" />';
                }
                else if (substr($sql_order, -4) == 'DESC' && $is_in_sort) {
                    $sort_order = ' ORDER BY ' . $sort_tbl . backquote($fields_meta[$i]->name) . ' ASC';
                    $order_img  = '&nbsp;<img src="./images/desc_order.gif" border="0" width="7" height="7" alt="DESC" />';
                }
                if (eregi('(.*)( LIMIT (.*)| PROCEDURE (.*)| FOR UPDATE| LOCK IN SHARE MODE)', $unsorted_sql_query, $regs3)) {
                    $sorted_sql_query = $regs3[1] . $sort_order . $regs3[2];
                } else {
                    $sorted_sql_query = $unsorted_sql_query . $sort_order;
                }
                $url_query = 'lang=' . $lang
                           . '&server=' . $server
                           . '&db=' . urlencode($db)
                           . '&table=' . urlencode($table)
                           . '&pos=' . $pos
                           . '&sessionMaxRows=' . $sessionMaxRows
                           . '&dontlimitchars' . $dontlimitchars
                           . '&sql_query=' . urlencode($sorted_sql_query);
                // 2.1.5 Displays the sorting url
                ?>
    <th>
        <a href="sql.php3?<?php echo $url_query; ?>">
            <?php echo htmlspecialchars($fields_meta[$i]->name); ?></a><?php echo $order_img . "\n"; ?>
    </th>
                <?php
            } // end if (2.1)

            // 2.2 Results can't be sorted
            else {
                echo "\n";
                ?>
    <th>
        <?php echo htmlspecialchars($fields_meta[$i]->name) . "\n"; ?>
    </th>
                <?php
            } // end else (2.2)
            echo "\n";
        } // end for

        // 3. Displays the full/partial text button (part 2) at the right
        //    column of the result table header if possible and required...
        if ($GLOBALS['cfgModifyDeleteAtRight']
            && ($is_display['edit_lnk'] != 'nn' || $is_display['del_lnk'] != 'nn')
            && $is_display['text_btn'] == '1') {
            echo "\n";
           ?>
    <td<?php echo $colspan; ?>" align="center">
        <a href="<?php echo $text_url; ?>">
            <img src="./images/<?php echo (($dontlimitchars) ? 'partialtext' : 'fulltext'); ?>.png" border="0" width="50" height="20" alt="<?php echo (($dontlimitchars) ? $GLOBALS['strPartialText'] : $GLOBALS['strFullText']); ?>" /></a>
    </td>
            <?php
        }
        //     ... else if no button, displays empty cols if required
        else if ($GLOBALS['cfgModifyDeleteAtRight']
                 && ($is_display['edit_lnk'] == 'nn' && $is_display['del_lnk'] == 'nn')) {
            echo "\n" . '    <td' . $colspan . '></td>';
        }
        echo "\n";
        ?>
</tr>
        <?php
        echo "\n";

        return true;
    } // end of the 'display_table_headers()' function


    /**
     * Displays the body of the results table
     *
     * @param   integer  the link id associated to the query which results have
     *                   to be displayed
     * @param   array    which elements to display
     * @param   array    the list of fields properties
     * @param   integer  the total number of fields returned by the sql query
     *
     * @return  boolean  always true
     *
     * @global  string   the current language
     * @global  integer  the server to use (refers to the number in the
     *                   configuration file)
     * @global  string   the database name
     * @global  string   the table name
     * @global  string   the sql query
     * @global  string   the url to go back in case of errors
     * @global  integer  the current position in results
     * @global  integer  the maximum number of rows per page
     * @global  array    the list of fields properties
     * @global  integer  the total number of fields returned by the sql query
     * @global  boolean  whether to limit the number of displayed characters of
     *                   text type fields or not
     *
     * @access	private
     *
     * @see display_table()
     */
    function display_table_body(&$dt_result, &$is_display)
    {
        global $lang, $server, $db, $table;
        global $goto;
        global $sql_query, $pos, $sessionMaxRow, $fields_meta, $fields_cnt;
        global $dontlimitchars;

        ?>
<!-- Results table body -->
        <?php
        echo "\n";

        $foo = 0;

        // Correction uva 19991216 in the while below
        // Previous code assumed that all tables have keys, specifically that
        // the phpMyAdmin GUI should support row delete/edit only for such
        // tables.
        // Although always using keys is arguably the prescribed way of
        // defining a relational table, it is not required. This will in
        // particular be violated by the novice.
        // We want to encourage phpMyAdmin usage by such novices. So the code
        // below has been changed to conditionally work as before when the
        // table being displayed has one or more keys; but to display
        // delete/edit options correctly for tables without keys.

        while ($row = mysql_fetch_row($dt_result)) {
            $bgcolor                  = ($foo % 2) ? $GLOBALS['cfgBgcolorOne'] : $GLOBALS['cfgBgcolorTwo'];

            ?>
<tr bgcolor="<?php echo $bgcolor; ?>">
            <?php
            echo "\n";

            // 1. Prepares the row (gets primary keys to use)
            if ($is_display['edit_lnk'] != 'nn' || $is_display['del_lnk'] != 'nn') {
                $primary_key              = '';
                $uva_nonprimary_condition = '';

                // 1.1 Results from a "SELECT" statement -> builds the
                //     the "primary" key to use in links
                if ($is_display['edit_lnk'] == 'ur' /* || $is_display['edit_lnk'] == 'dr' */) {
                    for ($i = 0; $i < $fields_cnt; ++$i) {
                        $primary   = $fields_meta[$i];
                        $condition = ' ' . backquote($primary->name) . ' ';
                        if (!isset($row[$i])) {
                            $row[$i]   = '';
                            $condition .= 'IS NULL AND';
                        } else {
                            $condition .= '= \'' . sql_addslashes($row[$i]) . '\' AND';
                        }
                        if ($primary->primary_key > 0) {
                            $primary_key .= $condition;
                        }
                        $uva_nonprimary_condition .= $condition;
                    } // end for

                    // Correction uva 19991216: prefer primary keys for
                    // condition, but use conjunction of all values if no
                    // primary key
                    if ($primary_key) {
                        $uva_condition = $primary_key;
                    } else {
                        $uva_condition = $uva_nonprimary_condition;
                    }
                    $uva_condition     = urlencode(ereg_replace(' ?AND$', '', $uva_condition));
                } // end if (1.1)

                // 1.2 Results from a "SHOW PROCESSLIST" statement -> gets the
                //     process id
                else if ($is_display['del_lnk'] == 'kp') {
                    $pma_pid = $row[0];
                }

                // 1.2 Defines the urls for the modify/delete link(s)
                $url_query  = 'lang=' . $lang
                            . '&server=' . $server
                            . '&db=' . urlencode($db)
                            . '&table=' . urlencode($table)
                            . '&pos=' . $pos
                            . '&sessionMaxRow=' . $sessionMaxRow
                            . '&dontlimitchars=' . $dontlimitchars;

                // 1.2.1 Modify link(s)
                if ($is_display['edit_lnk'] == 'ur') { // update row case
                    if (!empty($goto)
                        && empty($GLOBALS['QUERY_STRING'])
                        && (empty($GLOBALS['HTTP_SERVER_VARS']) || empty($GLOBALS['HTTP_SERVER_VARS']['QUERY_STRING']))) {
                        // void
                    } else {
                        $goto = 'sql.php3';
                    }
                    $edit_url = 'tbl_change.php3'
                              . '?' . $url_query
                              . '&primary_key=' . $uva_condition
                              . '&sql_query=' . urlencode($sql_query)
                              . '&goto=' . urlencode($goto);
                    $edit_str = $GLOBALS['strEdit'];
                } // end if (1.2.1)

                // 1.2.2 Delete/Kill link(s)
                if ($is_display['del_lnk'] == 'dr') { // delete row case
                    $goto     = 'sql.php3'
                              . '?' . $url_query
                              . '&sql_query=' . urlencode($sql_query)
                              . '&zero_rows=' . urlencode(htmlspecialchars($GLOBALS['strDeleted']))
                              . '&goto=tbl_properties.php3';
                    $del_url  = 'sql.php3'
                              . '?' . $url_query
                              . '&sql_query=' . urlencode('DELETE FROM ' . backquote($table) . ' WHERE') . $uva_condition . urlencode(' LIMIT 1')
                              . '&zero_rows=' . urlencode(htmlspecialchars($GLOBALS['strDeleted']))
                              . '&goto=' . urlencode($goto);
                    $js_conf  = 'DELETE FROM ' . js_format($table)
                              . ' WHERE ' . trim(js_format(urldecode($uva_condition), FALSE)) . ' LIMIT 1';
                    $del_str  = $GLOBALS['strDelete'];
                } else if ($is_display['del_lnk'] == 'kp') { // kill process case
                    $del_url  = 'sql.php3'
                              . '?lang=' . $lang
                              . '&server=' . $server
                              . '&db=mysql'
                              . '&sql_query=' . urlencode('KILL ' . $pma_pid)
                              . '&goto=main.php3';
                    $js_conf  = 'KILL ' . $pma_pid;
                    $del_str  = $GLOBALS['strKill'];
                } // end if (1.2.1)

                // 1.2.3 Displays the links at left if required
                if ($GLOBALS['cfgModifyDeleteAtLeft']) {
                    if (!empty($edit_url)) {
                        ?>
    <td>
        <a href="<?php echo $edit_url; ?>">
            <?php echo $edit_str; ?></a>
    </td>
                        <?php
                    }
                    if (!empty($del_url)) {
                        echo "\n";
                        ?>
    <td>
        <a href="<?php echo $del_url; ?>"
            <?php if (isset($js_conf)) echo 'onclick="return confirmLink(this, \'' . $js_conf . '\')"'; ?>>
            <?php echo $del_str; ?></a>
    </td>
                        <?php
                    }
                } // end if (1.2.3)
                echo "\n";
            } // end if (1)

            // 2. Displays the rows' values
            for ($i = 0; $i < $fields_cnt; ++$i) {
                if (!isset($row[$i])) {
                    $row[$i] = '';
                }
                $primary = $fields_meta[$i];
                if ($primary->numeric == 1) {
                    if ($row[$i] != '') {
                        echo '    <td align="right">' . $row[$i] . '</td>' . "\n";
                    } else {
                        echo '    <td align="right">&nbsp;</td>' . "\n";
                    }
                } else if ($GLOBALS['cfgShowBlob'] == FALSE && eregi('BLOB', $primary->type)) {
                    // loic1 : mysql_fetch_fields returns BLOB in place of TEXT
                    // fields type, however TEXT fields must be displayed even
                    // if $cfgShowBlob is false -> get the true type of the
                    // fields.
                    $field_flags = mysql_field_flags($dt_result, $i);
                    if (eregi('BINARY', $field_flags)) {
                        echo '    <td align="center">[BLOB]</td>' . "\n";
                    } else {
                        if (strlen($row[$i]) > $GLOBALS['cfgLimitChars'] && ($dontlimitchars != 1)) {
                            $row[$i] = substr($row[$i], 0, $GLOBALS['cfgLimitChars']) . '...';
                        }
                        // loic1 : displays <cr>/<lf>
                        if ($row[$i] != '') {
                            $row[$i] = ereg_replace("((\015\012)|(\015)|(\012))+", '<br />', htmlspecialchars($row[$i]));
                            echo '    <td>' . $row[$i] . '</td>' . "\n";
                        } else {
                            echo '    <td>&nbsp;</td>' . "\n";
                        }
                    }
                } else {
                    // loic1 : displays <cr>/<lf>
                    if ($row[$i] != '') {
                        // loic1: Cut text/blob fields even if $cfgShowBlob is true
                        if (eregi('BLOB', $primary->type)) {
                            if (strlen($row[$i]) > $GLOBALS['cfgLimitChars'] && ($dontlimitchars != 1)) {
                                $row[$i] = substr($row[$i], 0, $GLOBALS['cfgLimitChars']) . '...';
                            }
                        }
                        $row[$i] = ereg_replace("((\015\012)|(\015)|(\012))+", '<br />', htmlspecialchars($row[$i]));
                        echo '    <td>' . $row[$i] . '</td>' . "\n";
                    } else {
                        echo '    <td>&nbsp;</td>' . "\n";
                    }
                }
            } // end for (2)

            // 3. Displays the modify/delete links on the right if required
            if ($GLOBALS['cfgModifyDeleteAtRight']) {
                if (!empty($edit_url)) {
                    ?>
    <td>
        <a href="<?php echo $edit_url; ?>">
            <?php echo $edit_str; ?></a>
    </td>
                    <?php
                }
                if (!empty($del_url)) {
                    echo "\n";
                    ?>
    <td>
        <a href="<?php echo $del_url; ?>"
            <?php if (isset($js_conf)) echo 'onclick="return confirmLink(this, \'' . $js_conf . '\')"'; ?>>
            <?php echo $del_str; ?></a>
    </td>
                    <?php
                }
            } // end if (3)
            ?>
</tr>
            <?php
            echo "\n";
            $foo++;
        } // end while

        return true;
    } // end of the 'display_table_body()' function


    /**
     * Displays a table of results returned by a sql query.
     * This function is called by the "sql.php3" script.
     *
     * @param   integer the link id associated to the query which results have
     *                  to be displayed
     * @param   array   the display mode
     *
     * @global  string   the current language
     * @global  integer  the server to use (refers to the number in the
     *                   configuration file)
     * @global  string   the database name
     * @global  string   the table name
     * @global  string   the url to go back in case of errors
     * @global  string   the current sql query
     * @global  integer  the total number of rows returned by the sql query
     * @global  integer  the total number of rows returned by the sql query
     *                   without any programmatically appended "LIMIT" clause
     * @global  integer  the current postion of the first record to be
     *                   displayed
     * @global  array    the list of fields properties
     * @global  integer  the total number of fields returned by the sql query
     * @global  boolean  whether to limit the number of displayed characters of
     *                   text type fields or not
     *
     * @access	private
     *
     * @see     show_message(), set_display_mode(), display_table_navigation(),
     *          display_table_headers(), display_table_body()
     */
    function display_table(&$dt_result, &$the_disp_mode)
    {
        global $lang, $server, $db, $table;
        global $goto;
        global $sql_query, $num_rows, $unlim_num_rows, $pos, $fields_meta, $fields_cnt;
        global $dontlimitchars;

        // 1. ----- Prepares the work -----

        // 1.1 Gets the number of rows per page
        if (isset($GLOBALS['sessionMaxRows'])) {
            $GLOBALS['cfgMaxRows']     = $GLOBALS['sessionMaxRows'];
        } else {
            $GLOBALS['sessionMaxRows'] = $GLOBALS['cfgMaxRows'];
        }

        // 1.2 Gets the informations about which functionnalities should be
        //     displayed
        $total      = '';
        $is_display = set_display_mode($the_disp_mode, $total);
        if ($total == '') {
            unset($total);
        }

        // 1.3 Defines offsets for the next and previous pages
        if ($is_display['nav_bar'] == '1') {
            if (!isset($pos)) {
                $pos      = 0;
            }
            $pos_next     = $pos + $GLOBALS['cfgMaxRows'];
            $pos_prev     = $pos - $GLOBALS['cfgMaxRows'];
            if ($pos_prev < 0) {
                $pos_prev = 0;
            }
        } // end if

        // 1.4 Urlencodes the query to use in input form fields ($sql_query
        //     will be stripslashed in 'sql.php3' if the 'magic_quotes_gpc'
        //     directive is set to 'on')
        if (get_magic_quotes_gpc()) {
            $encoded_sql_query = urlencode(addslashes($sql_query));
        } else {
            $encoded_sql_query = urlencode($sql_query);
        }

        // 2. ----- Displays the top of the page -----

        // 2.1 Displays a messages with position informations
        if ($is_display['nav_bar'] == '1' && isset($pos_next)) {
            if (isset($unlim_num_rows) && $unlim_num_rows != $total) {
                $selectstring = ', ' . $unlim_num_rows . ' ' . $GLOBALS['strSelectNumRows'];
            } else {
                $selectstring = '';
            }
            $last_shown_rec = ($pos_next > $total) ? $total : $pos_next;
            show_message($GLOBALS['strShowingRecords'] . " $pos - $last_shown_rec ($total " . $GLOBALS['strTotal'] . $selectstring . ')');
        } else {
            show_message($GLOBALS['strSQLQuery']);
        }

        // 2.3 Displays the navigation bars
        if (!isset($table) || strlen(trim($table)) == 0) {
            $table = $fields_meta[0]->table;
        }
        if ($is_display['nav_bar'] == '1') {
            display_table_navigation($pos_next, $pos_prev, $encoded_sql_query);
            echo "\n";
        } else {
            echo "\n" . '<br /><br />' . "\n";
        }

        // 3. ----- Displays the results table -----

        ?>
<!-- Results table -->
<table border="<?php echo $GLOBALS['cfgBorder']; ?>" cellpadding="5">
        <?php
        echo "\n";
        display_table_headers($is_display, $fields_meta, $fields_cnt, $encoded_sql_query);
        display_table_body($dt_result, $is_display);
        ?>
</table>
<br />
        <?php
        echo "\n";

        // 4. ----- Displays the navigation bar at the bottom if required -----

        if ($is_display['nav_bar'] == '1') {
            display_table_navigation($pos_next, $pos_prev, $dt_result, $encoded_sql_query);
        } else {
            echo "\n" . '<br />' . "\n";
        }
    } // end of the 'display_table()' function



    /* ------------------- Functions used to build dumps ------------------- */

    /**
     * Uses the 'htmlspecialchars()' php function on databases, tables and fields
     * name if the dump has to be displayed on screen.
     *
     * @param   string   the string to format
     */
    function html_format($a_string = '')
    {
        return (empty($GLOBALS['asfile']) ? htmlspecialchars($a_string) : $a_string);
    } // end of the 'html_format()' function


    /**
     * Returns $table's CREATE definition
     *
     * Uses the 'html_format()' function defined in 'tbl_dump.php3'
     *
     * @param   string   the database name
     * @param   string   the table name
     * @param   string   the end of line sequence
     *
     * @return  string   the CREATE statement on success
     *
     * @global  boolean  whether to add 'drop' statements or not
     * @global  boolean  whether to use backquotes to allow the use of special
     *                   characters in database, table and fields names or not
     */
    function get_table_def($db, $table, $crlf)
    {
        global $drop;
        global $use_backquotes;

        $schema_create = '';
        if (!empty($drop)) {
            $schema_create .= 'DROP TABLE IF EXISTS ' . backquote(html_format($table), $use_backquotes) . ';' . $crlf;
        }

        // Steve Alberty's patch for complete table dump,
        // modified by Lem9 to allow older MySQL versions to continue to work
        if (MYSQL_INT_VERSION >= 32321) {
            // Whether to quote table and fields names or not
            if ($use_backquotes) {
                mysql_query('SET SQL_QUOTE_SHOW_CREATE = 1');
            } else {
                mysql_query('SET SQL_QUOTE_SHOW_CREATE = 0');
            }
            $result = mysql_query('SHOW CREATE TABLE ' . backquote($db) . '.' . backquote($table));
            if ($result != FALSE && mysql_num_rows($result) > 0) {
                $tmpres        = mysql_fetch_array($result);
                $schema_create .= str_replace("\n", $crlf, html_format($tmpres[1]));
            }
            return $schema_create;
        } // end if MySQL >= 3.23.20

        // For MySQL < 3.23.20
        $schema_create .= 'CREATE TABLE ' . html_format(backquote($table), $use_backquotes) . ' (' . $crlf;

        $local_query   = 'SHOW FIELDS FROM ' . backquote($db) . '.' . backquote($table);
        $result        = mysql_query($local_query) or mysql_die('', $local_query);
        while ($row = mysql_fetch_array($result)) {
            $schema_create     .= '   ' . html_format(backquote($row['Field'], $use_backquotes)) . ' ' . $row['Type'];
            if (isset($row['Default']) && $row['Default'] != '') {
                $schema_create .= ' DEFAULT \'' . html_format(sql_addslashes($row['Default'])) . '\'';
            }
            if ($row['Null'] != 'YES') {
                $schema_create .= ' NOT NULL';
            }
            if ($row['Extra'] != '') {
                $schema_create .= ' ' . $row['Extra'];
            }
            $schema_create     .= ',' . $crlf;
        } // end while
        $schema_create         = ereg_replace(',' . $crlf . '$', '', $schema_create);

        $local_query = 'SHOW KEYS FROM ' . backquote($db) . '.' . backquote($table);
        $result      = mysql_query($local_query) or mysql_die('', $local_query);
        while ($row = mysql_fetch_array($result))
        {
            $kname    = $row['Key_name'];
            $comment  = (isset($row['Comment'])) ? $row['Comment'] : '';
            $sub_part = (isset($row['Sub_part'])) ? $row['Sub_part'] : '';

            if ($kname != 'PRIMARY' && $row['Non_unique'] == 0) {
                $kname = "UNIQUE|$kname";
            }
            if ($comment == 'FULLTEXT') {
                $kname = 'FULLTEXT|$kname';
            }
            if (!isset($index[$kname])) {
                $index[$kname] = array();
            }
            if ($sub_part > 1) {
                $index[$kname][] = html_format(backquote($row['Column_name'], $use_backquotes)) . '(' . $sub_part . ')';
            } else {
                $index[$kname][] = html_format(backquote($row['Column_name'], $use_backquotes));
            }
        } // end while

        while (list($x, $columns) = @each($index)) {
            $schema_create     .= ',' . $crlf;
            if ($x == 'PRIMARY') {
                $schema_create .= '   PRIMARY KEY (';
            } else if (substr($x, 0, 6) == 'UNIQUE') {
                $schema_create .= '   UNIQUE ' . substr($x, 7) . ' (';
            } else if (substr($x, 0, 8) == 'FULLTEXT') {
                $schema_create .= '   FULLTEXT ' . substr($x, 9) . ' (';
            } else {
                $schema_create .= '   KEY ' . $x . ' (';
            }
            $schema_create     .= implode($columns, ', ') . ')';
        } // end while

        $schema_create .= $crlf . ')';

        // ??? Why bother about get_magic_quotes_gpc() here ???
        // if (get_magic_quotes_gpc()) {
        //     return stripslashes($schema_create);
        // } else {
        //     return $schema_create;
        // }
          return $schema_create;
    } // end of the 'get_table_def()' function


    /**
     * php >= 4.0.5 only : get the content of $table as a series of INSERT
     * statements.
     * After every row, a custom callback function $handler gets called.
     *
     * Last revision 13 July 2001: Patch for limiting dump size from
     * vinay@sanisoft.com & girish@sanisoft.com
     *
     * @param   string   the current database name
     * @param   string   the current table name
     * @param   string   the 'limit' clause to use with the sql query
     * @param   string   the name of the handler (function) to use at the end
     *                   of every row. This handler must accept one parameter
     *                   ($sql_insert)
     *
     * @return  boolean  always true
     *
     * @global  boolean  whether to use backquotes to allow the use of special
     *                   characters in database, table and fields names or not
     *
     * @see     get_table_content()
     *
     * @author  staybyte
     */
    function get_table_content_fast($db, $table, $add_query = '', $handler)
    {
        global $use_backquotes;

        $local_query = 'SELECT * FROM ' . backquote($db) . '.' . backquote($table) . $add_query;
        $result      = mysql_query($local_query) or mysql_die('', $local_query);
        if ($result != FALSE) {
            $fields_cnt = mysql_num_fields($result);

            // Checks whether the field is an integer or not
            for ($j = 0; $j < $fields_cnt; $j++) {
                $field_set[$j] = backquote(mysql_field_name($result, $j), $use_backquotes);
                $type          = mysql_field_type($result, $j);
                if ($type == 'tinyint' || $type == 'smallint' || $type == 'mediumint' || $type == 'int' ||
                    $type == 'bigint'  ||$type == 'timestamp') {
                    $field_num[$j] = TRUE;
                } else {
                    $field_num[$j] = FALSE;
                }
            } // end for

            // Sets the scheme
            if (isset($GLOBALS['showcolumns'])) {
                $fields        = implode(', ', $field_set);
                $schema_insert = 'INSERT INTO ' . backquote(html_format($table), $use_backquotes)
                               . ' (' . html_format($fields) . ') VALUES (';
            } else {
                $schema_insert = 'INSERT INTO ' . backquote(html_format($table), $use_backquotes)
                               . ' VALUES (';
            }
        
            $search     = array("\x0a","\x0d","\x1a"); //\x08\\x09, not required
            $replace    = array("\\n","\\r","\Z");
            $isFirstRow = TRUE;

            @set_time_limit(1200); // 20 Minutes

            while ($row = mysql_fetch_row($result)) {
                for ($j = 0; $j < $fields_cnt; $j++) {
                    if (!isset($row[$j])) {
                        $values[]     = 'NULL';
                    } else if (!empty($row[$j])) {
                        // a number
                        if ($field_num[$j]) {
                            $values[] = $row[$j];
                        }
                        // a string
                        else {
                            $values[] = "'" . str_replace($search, $replace, sql_addslashes($row[$j])) . "'";
                        }
                    } else {
                        $values[]     = "''";
                    } // end if
                } // end for

                // Extended inserts case
                if (isset($GLOBALS['extended_ins'])) {
                    if ($isFirstRow) {
                        $insert_line = $schema_insert . implode(',', $values) . ')';
                        $isFirstRow  = FALSE;
                    } else {
                        $insert_line = '(' . implode(',', $values) . ')';
                    }
                }
                // Other inserts case
                else { 
                   $insert_line = $schema_insert . implode(',', $values) . ')';
                }
                unset($values);

                // Call the handler
                $handler($insert_line);
            } // end while
            
            // Replace last comma by a semi-column in extended inserts case
            if (isset($GLOBALS['extended_ins'])) {
              $GLOBALS['tmp_buffer'] = ereg_replace(',([^,]*)$', ';\\1', $GLOBALS['tmp_buffer']);
            }
        } // end if ($result != FALSE)
    
        return TRUE;
    } // end of the 'get_table_content_fast()' function


    /**
     * php < 4.0.5 only: get the content of $table as a series of INSERT
     * statements.
     * After every row, a custom callback function $handler gets called.
     *
     * Last revision 13 July 2001: Patch for limiting dump size from
     * vinay@sanisoft.com & girish@sanisoft.com
     *
     * @param   string   the current database name
     * @param   string   the current table name
     * @param   string   the 'limit' clause to use with the sql query
     * @param   string   the name of the handler (function) to use at the end
     *                   of every row. This handler must accept one parameter
     *                   ($sql_insert)
     *
     * @return  boolean  always true
     *
     * @global  boolean  whether to use backquotes to allow the use of special
     *                   characters in database, table and fields names or not
     *
     * @see     get_table_content()
     */
    function get_table_content_old($db, $table, $add_query = '', $handler)
    {
        global $use_backquotes;

        $local_query = 'SELECT * FROM ' . backquote($db) . '.' . backquote($table) . $add_query;
        $result      = mysql_query($local_query) or mysql_die('', $local_query);
        $i           = 0;
        $isFirstRow  = TRUE;

        while ($row = mysql_fetch_row($result)) {
            @set_time_limit(60); // HaRa
            $table_list     = '(';

            for ($j = 0; $j < mysql_num_fields($result); $j++) {
                $table_list .= backquote(mysql_field_name($result, $j), $use_backquotes) . ', ';
            }

            $table_list     = substr($table_list, 0, -2);
            $table_list     .= ')';

            if (isset($GLOBALS['extended_ins']) && !$isFirstRow) {
                $schema_insert = '(';
            } else {
                if (isset($GLOBALS['showcolumns'])) {
                    $schema_insert = 'INSERT INTO ' . backquote(html_format($table), $use_backquotes)
                                   . ' ' . html_format($table_list) . ' VALUES (';
                } else {
                    $schema_insert = 'INSERT INTO ' . backquote(html_format($table), $use_backquotes)
                                   . ' VALUES (';
                }
                $isFirstRow        = FALSE;
            }

            for ($j = 0; $j < mysql_num_fields($result); $j++) {
                if (!isset($row[$j])) {
                    $schema_insert .= ' NULL,';
                } else if ($row[$j] != '') {
                    $dummy  = '';
                    $srcstr = $row[$j];
                    for ($xx = 0; $xx < strlen($srcstr); $xx++) {
                        $yy = strlen($dummy);
                        if ($srcstr[$xx] == "\\")   $dummy .= "\\\\";
                        if ($srcstr[$xx] == "'")    $dummy .= "\\'";
                        if ($srcstr[$xx] == "\"")   $dummy .= "\\\"";
                        if ($srcstr[$xx] == "\x00") $dummy .= "\\0";
                        if ($srcstr[$xx] == "\x0a") $dummy .= "\\n";
                        if ($srcstr[$xx] == "\x0d") $dummy .= "\\r";
                        if ($srcstr[$xx] == "\x08") $dummy .= "\\b";
                        if ($srcstr[$xx] == "\t")   $dummy .= "\\t";
                        if ($srcstr[$xx] == "\x1a") $dummy .= "\\Z";
                        if (strlen($dummy) == $yy)  $dummy .= $srcstr[$xx];
                    }
                    $schema_insert .= " '" . $dummy . "',";
                } else {
                    $schema_insert .= " '',";
                } // end if
            } // end for
            $schema_insert = ereg_replace(',$', '', $schema_insert);
            $schema_insert .= ')';
            $handler(trim($schema_insert));
            ++$i;
        } // end while

        // Replace last comma by a semi-column in extended inserts case
        if ($i > 0 && isset($GLOBALS['extended_ins'])) {
            $GLOBALS['tmp_buffer'] = ereg_replace(',([^,]*)$', ';\\1', $GLOBALS['tmp_buffer']);
        }

        return TRUE;
    } // end of the 'get_table_content_old()' function


    /**
     * Dispatches between the versions of 'get_table_content' to use depending
     * on the php version
     *
     * Last revision 13 July 2001: Patch for limiting dump size from
     * vinay@sanisoft.com & girish@sanisoft.com
     *
     * @param   string   the current database name
     * @param   string   the current table name
     * @param   integer  the offset on this table
     * @param   integer  the last row to get
     * @param   string   the name of the handler (function) to use at the end
     *                   of every row. This handler must accept one parameter
     *                   ($sql_insert)
     *
     * @see     get_table_content_fast(), get_table_content_old()
     * @author  staybyte
     */
    function get_table_content($db, $table, $limit_from = 0, $limit_to = 0, $handler)
    {
        // Defines the offsets to use
        if ($limit_from > 0) {
            $limit_from--;
        } else {
            $limit_from = 0;
        }
        if ($limit_to > 0 && $limit_from >= 0) {
            $add_query  = " LIMIT $limit_from, $limit_to";
        } else {
            $add_query  = '';
        }

        // Call the working function depending on the php version
        if (PHP_INT_VERSION >= 40005) {
            get_table_content_fast($db, $table, $add_query, $handler);
        } else {
            get_table_content_old($db, $table, $add_query, $handler);
        }
    } // end of the 'get_table_content()' function


    /**
     * Outputs the content of a table in CSV format
     *
     * Last revision 14 July 2001: Patch for limiting dump size from
     * vinay@sanisoft.com & girish@sanisoft.com
     *
     * @param   string   the database name
     * @param   string   the table name
     * @param   integer  the offset on this table
     * @param   integer  the last row to get
     * @param   string   the field separator character
     * @param   string   the optionnal "enclosed by" character
     * @param   string   the handler (function) to call. It must accept one
     *                   parameter ($sql_insert)
     *
     * @global  string   whether to obtain an excel compatible csv format or a
     *                   simple csv one
     *
     * @return  boolean always true
     */
    function get_table_csv($db, $table, $limit_from = 0, $limit_to = 0, $sep, $enc_by, $esc_by, $handler)
    {
        global $what;

        // Handles the "separator" and the optionnal "enclosed by" characters
        if ($what == 'excel') {
            $sep     = ';';
        } else if (!isset($sep)) {
            $sep     = '';
        } else {
            if (get_magic_quotes_gpc()) {
                $sep = stripslashes($sep);
            }
            $sep     = str_replace('\\t', "\011", $sep);
        }
        if ($what == 'excel') {
            $enc_by  = '"';
        } else if (!isset($enc_by)) {
            $enc_by  = '';
        } else if (get_magic_quotes_gpc()) {
            $enc_by  = stripslashes($enc_by);
        }
        if ($what == 'excel'
            || (empty($esc_by) && $enc_by != '')) {
            // double the "enclosed by" character
            $esc_by  = $enc_by;
        } else if (!isset($esc_by)) {
            $esc_by  = '';
        } else if (get_magic_quotes_gpc()) {
            $esc_by  = stripslashes($esc_by);
        }

        // Defines the offsets to use
        if ($limit_from > 0) {
            $limit_from--;
        } else {
            $limit_from = 0;
        }
        if ($limit_to > 0 && $limit_from >= 0) {
            $add_query  = " LIMIT $limit_from, $limit_to";
        } else {
            $add_query  = '';
        }

        // Gets the data from the database
        $local_query = 'SELECT * FROM ' . backquote($db) . '.' . backquote($table) . $add_query;
        $result      = mysql_query($local_query) or mysql_die('', $local_query);

        // Format the data
        $i      = 0;
        while ($row = mysql_fetch_row($result)) {
            @set_time_limit(60);
            $schema_insert = '';
            $fields_cnt    = mysql_num_fields($result);
            for ($j = 0; $j < $fields_cnt; $j++) {
                if (!isset($row[$j])) {
                    $schema_insert .= 'NULL';
                }
                else if ($row[$j] != '') {
                    // loic1 : always enclose fields
                    if ($what == 'excel') {
                        $row[$j]   = ereg_replace("\015(\012)?", "\012", $row[$j]);
                    }
                    $schema_insert .= $enc_by
                                   . str_replace($enc_by, $esc_by . $enc_by, $row[$j])
                                   . $enc_by;
                }
                else {
                    $schema_insert .= '';
                }
                if ($j < $fields_cnt-1) {
                    $schema_insert .= $sep;
                }
            } // end for
            $handler(trim($schema_insert));
            ++$i;
        } // end while

        return TRUE;
    } // end of the 'get_table_csv()' function



    /* -------- Functions used to format commands from imported file ------- */

    /**
     * Splits up large sql files into individual queries
     *
     * Last revision: 22 August 2001 - loic1
     *
     * @param   string   the sql commands
     * @param   string   the end of command line delimiter 
     *
     * @return  array    the splitted sql commands
     */
    function split_sql_file($sql, $delimiter)
    {
        $sql               = trim($sql);
        $char              = '';
        $last_char         = '';
        $ret               = array();
        $string_start      = '';
        $in_string         = FALSE;
        $escaped_backslash = FALSE;

        for ($i = 0; $i < strlen($sql); ++$i) {
            $char = $sql[$i];

            // if delimiter found, add the parsed part to the returned array
            if ($char == $delimiter && !$in_string) {
                $ret[]     = substr($sql, 0, $i);
                $sql       = substr($sql, $i + 1);
                $i         = 0;
                $last_char = '';
            }

            if ($in_string) {
                // We are in a string, first check for escaped backslashes
                if ($char == '\\') {
                    if ($last_char != '\\') {
                        $escaped_backslash = FALSE;
                    } else {
                        $escaped_backslash = !$escaped_backslash;
                    }
                }
                // then check for not escaped end of strings except for
                // backquotes than cannot be escaped
                if (($char == $string_start)
                    && ($char == '`' || !(($last_char == '\\') && !$escaped_backslash))) {
                    $in_string    = FALSE;
                    $string_start = '';
                }
            } else {
                // we are not in a string, check for start of strings
                if (($char == '"') || ($char == '\'') || ($char == '`')) {
                    $in_string    = TRUE;
                    $string_start = $char;
                }
            }
            $last_char = $char;
        } // end for

        // add any rest to the returned array
        if (!empty($sql)) {
            $ret[] = $sql;
        }
        return $ret;
    } // end of the 'split_sql_file()' function


    /**
     * Removes # type remarks from large sql files
     *
     * Version 3 20th May 2001 - Last Modified By Pete Kelly
     *
     * @param   string   the sql commands
     *
     * @return  string   the cleaned sql commands
     */
    function remove_remarks($sql)
    {
        $i = 0;

        while ($i < strlen($sql)) {
            // Patch from Chee Wai
            // (otherwise, if $i == 0 and $sql[$i] == "#", the original order
            // in the second part of the AND bit will fail with illegal index)
            if ($sql[$i] == '#' && ($i == 0 || $sql[$i-1] == "\n")) {
                $j = 1;
                while ($sql[$i+$j] != "\n") {
                    $j++;
                    if ($j+$i > strlen($sql)) {
                        break;
                    }
                } // end while
                $sql = substr($sql, 0, $i) . substr($sql, $i+$j);
            } // end if
            $i++;
        } // end while

        return $sql;
    } // end of the 'remove_remarks()' function



    /* ------------------------ The bookmark feature ----------------------- */

    /**
     * Defines the bookmark parameters for the current user
     *
     * @return  array    the bookmark parameters for the current user
     *
     * @global  array    the list of settings for the current server
     * @global  integer  the id of the current server
     */
    function get_bookmarks_param()
    {
        global $cfgServer;
        global $server;

        $cfgBookmark = FALSE;
        $cfgBookmark = '';

        // No server selected -> no bookmark table
        if ($server == 0) {
            return '';
        }

        $cfgBookmark['user']  = $cfgServer['user'];
        $cfgBookmark['db']    = $cfgServer['bookmarkdb'];
        $cfgBookmark['table'] = $cfgServer['bookmarktable'];

        return $cfgBookmark;
    } // end of the 'get_bookmarks_param()' function


    /**
     * Gets the list of bookmarks defined for the current database
     *
     * @param   string   the current database name
     * @param   array    the bookmark parameters for the current user
     *
     * @return  mixed    the bookmarks list if defined, false else
     */
    function list_bookmarks($db, $cfgBookmark)
    {
        $query  = 'SELECT label, id FROM '. backquote($cfgBookmark['db']) . '.' . backquote($cfgBookmark['table'])
                . ' WHERE dbase = \'' . sql_addslashes($db) . '\''
                . ' AND user = \'' . sql_addslashes($cfgBookmark['user']) . '\'';
        if (isset($GLOBALS['dbh'])) {
            $result = mysql_query($query, $GLOBALS['dbh']);
        } else {
            $result = mysql_query($query);
        }

        // There is some bookmarks -> store them
        if ($result > 0 && mysql_num_rows($result) > 0) {
            $flag = 1;
            while ($row = mysql_fetch_row($result)) {
                $bookmark_list[$flag . ' - ' . $row[0]] = $row[1];
                $flag++;
            } // end while
            return $bookmark_list;
        }
        // No bookmarks for the current database
        else {
            return FALSE;
        }
    } // end of the 'list_bookmarks()' function


    /**
     * Gets the sql command from a bookmark
     *
     * @param   string   the current database name
     * @param   array    the bookmark parameters for the current user
     * @param   integer  the id of the bookmark to get
     *
     * @return  string   the sql query
     */
    function query_bookmarks($db, $cfgBookmark, $id)
    {
        $query          = 'SELECT query FROM ' . backquote($cfgBookmark['db']) . '.' . backquote($cfgBookmark['table'])
                        . ' WHERE dbase = \'' . sql_addslashes($db) . '\''
                        . ' AND user = \'' . sql_addslashes($cfgBookmark['user']) . '\''
                        . ' AND id = ' . $id;
        if (isset($GLOBALS['dbh'])) {
            $result = mysql_query($query, $GLOBALS['dbh']);
        } else {
            $result = mysql_query($query);
        }
        $bookmark_query = mysql_result($result, 0, 'query');

        return $bookmark_query;
    } // end of the 'query_bookmarks()' function


    /**
     * Adds a bookmark
     *
     * @param   array    the properties of the bookmark to add
     * @param   array    the bookmark parameters for the current user
     */
    function add_bookmarks($fields, $cfgBookmark)
    {
        $query = 'INSERT INTO ' . backquote($cfgBookmark['db']) . '.' . backquote($cfgBookmark['table'])
               . ' (id, dbase, user, query, label) VALUES (\'\', \'' . sql_addslashes($fields['dbase']) . '\', \'' . sql_addslashes($fields['user']) . '\', \'' . sql_addslashes(urldecode($fields['query'])) . '\', \'' . sql_addslashes($fields['label']) . '\')';
        if (isset($GLOBALS['dbh'])) {
            $result = mysql_query($query, $GLOBALS['dbh']);
        } else {
            $result = mysql_query($query);
        }
    } // end of the 'add_bookmarks()' function


    /**
     * Deletes a bookmark
     *
     * @param   string   the current database name
     * @param   array    the bookmark parameters for the current user
     * @param   integer  the id of the bookmark to get
     */
    function delete_bookmarks($db, $cfgBookmark, $id)
    {
        $query  = 'DELETE FROM ' . backquote($cfgBookmark['db']) . '.' . backquote($cfgBookmark['table'])
                . ' WHERE user = \'' . sql_addslashes($cfgBookmark['user']) . '\''
                . ' AND id = ' . $id;
        if (isset($GLOBALS['dbh'])) {
            $result = mysql_query($query, $GLOBALS['dbh']);
        } else {
            $result = mysql_query($query);
        }
    } // end of the 'delete_bookmarks()' function


    /* -------------------- End of functions definitions ------------------- */


    /**
     * Bookmark Support
     */
    $cfgBookmark = get_bookmarks_param();

} // $__LIB_INC__

?>
