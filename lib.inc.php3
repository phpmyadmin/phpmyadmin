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
        header('status: 401 Unauthorized');
        header('HTTP/1.0 401 Unauthorized');
        header('WWW-authenticate: basic realm="phpMyAdmin on ' . $GLOBALS['cfgServer']['host'] . '"');
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
    include('./defines.inc.php3');



    /**
     * Loads the mysql extensions if it is not loaded yet
     * staybyte - 26. June 2001
     */
    if (PHP_INT_VERSION >= 30009) {
        if (PMA_WINDOWS) {
            $suffix = '.dll';
        } else {
            $suffix = '.so';
        }
        if (PHP_INT_VERSION < 40000) {
            $extension = 'MySQL';
        } else {
            $extension = 'mysql';
        }
        if (!@extension_loaded($extension) && !@get_cfg_var('safe_mode') && @function_exists('dl')) {
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
     */
    function mysql_die($error_message = '', $the_query = '')
    {
        if (!$error_message) {
            $error_message = mysql_error();
        }
        if (!$the_query) {
            $the_query     = $GLOBALS['sql_query'];
        }
        $hist              = (isset($GLOBALS['btnDrop'])) ? -2 : -1;

        echo '<b>'. $GLOBALS['strError'] . '</b>' . "\n";
        if (!empty($the_query)) {
            $query_base = htmlspecialchars($the_query);
            $query_base = ereg_replace("((\015\012)|(\015)|(\012)){3,}", "\n\n", $query_base);
            echo '<p>' . "\n";
            $edit_link = '<a href="db_details.php3?lang=' . $GLOBALS['lang'] . '&server=' . urlencode($GLOBALS['server']) . '&db=' . urlencode($GLOBALS['db']) . '&sql_query=' . urlencode($the_query) . '&show_query=y">' . $GLOBALS['strEdit'] . '</a>';
            echo '    ' . $GLOBALS['strSQLQuery'] . '&nbsp;:&nbsp;' . "\n";
            echo '    [' . $edit_link . ']' . "\n";
            echo '<pre>' . "\n" . $query_base . "\n" . '</pre>' . "\n";
            echo '</p>' . "\n";
        }
        if (!empty($error_message)) {
            $error_message = htmlspecialchars($error_message);
            $error_message = ereg_replace("((\015\012)|(\015)|(\012)){3,}", "\n\n", $error_message);
        }
        echo '<p>' . "\n";
        echo '    ' . $GLOBALS['strMySQLSaid'] . '<br />' . "\n";
        echo '<pre>' . "\n" . $error_message . "\n" . '</pre>' . "\n";
        echo '</p>' . "\n";
        echo '<a href="javascript:window.history.go(' . $hist . ')">' . $GLOBALS['strBack'] . '</a>';

        echo "\n" . '</body>' . "\n";

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
                $server_port   = (empty($cfgServer['port']))
                               ? ''
                               : ':' . $cfgServer['port'];
                $server_socket = (empty($cfgServer['socket']) || PHP_INT_VERSION < 30010)
                               ? ''
                               : ':' . $cfgServer['socket'];
                $dbh           = $connect_func(
                                     $cfgServer['host'] . $server_port . $server_socket,
                                     $cfgServer['stduser'],
                                     $cfgServer['stdpass']
                                 ) or mysql_die();

                $PHP_AUTH_USER = str_replace('\'', '\\\'', $PHP_AUTH_USER);
                $PHP_AUTH_PW   = str_replace('\'', '\\\'', $PHP_AUTH_PW);
                $auth_query = 'SELECT User, Password, Select_priv '
                            . 'FROM mysql.user '
                            . 'WHERE '
                            .    'User = \'' . $PHP_AUTH_USER . '\' '
                            .    'AND Password = PASSWORD(\'' . $PHP_AUTH_PW . '\')';
                $rs         = mysql_query($auth_query, $dbh) or mysql_die();

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
                        $rs = mysql_query('SELECT DISTINCT Db FROM mysql.db WHERE Select_priv = \'Y\' AND User = \'' . $PHP_AUTH_USER . '\'') or mysql_die();
                        if (@mysql_numrows($rs) <= 0) {
                            $rs = mysql_query('SELECT Db FROM mysql.tables_priv WHERE Table_priv LIKE \'%Select%\' AND User = \'' . $PHP_AUTH_USER . '\'') or mysql_die();
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
        $server_port   = (empty($cfgServer['port']))
                       ? ''
                       : ':' . $cfgServer['port'];
        $server_socket = (empty($cfgServer['socket']) || PHP_INT_VERSION < 30010)
                       ? ''
                       : ':' . $cfgServer['socket'];
        $link          = $connect_func(
                             $cfgServer['host'] . $server_port . $server_socket,
                             $cfgServer['user'],
                             $cfgServer['password']
                         ) or mysql_die();
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



    /* ----- Functions used to display records returned by a sql query ----- */

    /**
     * Expands table alias in SQL queries
     *
     * @param   string   a single sql query
     *
     * @return  string   the expanded SQL query
     *
     * @author Robin Johnson
     *
     * @version 1.1 - 10th August 2001
     */
    function expand_sql_query($sql)
    {
        $sql             = trim($sql);
        $arr             = explode(' ', $sql);
        if (strtoupper($arr[0]) != 'SELECT') {
            return $sql;
        }
        $start_table_ref = FALSE;
        $end_table_ref   = FALSE;

        // Finds which block of text has the table reference data
        for ($i = 0; $i < count($arr); $i++) {
            $tmp = trim(strtoupper($arr[$i]));
            if ($tmp == 'FROM') {
                $start_table_ref = $i + 1;
            }
            else if ($tmp == 'WHERE' || $tmp == 'GROUP' || $tmp == 'HAVING' || $tmp == 'ORDER'
                      || $tmp == 'LIMIT' || $tmp == 'PROCEDURE' || $tmp == 'FOR' || $tmp == 'BY'
                      || $tmp == 'UPDATE' || $tmp == 'LOCK' || $tmp == 'IN' || $tmp == 'SHARE'
                       || $tmp == 'MODE') {
                // We want only the first one 
                if (!$end_table_ref) {
                    $end_table_ref = $i - 1;
                }
            }
        } // end for
        // In case the table reference was the last thing
        if (!$end_table_ref) {
            $end_table_ref = count($arr) - 1;
        }

        // Handles the table reference data
        // First put it back together
        $table_ref_data     = ''; 
        for ($i = $start_table_ref; $i <= $end_table_ref; $i++) {
            $table_ref_data .= ' ' . $arr[$i];
        } // end for

        // Cleans out the extra spaces
        $table_ref_data     = trim($table_ref_data);

        // Cleans up a bit
        unset($arr);
        unset($tmp);
        unset($i); 

        // Breaks it apart into each table used
        $arr = explode(',', $table_ref_data); 
    
        // Handles each table used
        reset($arr);
        while (list(, $table) = each ($arr)) {
            $table = trim($table);
            if (isset($data)) {
                unset($data);
            }
            $data = explode(' ', $table); //will have at most 3 items
            if (isset($data_count)) {
                unset($data_count);
            }
            $data_count = count($data);
            if (isset($match)) {
                unset($match);
            }
            if ($data_count == 1) {
                continue;
            }
            // Checks with 'as' keyword specified...
            if ($data_count == 3) {
                $data[1] = $data[2];
                $match   = $data[0] . ' as ' . $data[1];
            }
            // ... and now in form "departments d"
            if ($data_count >= 2) {
                $sql = eregi_replace(
                           '([^a-zA-Z0-9])' . $data[1] . '\.',
                           '\1 ' . $data[0] . '.',
                           $sql);
                if (!isset($match)) {
                    $match = $data[0] . ' ' . $data[1];
                }
                $sql = str_replace($match, ' ' . $data[0], $sql);
            } // end if
        } // end while

        return $sql;
    } // end of the 'expand_sql_query()' function


    /**
     * Displays a navigation bar to browse among the results of a sql query
     *
     * @param   integer  the offset for the "next" page
     * @param   integer  the offset for the "previous" page
     * @param   array    the result of the query
     *
     * @global  string   the current language
     * @global  integer  the server to use (refers to the number in the
     *                   configuration file)
     * @global  string   the database name
     * @global  string   the table name
     * @global  string   the current sql query
     * @global  integer  the current position in results
     * @global  string   the url to go back in case of errors
     * @global  integer  the maximum number of rows per page
     * @global  integer  the total number of rows returned by the sql query
     */
    function show_table_navigation($pos_next, $pos_prev, $dt_result)
    {
        global $lang, $server, $db, $table;
        global $sql_query, $pos, $goto;
        global $sessionMaxRows, $SelectNumRows;
        
        // $sql_query will be stripslashed in 'sql.php3' if the
        // 'magic_quotes_gpc' directive is set to 'on'
        if (get_magic_quotes_gpc()) {
            $encoded_sql_query = urlencode(addslashes($sql_query));
        } else {
            $encoded_sql_query = urlencode($sql_query);
        }
        ?>

<!--  Beginning of table navigation bar -->
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
            <input type="hidden" name="sql_query" value="<?php echo $encoded_sql_query; ?>" />
            <input type="hidden" name="pos" value="0" />
            <input type="hidden" name="sessionMaxRows" value="<?php echo $sessionMaxRows; ?>" />
            <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
            <input type="submit" name="navig" value="<?php echo $GLOBALS['strPos1'] . ' &lt;&lt;'; ?>" />
        </form>
    </td>
    <td>
        <form action="sql.php3" method="post">
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
            <input type="hidden" name="sql_query" value="<?php echo $encoded_sql_query; ?>" />
            <input type="hidden" name="pos" value="<?php echo $pos_prev; ?>" />
            <input type="hidden" name="sessionMaxRows" value="<?php echo $sessionMaxRows; ?>" />
            <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
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
            onsubmit="return (checkFormElementInRange(this, 'pos', 0, <?php echo $SelectNumRows-1; ?>) && checkFormElementInRange(this, 'sessionMaxRows'))">
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
            <input type="hidden" name="sql_query" value="<?php echo $encoded_sql_query; ?>" />
            <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
            <input type="submit" name="navig" value="<?php echo $GLOBALS['strShow']; ?>&nbsp;:" />
            <input type="text" name="sessionMaxRows" size="3" value="<?php echo $sessionMaxRows; ?>" />
            <?php echo $GLOBALS['strRowsFrom'] . "\n"; ?>
            <input type="text" name="pos" size="3" value="<?php echo (($pos_next >= $SelectNumRows) ? 0 : $pos_next); ?>" />
        </form>
    </td>
    <td>
        &nbsp;&nbsp;&nbsp;
    </td>
        <?php
        // Move to the next page or to the last one
        if (($pos + $sessionMaxRows < $SelectNumRows) && mysql_num_rows($dt_result) >= $sessionMaxRows) {
            ?>
    <td>
        <form action="sql.php3" method="post">
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
            <input type="hidden" name="sql_query" value="<?php echo $encoded_sql_query; ?>" />
            <input type="hidden" name="pos" value="<?php echo $pos_next; ?>" />
            <input type="hidden" name="sessionMaxRows" value="<?php echo $sessionMaxRows; ?>" />
            <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
            <input type="submit" name="navig" value="<?php echo '&gt; ' . $GLOBALS['strNext']; ?>" />
        </form>
    </td>
    <td>
        <form action="sql.php3" method="post"
            onsubmit="return <?php echo (($pos + $sessionMaxRows < $SelectNumRows && mysql_num_rows($dt_result) >= $sessionMaxRows) ? 'true' : 'false'); ?>">
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
            <input type="hidden" name="sql_query" value="<?php echo $encoded_sql_query; ?>" />
            <input type="hidden" name="pos" value="<?php echo $SelectNumRows - $sessionMaxRows; ?>" />
            <input type="hidden" name="sessionMaxRows" value="<?php echo $sessionMaxRows; ?>" />
            <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
            <input type="submit" name="navig" value="<?php echo '&gt;&gt; ' . $GLOBALS['strEnd']; ?>" />
        </form>
    </td>
            <?php
        } // end move toward
        echo "\n";
        ?>
</tr>
</table>
<!-- End of table navigation bar -->

        <?php
    } // end of the 'show_table_navigation()' function


    /**
     * Displays a table of results returned by a sql query
     *
     * @param   array   the result table to display
     * @param   mixed   whether to display navigation bar and bookmarks links
     *                  or not
     *
     * @global  string   the current language
     * @global  integer  the server to use (refers to the number in the
     *                   configuration file)
     * @global  string   the database name
     * @global  string   the table name
     * @global  string   the current sql query
     * @global  string   the url to go back in case of errors
     * @global  integer  the total number of rows returned by the sql query
     */
    function display_table($dt_result, $is_simple = FALSE)
    {
        global $lang, $server, $db, $table;
        global $sql_query, $goto, $pos;
        global $SelectNumRows;

        // Gets the number of rows per page
        if (isset($GLOBALS['sessionMaxRows'])) {
            $GLOBALS['cfgMaxRows']     = $GLOBALS['sessionMaxRows'];
        } else {
            $GLOBALS['sessionMaxRows'] = $GLOBALS['cfgMaxRows'];
        }

        // Loads a javascript library that does quick validations
        ?>

<script type="text/javascript" language="javascript">
<!--
var errorMsg1 = '<?php echo(str_replace('\'', '\\\'', $GLOBALS['strNotNumber'])); ?>';
var errorMsg2 = '<?php echo(str_replace('\'', '\\\'', $GLOBALS['strNotValidNumber'])); ?>';
//-->
</script>
<script src="functions.js" type="text/javascript" language="javascript"></script>

        <?php
        echo "\n";

        // Counts the number of rows in the table if required
        if (isset($SelectNumRows) && $SelectNumRows != '') {
            $total = $SelectNumRows;
        }
        else if (!$is_simple && !empty($table) && !empty($db)) {
            $result = mysql_query('SELECT COUNT(*) as total FROM ' . backquote($db) . '.' . backquote($table)) or mysql_die();
            $row    = mysql_fetch_array($result);
            $total  = $row['total'];
        } // end if

        // Defines offsets for the next and previous pages
        if (!$is_simple) {
            if (!isset($pos)) {
                $pos      = 0;
            }
            $pos_next     = $pos + $GLOBALS['cfgMaxRows'];
            $pos_prev     = $pos - $GLOBALS['cfgMaxRows'];
            if ($pos_prev < 0) {
                $pos_prev = 0;
            }
        } // end if

        // Displays a messages with position informations
        if (isset($total) && $total > 1) {
            if (isset($SelectNumRows) && $SelectNumRows != $total) {
                $selectstring = ', ' . $SelectNumRows . ' ' . $GLOBALS['strSelectNumRows'];
            } else {
                $selectstring = '';
            }
            $lastShownRec = ($pos_next > $total) ? $total : $pos_next;
            show_message($GLOBALS['strShowingRecords'] . " $pos - $lastShownRec ($total " . $GLOBALS['strTotal'] . $selectstring . ')');
        } else {
            show_message($GLOBALS['strSQLQuery']);
        }

        // Displays the navigation bars
        $field     = mysql_fetch_field($dt_result);
        if (!isset($table) || strlen(trim($table)) == 0) {
            $table = $field->table;
        }
        mysql_field_seek($dt_result, 0);
        if (!$is_simple
            && (!isset($SelectNumRows) || $SelectNumRows > 1)) {
            show_table_navigation($pos_next, $pos_prev, $dt_result);
        } else {
            echo "\n" . '<br /><br />' . "\n";
        }

        // Displays the results
        $is_show_processlist = eregi("^[ \n\r]*show[ \n\r]*processlist[ \n\r]*$", $sql_query);
        ?>

<table border="<?php echo $GLOBALS['cfgBorder']; ?>" cellpadding ="5">
<tr>
        <?php
        echo "\n";
        if ($GLOBALS['cfgModifyDeleteAtLeft'] && !$is_simple) {
            echo '    <td></td>' . "\n";
            echo '    <td></td>' . "\n";
        }
        if ($is_show_processlist) {
            echo '    <td></td>' . "\n";
        }
        while ($field = mysql_fetch_field($dt_result)) {
            // Result is more than one row long
            if (@mysql_num_rows($dt_result) > 1 && !$is_simple) {
                // Defines the url used to append/modify a sorting order
                // 1. Checks if an hard coded 'order by' clause exists
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
                // 2. Checks if the current column is used to sort the result
                if (empty($sql_order)) {
                    $is_in_sort = FALSE;
                } else {
                    $is_in_sort = eregi(' (`?)' . str_replace('\\', '\\\\', $field->name) . '(`?)[ ,$]', $sql_order);
                }
                // 3. Do define the sorting url
                if (!$is_in_sort) {
                    $sort_order = ' ORDER BY ' . backquote($field->name) . ' ' . $GLOBALS['cfgOrder'];
                    $order_img  = '';
                }
                else if (substr($sql_order, -3) == 'ASC' && $is_in_sort) {
                    $sort_order = ' ORDER BY ' . backquote($field->name) . ' DESC';
                    $order_img  = '&nbsp;<img src="./images/asc_order.gif" border="0" width="7" height="7" alt="ASC" />';
                }
                else if (substr($sql_order, -4) == 'DESC' && $is_in_sort) {
                    $sort_order = ' ORDER BY ' . backquote($field->name) . ' ASC';
                    $order_img  = '&nbsp;<img src="./images/desc_order.gif" border="0" width="7" height="7" alt="DESC" />';
                }
                if (eregi('(.*)( LIMIT (.*)| PROCEDURE (.*)| FOR UPDATE| LOCK IN SHARE MODE)', $unsorted_sql_query, $regs3)) {
                    $sorted_sql_query = $regs3[1] . $sort_order . $regs3[2];
                } else {
                    $sorted_sql_query = $unsorted_sql_query . $sort_order;
                }
                $url_query = 'lang=' . $lang
                           . '&server=' . urlencode($server)
                           . '&db=' . urlencode($db)
                           . '&table=' . urlencode($table)
                           . '&pos=' . $pos
                           . '&sql_query=' . urlencode($sorted_sql_query);
                ?>
    <th>
        <a href="sql.php3?<?php echo $url_query; ?>">
            <?php echo htmlspecialchars($field->name); ?></a><?php echo $order_img . "\n"; ?>
    </th>
                <?php
            } // end if

            // Result is one row long
            else {
                echo "\n";
                ?>
    <th>
        <?php echo htmlspecialchars($field->name) . "\n"; ?>
    </th>
                <?php
            } // end else
            echo "\n";
        } // end while
        ?>
</tr>

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
            $primary_key              = '';
            $uva_nonprimary_condition = '';
            $bgcolor                  = ($foo % 2) ? $GLOBALS['cfgBgcolorOne'] : $GLOBALS['cfgBgcolorTwo'];
            
            ?>
<tr bgcolor="<?php echo $bgcolor; ?>">
            <?php
            echo "\n";
            $fields_cnt = mysql_num_fields($dt_result);
            for ($i = 0; $i < $fields_cnt; ++$i) {
                $primary   = mysql_fetch_field($dt_result, $i);
                $condition = ' ' . backquote($primary->name) . ' ';
                if (!isset($row[$i])) {
                    $row[$i]   = '';
                    $condition .= 'IS NULL AND';
                } else {
                    $condition .= '= \'' . sql_addslashes($row[$i]) . '\' AND';
                }
                if ($primary->numeric == 1) {
                    if ($is_show_processlist) {
                        $Id = $row[$i];
                    }
                }
                if ($primary->primary_key > 0) {
                    $primary_key .= $condition;
                }
                $uva_nonprimary_condition .= $condition;
            } // end for

            // Correction uva 19991216: prefer primary keys for condition, but
            // use conjunction of all values if no primary key
            if ($primary_key) {
                $uva_condition = $primary_key;
            } else {
                $uva_condition = $uva_nonprimary_condition;
            }
            $uva_condition     = urlencode(ereg_replace(' ?AND$', '', $uva_condition));
            
            $url_query  = 'lang=' . $lang
                        . '&server=' . urlencode($server)
                        . '&db=' . urlencode($db)
                        . '&table=' . urlencode($table)
                        . '&pos=' . $pos;

            $goto       = (!empty($goto) && empty($GLOBALS['QUERY_STRING'])) ? $goto : 'sql.php3';
            $edit_url   = 'tbl_change.php3'
                        . '?' . $url_query
                        . '&primary_key=' . $uva_condition
                        . '&sql_query=' . urlencode($sql_query)
                        . '&goto=' . urlencode($goto);

            $goto       = 'sql.php3'
                        . '?' . $url_query
                        . '&sql_query=' . urlencode($sql_query)
                        . '&zero_rows=' . urlencode(htmlspecialchars($GLOBALS['strDeleted']))
                        . '&goto=tbl_properties.php3';
            $delete_url = 'sql.php3'
                        . '?' . $url_query
                        . '&sql_query=' . urlencode('DELETE FROM ' . backquote($table) . ' WHERE') . $uva_condition
                        . '&zero_rows=' . urlencode(htmlspecialchars($GLOBALS['strDeleted']))
                        . '&goto=' . urlencode($goto);

            if ($GLOBALS['cfgModifyDeleteAtLeft'] && !$is_simple) {
                ?>
    <td>
        <a href="<?php echo $edit_url; ?>">
            <?php echo $GLOBALS['strEdit']; ?></a>
    </td>
    <td>
        <a href="<?php echo $delete_url; ?>">
            <?php echo $GLOBALS['strDelete']; ?></a>
    </td>
                <?php
                echo "\n";
            } // end if

            if ($is_show_processlist) {
                ?>
    <td align="right">
        <a href="sql.php3?db=mysql&sql_query=<?php echo urlencode('KILL ' . $Id); ?>&goto=main.php3">
            <?php echo $GLOBALS['strKill']; ?></a>
    </td>
                <?php
                echo "\n";
            } // end if

            $fields_cnt = mysql_num_fields($dt_result);
            for ($i = 0; $i < $fields_cnt; ++$i) {
                if (!isset($row[$i])) {
                    $row[$i] = '';
                }
                $primary = mysql_fetch_field($dt_result, $i);
                if ($primary->numeric == 1) {
                    echo '    <td align="right">' . $row[$i] . '</td>' . "\n";
                } else if ($GLOBALS['cfgShowBlob'] == FALSE && eregi('BLOB', $primary->type)) {
                    // loic1 : mysql_fetch_fields returns BLOB in place of TEXT
                    // fields type, however TEXT fields must be displayed even
                    // if $cfgShowBlob is false -> get the true type of the
                    // fields.
                    $result_type     = mysql_query('SHOW FIELDS FROM ' . backquote($db) . '.' . backquote($primary->table) . ' LIKE \'' . sql_addslashes($primary->name, TRUE) . '\'') or mysql_die();
                    $true_field_type = mysql_fetch_array($result_type);
                    if (eregi('BLOB', $true_field_type['Type'])) {
                        echo '    <td>[BLOB]</td>' . "\n";
                    } else {
                        if (strlen($row[$i]) > $GLOBALS['cfgLimitChars']) {
                            $row[$i] = substr($row[$i], 0, $GLOBALS['cfgLimitChars']) . '...';
                        }
                        // loic1 : displays <cr>/<lf>
                        //  echo '    <td>' . htmlspecialchars($row[$i]) . '</td>' . "\n";
                        $row[$i]     = ereg_replace("((\015\012)|(\015)|(\012))+", '<br />', htmlspecialchars($row[$i]));
                        echo '    <td>' . $row[$i] . '</td>' . "\n";
                    }
                } else {
                    // loic1 : displays <cr>/<lf>
                    // echo '    <td>' . htmlspecialchars($row[$i]) . '</td>' . "\n";
                    $row[$i] = ereg_replace("((\015\012)|(\015)|(\012))+", '<br />', htmlspecialchars($row[$i]));
                    echo '    <td>' . $row[$i] . '</td>' . "\n";
                }
            } // end for
            // Possibility to have the modify/delete button on the left added
            // Benjamin Gandon -- 2000-08-29
            if ($GLOBALS['cfgModifyDeleteAtRight'] && !$is_simple) {
                ?>
    <td>
        <a href="<?php echo $edit_url; ?>">
            <?php echo $GLOBALS['strEdit']; ?></a>
    </td>
    <td>
        <a href="<?php echo $delete_url; ?>">
            <?php echo $GLOBALS['strDelete']; ?></a>
    </td>
                <?php
                echo "\n";
            } // end if
            ?>
</tr>
            <?php
            echo "\n";
            $foo++;
        } // end while
        ?>
</table>
<br />

        <?php
        echo "\n";
        if (!$is_simple
            && (!isset($SelectNumRows) || $SelectNumRows > 1)) {
            show_table_navigation($pos_next, $pos_prev, $dt_result);
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
        if (MYSQL_MAJOR_VERSION >= 32321) {
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

        $result        = mysql_query('SHOW FIELDS FROM ' . backquote($db) . '.' . backquote($table)) or mysql_die();
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

        $result = mysql_query('SHOW KEYS FROM ' . backquote($db) . '.' . backquote($table)) or mysql_die();
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

        $result = mysql_query('SELECT * FROM ' . backquote($db) . '.' . backquote($table) . $add_query) or mysql_die();
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

        $result     = mysql_query('SELECT * FROM ' . backquote($db) . '.' . backquote($table) . $add_query) or mysql_die();
        $i          = 0;
        $isFirstRow = TRUE;

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
    function get_table_csv($db, $table, $limit_from = 0, $limit_to = 0, $sep, $enc_by, $handler)
    {
        global $what;

        // Handles the "separator" and the optionnal "enclosed by" characters
        if (empty($sep) || $what == 'excel') {
            $sep     = ';';
        }
        else {
            if (get_magic_quotes_gpc()) {
                $sep = stripslashes($sep);
            }
            $sep     = str_replace('\\t', "\011", $sep);
        }
        if (empty($enc_by) || $what == 'excel') {
            $enc_by     = '"';
        }
        else {
            if (get_magic_quotes_gpc()) {
                $enc_by = stripslashes($enc_by);
            }
            $enc_by     = str_replace('&quot;', '"', $enc_by);
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
        $result = mysql_query('SELECT * FROM ' . backquote($db) . '.' . backquote($table) . $add_query) or mysql_die();

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
                    if ($what == 'excel') {
                        $row[$j]     = ereg_replace("\015(\012)?", "\012", $row[$j]);                          
                        $re_test     = "$enc_by|$sep|\012";
                    } else {
                        $re_test     = "[$enc_by$sep]|" . $GLOBALS['add_character'];
                    }
                    if (ereg($re_test, $row[$j])) {
                        $row[$j] = $enc_by . str_replace($enc_by, $enc_by . $enc_by, $row[$j]) . $enc_by;
                    }
                    $schema_insert .= $row[$j];
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
     * Last revision: 2nd August 2001 - Benjamin Gandon
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
                // then check for not escaped end of strings
                if (($char == $string_start)
                    && !(($last_char == '\\') && !$escaped_backslash)) {
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
            // (otherwise, if $i==0 and $sql[$i] == "#", the original order
            // in the second part of the AND bit will fail with illegal index)
            //    if ($sql[$i] == "#" and ($sql[$i-1] == "\n" or $i==0)) {
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
