<?php
/* $Id$ */


/**
 * Misc stuff and functions used by almost all the scripts.
 * Among other things, it contains the advanced authentification work.
 */



if (!defined('PMA_COMMON_LIB_INCLUDED')){
    define('PMA_COMMON_LIB_INCLUDED', 1);

    /**
     * Order of sections for common.lib.php3:
     *
     * in PHP3, functions and constants must be physically defined
     * before they are referenced
     *
     * some functions need the constants of libraries/defines.lib.php3
     * and defines_php.lib.php3
     *
     * the PMA_setFontSizes() function must be before the call to the
     * libraries/auth/cookie.auth.lib.php3 library
     *
     * the include of libraries/defines.lib.php3 must be after the connection
     * to db to get the MySql version
     *
     * the PMA_sqlAddslashes() function must be before the connection to db
     *
     * the authentication libraries must be before the connection to db but
     * after the PMA_isInto() function
     *
     * the PMA_mysqlDie() function must be before the connection to db but
     * after mysql extension has been loaded
     *
     * the PMA_mysqlDie() function needs the PMA_format_sql() Function
     *
     * ... so the required order is:
     *
     * - parsing of the configuration file
     * - first load of the libraries/define.lib.php3 library (won't get the
     *   MySQL release number)
     * - load of mysql extension (if necessary)
     * - definition of PMA_sqlAddslashes()
     * - definition of PMA_format_sql()
     * - definition of PMA_mysqlDie()
     * - definition of PMA_isInto()
     * - definition of PMA_setFontSizes()
     * - loading of an authentication library
     * - db connection
     * - authentication work
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

    /**
     * Detects the config file we want to load
     */
    if (file_exists('./config.inc.developer.php3')) {
        $cfgfile_to_load = './config.inc.developer.php3';
    } else {
        $cfgfile_to_load = './config.inc.php3';
    }

    /**
     * Parses the configuration file and gets some constants used to define
     * versions of phpMyAdmin/php/mysql...
     */    
    $old_error_reporting = error_reporting(0);
    include($cfgfile_to_load);
    // Include failed
    if (!isset($cfgServers) && !isset($cfg['Servers'])) {
        // Creates fake settings
        $cfg = array('DefaultLang' => 'en');
        // Loads the language file
        include('./libraries/select_lang.lib.php3');
        // Sends the Content-Type header
        header('Content-Type: text/html; charset=' . $charset);
        // Displays the error message
        ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $available_languages[$lang][2]; ?>" lang="<?php echo $available_languages[$lang][2]; ?>" dir="<?php echo $text_dir; ?>">

<head>
<title>phpMyAdmin</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>" />
<style type="text/css">
<!--
body  {font-family: sans-serif; font-size: small; color: #000000; background-color: #F5F5F5}
h1    {font-family: sans-serif; font-size: large; font-weight: bold}
//-->
</style>
</head>


<body bgcolor="#ffffff">
<h1>phpMyAdmin - <?php echo $strError; ?></h1>
<p>
    <?php echo $strConfigFileError; ?><br /><br />
    <a href="config.inc.php3" target="_blank">config.inc.php3</a>
</p>
</body>

</html>
        <?php
        exit();
    }
    error_reporting($old_error_reporting);
    unset($old_error_reporting);
    unset($cfgfile_to_load);

    /**
     * Includes compatibility code for older config.inc.php3 revisions
     * if necessary
     */
    if (!isset($cfg['FileRevision']) || (int) substr($cfg['FileRevision'], 13, 3) < 120) {
        include('./libraries/config_import.lib.php3');
    }

    /**
     * Includes the language file if it hasn't been included yet
     */
    if (!defined('PMA_SELECT_LANG_LIB_INCLUDED')){
        include('./libraries/select_lang.lib.php3');
    }

    /**
     * Include MySQL wrappers.
     */
    include('./libraries/mysql_wrappers.lib.php3');

    /**
     * Gets constants that defines the PHP version number.
     * This include must be located physically before any code that needs to
     * reference the constants, else PHP 3.0.16 won't be happy.
     */
    include('./libraries/defines_php.lib.php3');

    /**
     * Charset conversion.
     */
    include('./libraries/charset_conversion.lib.php3');

    /**
     * Gets constants that defines the MySQL version number.
     * This include must be located physically before any code that needs to
     * reference the constants, else PHP 3.0.16 won't be happy; and must be
     * located after we are connected to db to get the MySql version (see
     * below).
     */
    include('./libraries/defines.lib.php3');


    // If zlib output compression is set in the php configuration file, no
    // output buffering should be run
    if (PMA_PHP_INT_VERSION < 40000
        || (PMA_PHP_INT_VERSION >= 40005 && @ini_get('zlib.output_compression'))) {
        $cfg['OBGzip'] = FALSE;
    }


    /**
     * Loads the mysql extensions if it is not loaded yet
     * staybyte - 26. June 2001
     */
    if (((PMA_PHP_INT_VERSION >= 40000 && !@ini_get('safe_mode') && @ini_get('enable_dl'))
        || (PMA_PHP_INT_VERSION < 40000 && PMA_PHP_INT_VERSION > 30009 && !@get_cfg_var('safe_mode')))
        && @function_exists('dl')) {
        if (PMA_PHP_INT_VERSION < 40000) {
            $extension = 'MySQL';
        } else {
            $extension = 'mysql';
        }
        if (PMA_IS_WINDOWS) {
            $suffix = '.dll';
        } else {
            $suffix = '.so';
        }
        if (!@extension_loaded($extension)) {
            @dl($extension . $suffix);
        }
        if (!@extension_loaded($extension)) {
            echo $strCantLoadMySQL . '<br />' . "\n"
                 . '<a href="./Documentation.html#faqmysql" target="documentation">' . $GLOBALS['strDocu'] . '</a>' . "\n";
            exit();
        }
    } // end load mysql extension


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
    function PMA_sqlAddslashes($a_string = '', $is_like = FALSE)
    {
        if ($is_like) {
            $a_string = str_replace('\\', '\\\\\\\\', $a_string);
        } else {
            $a_string = str_replace('\\', '\\\\', $a_string);
        }
        $a_string = str_replace('\'', '\\\'', $a_string);

        return $a_string;
    } // end of the 'PMA_sqlAddslashes()' function


    /**
     * format sql strings
     *
     * @param   string   sql
     *
     * @return  string   the formatted sql
     *
     * @global  array    the configuration array
     * @global  boolean  whether the current statement is a multiple one or not
     *
     * @access  public
     *
     * @author  Mike Beck <mikebeck@users.sourceforge.net>
     */
    function PMA_format_sql ($sql) {
        global $cfg, $mult;

// lem9: bypass this function for now
        return $sql;

        $sfuncs   = '^' . implode('$|^', $cfg['Functions']) . '$';
        $skeyw    = '^' . implode('$|^', $cfg['keywords']) . '$';
        $scoltype = '^' . implode('$|^', $cfg['ColumnTypes']) . '$';
        $add      = '^' . implode('$|^', $cfg['additional']) . '$';

        // First of all lets remove all newlines - we'll add our own later
        $sql  = str_replace("\n", ' ', $sql);
        // There should always be blanks around = and after , ()
        // fixme - i would like to replace ';' with '; ' but then i need
        // to know how to do that without getting ; within strings as well
        $sql  = str_replace('=', ' = ', $sql);
        $sql  = str_replace(',', ', ', $sql);
        $sql  = str_replace(')', ' ) ', $sql);
        $sql  = str_replace('(', ' ( ', $sql);

        // Now split everything by the blanks
        $sql_parts = explode(' ', $sql);
        // Start a loop over the parts check each word and put them back into
        // $sql
        unset($sql);
        $s_nr      = 0;

        while (list($num, $word) = each($sql_parts)) {
            // We might have added to many blanks when checking for = and,
            // which might lead to empty members in the array
            if (strlen($word) == 0) {
                continue;
            }
            $is_string = FALSE;

            // Anything inside quots might be more than one word
            // so as we splitted by the blanks we have to try to get those
            // parts back together
            if ((substr($word, 0, 1) == '\'' || substr($word, 0, 1) == '"')
                && !isset($temp)) {
                //  start of a string
                $temp          = $word;
                $is_string     = TRUE;
            } else {
                if (isset($temp)) {
                    // We are continuing a string
                    $temp      .= $word;
                    $is_string = TRUE;
                 }
            } // end if... else...

            if (substr($word, strlen($word) - 1, 1) == '\''
                || substr($word, strlen($word) - 1, 1) == '"') {
                // End of a String
                $word      = '<font color="' . $cfg['colorStrings'] . '">' . htmlspecialchars($temp) . '</font>';
                unset($temp);
                // Debug echo "fertig " . $word . '<br />';
                $is_string = FALSE;
            } // end if

            if (!isset($is_string) || $is_string == FALSE) {
                // No String
                if (eregi($sfuncs,  $word)) {
                    $word     = '<font color="' . $cfg['colorFunctions'].'">' . htmlspecialchars($word) . '</font>';
                } else if (eregi($skeyw,  $word)) {
                    $word     = '<font color="' . $cfg['colorKeywords'].'">' . htmlspecialchars($word) . '</font>';
                    if (!isset($mult) || $mult != TRUE) {
                        $word = "\n" . $word;
                    }
                } else if (eregi($scoltype, $word)) {
                    $word     = '<font color="' . $cfg['colorColType'].'">' . htmlspecialchars($word) . '</font>';
                } else if (eregi($add, $word)) {
                    $word     = '<font color="' . $cfg['colorAdd'].'">' . htmlspecialchars($word) . '</font>';
                } else if ($word == '(') {
                    if (isset($brack_o)) {
                        $skey = count($brack_o);
                    } else {
                        $skey = 0;
                    }
                    $brack_o[$skey] = $s_nr;
                } else if ($word == ')') {
                    if (isset($brack_o)) {
                        unset($brack_o[count($brack_o) - 1]);
                        if (count($brack_o) == 0) {
                            unset($brack_o);
                        }
                    } else {
                        $brack_c[] = $s_nr;
                    }
                } else if ($word == ';') {
                    $word     = ';' . "\n";
                }
            }

            if (!isset($temp) || strlen($temp) == 0) {
                $sql_p[$s_nr] = $word;
                $s_nr++;
            }
        } // end while

        if (isset($brack_o)) {
            while (list($num, $elem) = each($brack_o)) {
                $sql_p[$elem] = '<font color="red">' . $sql_p[$elem] . '</font>';
                echo '<br /><font color="red">' . $GLOBALS['strMissingBracket'] . '</font><br />';
            }
        }

        if (isset($brack_c)) {
            while (list($num, $elem) = each($brack_c)) {
                $sql_p[$elem] = '<font color="red">' . $sql_p[$elem] . '</font>';
                echo '<br /><font color="red">' . $GLOBALS['strMissingBracket'] . '</font><br />';
            }
        }

        $sql = implode(' ', $_sql_p);
        $sql = ereg_replace("((\015\012)|(\015)|(\012))+", '<br />', $sql);
        $sql = ereg_replace('<br />[ ]*<br />', '<br />', $sql);

        return $sql;
    } // end of the "PMA_format_sql()" function


    /**
     * Displays a MySQL error message in the right frame.
     *
     * @param   string   the error mesage
     * @param   string   the sql query that failed
     * @param   boolean  whether to show a "modify" link or not
     * @param   string   the "back" link url (full path is not required)
     *
     * @global  array    the configuration array
     *
     * @access  public
     */
    function PMA_mysqlDie($error_message = '', $the_query = '',
                          $is_modify_link = TRUE, $back_url = '')
    {
        global $cfg;

        if (empty($GLOBALS['is_header_sent'])) {
            // rabus: If we include header.inc.php3 here, we get a huge set of
            // "Undefined variable" errors (see bug #549570)!
            include('./header.inc.php3');
        }

        if (!$error_message) {
            $error_message = PMA_mysql_error();
        }
        if (!$the_query && !empty($GLOBALS['sql_query'])) {
            $the_query = $GLOBALS['sql_query'];
        }

        echo '<p><b>'. $GLOBALS['strError'] . '</b></p>' . "\n";
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
                     . '<a href="db_details.php3?lang=' . $GLOBALS['lang'] . '&amp;convcharset=' . $GLOBALS['convcharset'] . '&amp;server=' . urlencode($GLOBALS['server']) . '&amp;db=' . urlencode($GLOBALS['db']) . '&amp;sql_query=' . urlencode($the_query) . '&amp;show_query=y">' . $GLOBALS['strEdit'] . '</a>'
                     . ']' . "\n";
            } // end if
            echo '<p>' . "\n"
                 . '    ' . ($cfg['UseSyntaxColoring'] ? PMA_format_sql($query_base) : $query_base) . "\n"
                 . '</p>' . "\n";
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
    } // end of the 'PMA_mysqlDie()' function


    /**
     * Defines whether a string exists inside an array or not
     *
     * @param   string   string to search for
     * @param   mixed    array to search into
     *
     * @return  integer  the rank of the $toFind string in the array or '-1' if
     *                   it hasn't been found
     *
     * @access  public
     */
    function PMA_isInto($toFind = '', &$in)
    {
        $max = count($in);
        for ($i = 0; $i < $max && ($toFind != $in[$i]); $i++) {
            // void();
        }

        return ($i < $max) ? $i : -1;
    }  // end of the 'PMA_isInto()' function


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
    function PMA_setFontSizes()
    {
        global $font_size, $font_bigger, $font_smaller, $font_smallest;

        // IE (<6)/Opera for win case: needs smaller fonts than anyone else
        if (PMA_USR_OS == 'Win'
            && ((PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER < 6) || PMA_USR_BROWSER_AGENT == 'OPERA')) {
            $font_size     = 'x-small';
            $font_bigger   = 'large';
            $font_smaller  = '90%';
            $font_smallest = '7pt';
        }
        // IE6 and other browsers for win case
        else if (PMA_USR_OS == 'Win') {
            $font_size     = 'small';
            $font_bigger   = 'large';
            $font_smaller  = (PMA_USR_BROWSER_AGENT == 'IE')
                           ? '90%'
                           : 'x-small';
            $font_smallest = 'x-small';
        }
        // Some mac browsers need also smaller default fonts size (OmniWeb &
        // Opera)...
        else if (PMA_USR_OS == 'Mac'
                 && (PMA_USR_BROWSER_AGENT == 'OMNIWEB' || PMA_USR_BROWSER_AGENT == 'OPERA')) {
            $font_size     = 'x-small';
            $font_bigger   = 'large';
            $font_smaller  = '90%';
            $font_smallest = '7pt';
        }
        // ... but most of them (except IE 5+ & NS 6+) need bigger fonts
        else if (PMA_USR_OS == 'Mac'
                 && ((PMA_USR_BROWSER_AGENT != 'IE' && PMA_USR_BROWSER_AGENT != 'MOZILLA')
                     || PMA_USR_BROWSER_VER < 5)) {
            $font_size     = 'medium';
            $font_bigger   = 'x-large';
            $font_smaller  = 'small';
            $font_smallest = 'x-small';
        }
        // OS/2 browser
        else if (PMA_USR_OS == 'OS/2'
                 && PMA_USR_BROWSER_AGENT == 'OPERA') {
            $font_size     = 'small';
            $font_bigger   = 'medium';
            $font_smaller  = 'x-small';
            $font_smallest = 'x-small';
        }
        else {
            $font_size     = 'small';
            $font_bigger   = 'large';
            $font_smaller  = 'x-small';
            $font_smallest = 'x-small';
        }

        return true;
    } // end of the 'PMA_setFontSizes()' function


    /**
     * $cfg['PmaAbsoluteUri'] is a required directive else cookies won't be
     * set properly and, depending on browsers, inserting or updating a
     * record might fail
     */
    $display_pmaAbsoluteUri_warning = 0;

    // Olivier: Setup a default value to let the people and lazy syadmins
    //          work anyway, but display a big warning on the main.php3
    //          page.
    if (empty($cfg['PmaAbsoluteUri'])) {
        $port_in_HTTP_HOST              = (strpos($HTTP_SERVER_VARS['HTTP_HOST'], ':') > 0);
        $cfg['PmaAbsoluteUri']          = (!empty($HTTP_SERVER_VARS['HTTPS']) ? 'https' : 'http') . '://'
                                        . $HTTP_SERVER_VARS['HTTP_HOST']
                                        . ((!empty($HTTP_SERVER_VARS['SERVER_PORT']) && !$port_in_HTTP_HOST) ? ':' . $HTTP_SERVER_VARS['SERVER_PORT'] : '')
                                        . substr($PHP_SELF, 0, strrpos($PHP_SELF, '/') + 1);
        // We display the warning by default, but not if it is disabled thru
        // via the $cfg['PmaAbsoluteUri_DisableWarning'] variable.
        // This is intended for sysadmins that actually want the default
        // behaviour of auto-detection due to their setup.
        // See the mailing list message:
        // http://sourceforge.net/mailarchive/forum.php?thread_id=859093&forum_id=2141
        if ($cfg['PmaAbsoluteUri_DisableWarning'] == FALSE) {
            $display_pmaAbsoluteUri_warning = 1;
        }
    }
    // Adds a trailing slash et the end of the phpMyAdmin uri if it does not
    // exist
    else if (substr($cfg['PmaAbsoluteUri'], -1) != '/') {
        $cfg['PmaAbsoluteUri'] .= '/';
    }


    /**
     * Make sure $cfg['DefaultTabDatabase'] and $cfg['DefaultTabTable'] are set.
     * Todo: check if it is set to a *valid* value.
     */
    if (empty($cfg['DefaultTabDatabase'])) {
        $cfg['DefaultTabDatabase'] = 'db_details_structure.php3';
    }

    if (empty($cfg['DefaultTabTable'])) {
        $cfg['DefaultTabTable']    = 'tbl_properties_structure.php3';
    }


    /**
     * Use mysql_connect() or mysql_pconnect()?
     */
    $connect_func = ($cfg['PersistentConnections']) ? 'mysql_pconnect' : 'mysql_connect';
    $dblist       = array();


    /**
     * Gets the valid servers list and parameters
     */
    reset($cfg['Servers']);
    while (list($key, $val) = each($cfg['Servers'])) {
        // Don't use servers with no hostname
        if (empty($val['host'])) {
            unset($cfg['Servers'][$key]);
        }
    }

    if (empty($server) || !isset($cfg['Servers'][$server]) || !is_array($cfg['Servers'][$server])) {
        $server = $cfg['ServerDefault'];
    }


    /**
     * If no server is selected, make sure that $cfg['Server'] is empty (so
     * that nothing will work), and skip server authentication.
     * We do NOT exit here, but continue on without logging into any server.
     * This way, the welcome page will still come up (with no server info) and
     * present a choice of servers in the case that there are multiple servers
     * and '$cfg['ServerDefault'] = 0' is set.
     */
    if ($server == 0) {
        $cfg['Server'] = array();
    }

    /**
     * Otherwise, set up $cfg['Server'] and do the usual login stuff.
     */
    else if (isset($cfg['Servers'][$server])) {
        $cfg['Server'] = $cfg['Servers'][$server];

        // Check how the config says to connect to the server
        $server_port   = (empty($cfg['Server']['port']))
                       ? ''
                       : ':' . $cfg['Server']['port'];
        if (strtolower($cfg['Server']['connect_type']) == 'tcp') {
            $cfg['Server']['socket'] = '';
        }
        $server_socket = (empty($cfg['Server']['socket']) || PMA_PHP_INT_VERSION < 30010)
                       ? ''
                       : ':' . $cfg['Server']['socket'];

        // Gets the authentication library that fits the $cfg['Server'] settings
        // and run authentication
        include('./libraries/auth/' . $cfg['Server']['auth_type'] . '.auth.lib.php3');
        if (!PMA_auth_check()) {
            PMA_auth();
        } else {
            PMA_auth_set_user();
        }

        // Check IP-based Allow/Deny rules as soon as possible to reject the
        // user
        // Based on mod_access in Apache:
        // http://cvs.apache.org/viewcvs.cgi/httpd-2.0/modules/aaa/mod_access.c?rev=1.37&content-type=text/vnd.viewcvs-markup
        // Look at: "static int check_dir_access(request_rec *r)"
        // Robbat2 - May 10, 2002
        if (isset($cfg['Server']['AllowDeny']) && $cfg['Server']['AllowDeny']['order']) {
            include('./libraries/ip_allow_deny.lib.php3');

            $allowDeny_forbidden         = FALSE; // default
            if ($cfg['Server']['AllowDeny']['order'] == 'allow,deny') {
                $allowDeny_forbidden     = TRUE;
                if (PMA_allowDeny('allow')) {
                    $allowDeny_forbidden = FALSE;
                }
                if (PMA_allowDeny('deny')) {
                    $allowDeny_forbidden = TRUE;
                }
            } else if ($cfg['Server']['AllowDeny']['order'] == 'deny,allow') {
                if (PMA_allowDeny('deny')) {
                    $allowDeny_forbidden = TRUE;
                }
                if (PMA_allowDeny('allow')) {
                    $allowDeny_forbidden = FALSE;
                }
            } else if ($cfg['Server']['AllowDeny']['order'] == 'explicit') {
                if (PMA_allowDeny('allow')
                    && !PMA_allowDeny('deny')) {
                    $allowDeny_forbidden = FALSE;
                } else {
                    $allowDeny_forbidden = TRUE;
                }
            } // end if... else if... else if

            // Ejects the user if banished
            if ($allowDeny_forbidden) {
               PMA_auth_fails();
            }
            unset($allowDeny_forbidden); //Clean up after you!
        } // end if

        // The user can work with only some databases
        if (isset($cfg['Server']['only_db']) && $cfg['Server']['only_db'] != '') {
            if (is_array($cfg['Server']['only_db'])) {
                $dblist   = $cfg['Server']['only_db'];
            } else {
                $dblist[] = $cfg['Server']['only_db'];
            }
        } // end if

        if (PMA_PHP_INT_VERSION >= 40000) {
            $bkp_track_err = @ini_set('track_errors', 1);
        }

        // Try to connect MySQL with the control user profile (will be used to
        // get the privileges list for the current user but the true user link
        // must be open after this one so it would be default one for all the
        // scripts)
        if ($cfg['Server']['controluser'] != '') {
            $dbh                = @$connect_func(
                                      $cfg['Server']['host'] . $server_port . $server_socket,
                                      $cfg['Server']['controluser'],
                                      $cfg['Server']['controlpass']
                                  );
            if ($dbh == FALSE) {
                if (PMA_mysql_error()) {
                    $conn_error = PMA_mysql_error();
                } else if (isset($php_errormsg)) {
                    $conn_error = $php_errormsg;
                } else {
                    $conn_error = 'Cannot connect: invalid settings.';
                }
                $local_query    = $connect_func . '('
                                . $cfg['Server']['host'] . $server_port . $server_socket . ', '
                                . $cfg['Server']['controluser'] . ', '
                                . $cfg['Server']['controlpass'] . ')';
                if (empty($GLOBALS['is_header_sent'])) {
                    include('./header.inc.php3');
                }
                PMA_mysqlDie($conn_error, $local_query, FALSE);
            } // end if
        } // end if

        // Pass #1 of DB-Config to read in master level DB-Config will go here
        // Robbat2 - May 11, 2002

        // Connects to the server (validates user's login)
        $userlink               = @$connect_func(
                                      $cfg['Server']['host'] . $server_port . $server_socket,
                                      $cfg['Server']['user'],
                                      $cfg['Server']['password']
                                  );
        if ($userlink == FALSE) {
            PMA_auth_fails();
        } // end if

        // Pass #2 of DB-Config to read in user level DB-Config will go here
        // Robbat2 - May 11, 2002

        if (PMA_PHP_INT_VERSION >= 40000) {
            @ini_set('track_errors', $bkp_track_err);
        }

        // If controluser isn't defined, use the current user settings to get
        // his rights
        if ($cfg['Server']['controluser'] == '') {
            $dbh = $userlink;
        }

        // Runs the "defines.lib.php3" for the second time to get the mysql
        // release number
        include('./libraries/defines.lib.php3');

        // if 'only_db' is set for the current user, there is no need to check for
        // available databases in the "mysql" db
        $dblist_cnt = count($dblist);
        if ($dblist_cnt) {
            $true_dblist  = array();
            $is_show_dbs  = TRUE;
            for ($i = 0; $i < $dblist_cnt; $i++) {
                if ($is_show_dbs && ereg('(^|[^\])(_|%)', $dblist[$i])) {
                    $local_query = 'SHOW DATABASES LIKE \'' . $dblist[$i] . '\'';
                    $rs          = PMA_mysql_query($local_query, $dbh);
                    // "SHOW DATABASES" statement is disabled
                    if ($i == 0
                        && (PMA_mysql_error() && mysql_errno() == 1045)) {
                        $true_dblist[] = str_replace('\\_', '_', str_replace('\\%', '%', $dblist[$i]));
                        $is_show_dbs   = FALSE;
                    }
                    // Debug
                    // else if (PMA_mysql_error()) {
                    //    PMA_mysqlDie('', $local_query, FALSE);
                    // }
                    while ($row = @PMA_mysql_fetch_row($rs)) {
                        $true_dblist[] = $row[0];
                    } // end while
                    if ($rs) {
                        mysql_free_result($rs);
                    }
                } else {
                    $true_dblist[]     = str_replace('\\_', '_', str_replace('\\%', '%', $dblist[$i]));
                } // end if... else...
            } // end for
            $dblist       = $true_dblist;
            unset($true_dblist);
        } // end if

        // 'only_db' is empty for the current user...
        else {
            // ... first checks whether the "safe_show_database" is on or not
            //     (if MYSQL supports this)
            if (PMA_MYSQL_INT_VERSION >= 32330) {
                $local_query      = 'SHOW VARIABLES LIKE \'safe_show_database\'';
                $rs               = PMA_mysql_query($local_query, $dbh); // Debug: or PMA_mysqlDie('', $local_query, FALSE);
                $is_safe_show_dbs = ($rs) ? @PMA_mysql_result($rs, 0, 'Value') : FALSE;

                // ... and if on, try to get the available dbs list
                if ($is_safe_show_dbs && strtoupper($is_safe_show_dbs) != 'OFF') {
                    $uva_alldbs   = mysql_list_dbs($userlink);
                    while ($uva_row = PMA_mysql_fetch_array($uva_alldbs)) {
                        $dblist[] = $uva_row[0];
                    } // end while
                    $dblist_cnt   = count($dblist);
                    unset($uva_alldbs);
                    mysql_free_result($rs);
                } // end if ($is_safe_show_dbs)
            } //end if (PMA_MYSQL_INT_VERSION)

            // ... else checks for available databases in the "mysql" db
            if (!$dblist_cnt) {
                $auth_query   = 'SELECT User, Select_priv '
                              . 'FROM mysql.user '
                              . 'WHERE User = \'' . PMA_sqlAddslashes($cfg['Server']['user']) . '\'';
                $rs           = PMA_mysql_query($auth_query, $dbh); // Debug: or PMA_mysqlDie('', $auth_query, FALSE);
            } // end
        } // end if (!$dblist_cnt)

        // Access to "mysql" db allowed and dblist still empty -> gets the
        // usable db list
        if (!$dblist_cnt
            && ($rs && @mysql_numrows($rs))) {
            $row = PMA_mysql_fetch_array($rs);
            mysql_free_result($rs);
            // Correction uva 19991215
            // Previous code assumed database "mysql" admin table "db" column
            // "db" contains literal name of user database, and works if so.
            // Mysql usage generally (and uva usage specifically) allows this
            // column to contain regular expressions (we have all databases
            // owned by a given student/faculty/staff beginning with user i.d.
            // and governed by default by a single set of privileges with
            // regular expression as key). This breaks previous code.
            // This maintenance is to fix code to work correctly for regular
            // expressions.
            if ($row['Select_priv'] != 'Y') {

                // 1. get allowed dbs from the "mysql.db" table
                // lem9: User can be blank (anonymous user)
                $local_query = 'SELECT DISTINCT Db FROM mysql.db WHERE Select_priv = \'Y\' AND (User = \'' . PMA_sqlAddslashes($cfg['Server']['user']) . '\' OR User = \'\')';
                $rs          = PMA_mysql_query($local_query, $dbh); // Debug: or PMA_mysqlDie('', $local_query, FALSE);
                if ($rs && @mysql_numrows($rs)) {
                    // Will use as associative array of the following 2 code
                    // lines:
                    //   the 1st is the only line intact from before
                    //     correction,
                    //   the 2nd replaces $dblist[] = $row['Db'];
                    $uva_mydbs = array();
                    // Code following those 2 lines in correction continues
                    // populating $dblist[], as previous code did. But it is
                    // now populated with actual database names instead of
                    // with regular expressions.
                    while ($row = PMA_mysql_fetch_array($rs)) {
                        // loic1: all databases cases - part 1
                        if (empty($row['Db']) || $row['Db'] == '%') {
                            $uva_mydbs['%'] = 1;
                            break;
                        }
                        // loic1: avoid multiple entries for dbs
                        if (!isset($uva_mydbs[$row['Db']])) {
                            $uva_mydbs[$row['Db']] = 1;
                        }
                    } // end while
                    mysql_free_result($rs);
                    $uva_alldbs = mysql_list_dbs($dbh);
                    // loic1: all databases cases - part 2
                    if (isset($uva_mydbs['%'])) {
                        while ($uva_row = PMA_mysql_fetch_array($uva_alldbs)) {
                            $dblist[] = $uva_row[0];
                        } // end while
                    } // end if
                    else {
                        while ($uva_row = PMA_mysql_fetch_array($uva_alldbs)) {
                            $uva_db = $uva_row[0];
                            if (isset($uva_mydbs[$uva_db]) && $uva_mydbs[$uva_db] == 1) {
                                $dblist[]           = $uva_db;
                                $uva_mydbs[$uva_db] = 0;
                            } else if (!isset($dblist[$uva_db])) {
                                reset($uva_mydbs);
                                while (list($uva_matchpattern, $uva_value) = each($uva_mydbs)) {
                                    // loic1: fixed bad regexp
                                    // TODO: db names may contain characters
                                    //       that are regexp instructions
                                    $re        = '(^|(\\\\\\\\)+|[^\])';
                                    $uva_regex = ereg_replace($re . '%', '\\1.*', ereg_replace($re . '_', '\\1.{1}', $uva_matchpattern));
                                    // Fixed db name matching
                                    // 2000-08-28 -- Benjamin Gandon
                                    if (ereg('^' . $uva_regex . '$', $uva_db)) {
                                        $dblist[] = $uva_db;
                                        break;
                                    }
                                } // end while
                            } // end if ... else if....
                        } // end while
                    } // end else
                    mysql_free_result($uva_alldbs);
                    unset($uva_mydbs);
                } // end if

                // 2. get allowed dbs from the "mysql.tables_priv" table
                $local_query = 'SELECT DISTINCT Db FROM mysql.tables_priv WHERE Table_priv LIKE \'%Select%\' AND User = \'' . PMA_sqlAddslashes($cfg['Server']['user']) . '\'';
                $rs          = PMA_mysql_query($local_query, $dbh); // Debug: or PMA_mysqlDie('', $local_query, FALSE);
                if ($rs && @mysql_numrows($rs)) {
                    while ($row = PMA_mysql_fetch_array($rs)) {
                        if (PMA_isInto($row['Db'], $dblist) == -1) {
                            $dblist[] = $row['Db'];
                        }
                    } // end while
                    mysql_free_result($rs);
                } // end if
            } // end if
        } // end building available dbs from the "mysql" db

    } // end server connecting

    /**
     * Missing server hostname
     */
    else {
        echo $strHostEmpty;
    }


    /**
     * Get the list and number of available databases.
     *
     * @param   string   the url to go back to in case of error
     *
     * @return  boolean  always true
     *
     * @global  array    the list of available databases
     * @global  integer  the number of available databases
     */
    function PMA_availableDatabases($error_url = '')
    {
        global $dblist;
        global $num_dbs;

        $num_dbs = count($dblist);

        // 1. A list of allowed databases has already been defined by the
        //    authentification process -> gets the available databases list
        if ($num_dbs) {
            $true_dblist = array();
            for ($i = 0; $i < $num_dbs; $i++) {
                $dblink  = @PMA_mysql_select_db($dblist[$i]);
                if ($dblink) {
                    $true_dblist[] = $dblist[$i];
                } // end if
            } // end for
            $dblist      = array();
            $dblist      = $true_dblist;
            unset($true_dblist);
            $num_dbs     = count($dblist);
        } // end if

        // 2. Allowed database list is empty -> gets the list of all databases
        //    on the server
        else {
            $dbs          = mysql_list_dbs() or PMA_mysqlDie('', 'mysql_list_dbs()', FALSE, $error_url);
            $num_dbs      = ($dbs) ? @mysql_num_rows($dbs) : 0;
            $real_num_dbs = 0;
            for ($i = 0; $i < $num_dbs; $i++) {
                $db_name_tmp = PMA_mysql_dbname($dbs, $i);
                $dblink      = @PMA_mysql_select_db($db_name_tmp);
                if ($dblink) {
                    $dblist[] = $db_name_tmp;
                    $real_num_dbs++;
                }
            } // end for
            mysql_free_result($dbs);
            $num_dbs = $real_num_dbs;
        } // end else

        return TRUE;
    } // end of the 'PMA_availableDatabases()' function



    /* ----------------------- Set of misc functions ----------------------- */


    /**
     * Adds backquotes on both sides of a database, table or field name.
     * Since MySQL 3.23.6 this allows to use non-alphanumeric characters in
     * these names.
     *
     * @param   mixed    the database, table or field name to "backquote" or
     *                   array of it
     * @param   boolean  a flag to bypass this function (used by dump
     *                   functions)
     *
     * @return  mixed    the "backquoted" database, table or field name if the
     *                   current MySQL release is >= 3.23.6, the original one
     *                   else
     *
     * @access  public
     */
    function PMA_backquote($a_name, $do_it = TRUE)
    {
        if ($do_it
            && PMA_MYSQL_INT_VERSION >= 32306
            && !empty($a_name) && $a_name != '*') {

            if (is_array($a_name)) {
                 $result = array();
                 reset($a_name);
                 while(list($key, $val) = each($a_name)) {
                     $result[$key] = '`' . $val . '`';
                 }
                 return $result;
            } else {
                return '`' . $a_name . '`';
            }
        } else {
            return $a_name;
        }
    } // end of the 'PMA_backquote()' function


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
    function PMA_jsFormat($a_string = '', $add_backquotes = TRUE)
    {
        if (is_string($a_string)) {
            $a_string = str_replace('"', '&quot;', $a_string);
            $a_string = str_replace('\\', '\\\\', $a_string);
            $a_string = str_replace('\'', '\\\'', $a_string);
            $a_string = str_replace('#', '\\#', $a_string);
            $a_string = str_replace("\012", '\\\\n', $a_string);
            $a_string = str_replace("\015", '\\\\r', $a_string);
        }

        return (($add_backquotes) ? PMA_backquote($a_string) : $a_string);
    } // end of the 'PMA_jsFormat()' function


    /**
     * Defines the <CR><LF> value depending on the user OS.
     *
     * @return  string   the <CR><LF> value to use
     *
     * @access  public
     */
    function PMA_whichCrlf()
    {
        $the_crlf = "\n";

        // The 'PMA_USR_OS' constant is defined in "./libraries/defines.lib.php3"
        // Win case
        if (PMA_USR_OS == 'Win') {
            $the_crlf = "\r\n";
        }
        // Mac case
        else if (PMA_USR_OS == 'Mac') {
            $the_crlf = "\r";
        }
        // Others
        else {
            $the_crlf = "\n";
        }

        return $the_crlf;
    } // end of the 'PMA_whichCrlf()' function


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
    function PMA_countRecords($db, $table, $ret = FALSE)
    {
        $result = PMA_mysql_query('SELECT COUNT(*) AS num FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table));
        $num    = ($result) ? PMA_mysql_result($result, 0, 'num') : 0;
        mysql_free_result($result);
        if ($ret) {
            return $num;
        } else {
            echo number_format($num, 0, $GLOBALS['number_decimal_separator'], $GLOBALS['number_thousands_separator']);
            return TRUE;
        }
    } // end of the 'PMA_countRecords()' function


    /**
     * Displays a message at the top of the "main" (right) frame
     *
     * @param   string  the message to display
     *
     * @global  array   the configuration array
     *
     * @access  public
     */
    function PMA_showMessage($message)
    {
        global $cfg;

        // Reloads the navigation frame via JavaScript if required
        if (isset($GLOBALS['reload']) && $GLOBALS['reload']) {
            echo "\n";
            $reload_url = './left.php3'
                        . '?lang=' . $GLOBALS['lang']
                        . '&convcharset=' . $GLOBALS['convcharset']
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

        // Corrects the tooltip text via JS if required
        else if (isset($GLOBALS['table']) && $cfg['ShowTooltip'] && PMA_MYSQL_INT_VERSION >= 32303) {
            $result = @PMA_mysql_query('SHOW TABLE STATUS FROM ' . PMA_backquote($GLOBALS['db']) . ' LIKE \'' . PMA_sqlAddslashes($GLOBALS['table'], TRUE) . '\'');
            if ($result) {
                $tmp     = PMA_mysql_fetch_array($result, MYSQL_ASSOC);
                $tooltip = (empty($tmp['Comment']))
                         ? ''
                         : $tmp['Comment'] . ' ';
                $tooltip .= '(' . $tmp['Rows'] . ' ' . $GLOBALS['strRows'] . ')';
                mysql_free_result($result);
                unset($tmp);
                $md5_tbl = md5($GLOBALS['table']);
                echo "\n";
                ?>
<script type="text/javascript" language="javascript1.2">
<!--
if (typeof(document.getElementById) != 'undefined'
    && typeof(window.parent.frames['nav']) != 'undefined'
    && typeof(window.parent.frames['nav'].document.getElementById('<?php echo $md5_tbl; ?>')) != 'undefined'
    && typeof(window.parent.frames['nav'].document.getElementById('<?php echo $md5_tbl; ?>').title) == 'string') {
    window.parent.frames['nav'].document.getElementById('<?php echo $md5_tbl; ?>').title = '<?php echo PMA_jsFormat($tooltip, FALSE); ?>';
}
//-->
</script>
                <?php
            } // end if
        } // end if... else if

        echo "\n";
        ?>
<div align="<?php echo $GLOBALS['cell_align_left']; ?>">
    <table border="<?php echo $cfg['Border']; ?>" cellpadding="5">
    <tr>
        <td bgcolor="<?php echo $cfg['ThBgcolor']; ?>">
            <b><?php echo (get_magic_quotes_gpc()) ? stripslashes($message) : $message; ?></b><br />
        </td>
    </tr>
        <?php
        if ($cfg['ShowSQL'] == TRUE && !empty($GLOBALS['sql_query'])) {
            // Basic url query part
            $url_qpart = '?convcharset=' . $GLOBALS['convcharset']
                       . '&amp;lang=' . $GLOBALS['lang']
                       . '&amp;server=' . $GLOBALS['server']
                       . ((!empty($GLOBALS['db'])) ? '&amp;db=' . urlencode($GLOBALS['db']) : '')
                       . ((!empty($GLOBALS['table'])) ? '&amp;table=' . urlencode($GLOBALS['table']) : '');

            echo "\n";
            ?>
    <tr>
        <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
            <?php
            echo "\n";
            // Html format the query to be displayed
            // The nl2br function isn't used because its result isn't a valid
            // xhtml1.0 statement before php4.0.5 ("<br>" and not "<br />")
            // If we want to show some sql code it is easiest to create it here
            $sqlnr = 1;
            if (!empty($GLOBALS['show_as_php'])) {
                $new_line = '&quot;;<br />' . "\n" . '            $sql .= &quot;';
            } else if ($cfg['UseSyntaxColoring'] == FALSE) {
                $new_line = '<br />' . "\n";
            }
            if (isset($new_line)) {
                $query_base = htmlspecialchars($GLOBALS['sql_query']);
                $query_base = ereg_replace("((\015\012)|(\015)|(\012))+", $new_line, $query_base);
            } else {
                $query_base = $GLOBALS['sql_query'];
            }
            if (!empty($GLOBALS['show_as_php'])) {
                $query_base = '$sql  = &quot;' . $query_base;
            } else if ($cfg['UseSyntaxColoring']) {
                $query_base = PMA_format_sql($query_base);
            }

            // Prepares links that may be displayed to edit/explain the query
            if (!isset($GLOBALS['show_query']) || $GLOBALS['show_query'] != 'y') {
                if (!isset($GLOBALS['goto'])) {
                    $edit_target = (isset($GLOBALS['table'])) ? 'tbl_properties.php3' : 'db_details.php3';
                } else if ($GLOBALS['goto'] != 'main.php3') {
                    $edit_target = $GLOBALS['goto'];
                } else {
                    $edit_target = '';
                }
                if ($edit_target == 'tbl_properties.php3') {
                    $edit_link = '<a href="tbl_properties.php3'
                               . $url_qpart
                               . '&amp;sql_query=' . urlencode($GLOBALS['sql_query']) . '&amp;show_query=y#querybox">' . $GLOBALS['strEdit'] . '</a>';
                } else if ($edit_target != '') {
                    $edit_link = '<a href="db_details.php3'
                               . $url_qpart
                               . '&amp;sql_query=' . urlencode($GLOBALS['sql_query']) . '&amp;show_query=y#querybox">' . $GLOBALS['strEdit'] . '</a>';
                }
                // Want to have the query explained (Mike Beck 2002-05-22)
                // but only explain a SELECT (that has not been explained)
                if (eregi('^SELECT[[:space:]]+', $GLOBALS['sql_query'])) {
                    $explain_link = '[<a href="sql.php3'
                                  . $url_qpart
                                  . '&amp;sql_query=' . urlencode('EXPLAIN ' . $GLOBALS['sql_query']) . '">' . $GLOBALS['strExplain'] . '</a>]&nbsp;';
                } else {
                    $explain_link = '';
                }
                // Also we would like to get the SQL formed in some nice
                // php-code (Mike Beck 2002-05-22)
                if (!empty($GLOBALS['show_as_php'])) {
                    $php_link = '<a href="sql.php3'
                              . $url_qpart
                              . '&amp;sql_query=' . urlencode($GLOBALS['sql_query']) . '&amp;show_as_php=0">' . $GLOBALS['strNoPhp'] . '</a>';
                } else {
                    $php_link = '<a href="sql.php3'
                              . $url_qpart
                              . '&amp;sql_query=' . urlencode($GLOBALS['sql_query']) . '&amp;show_as_php=1">' . $GLOBALS['strPhp'] . '</a>';
                }
            } // end if (prepare links)

            // Displays the message
            echo '            ' . $GLOBALS['strSQLQuery'] . '&nbsp;:';
            if (!empty($edit_target)) {
                echo '&nbsp;[' . $edit_link . ']&nbsp;' . $explain_link . '[' . $php_link . ']<br />' . "\n";
            } else {
                echo '<br />' . "\n";
            }
            echo '            ' . $query_base;
            // If a 'LIMIT' clause has been programatically added to the query
            // displays it
            if (!empty($GLOBALS['sql_limit_to_append'])) {
                if($cfg['UseSyntaxColoring']) {
                    echo PMA_format_sql($GLOBALS['sql_limit_to_append']);
                } else {
                    echo $GLOBALS['sql_limit_to_append'];
                }
            }
            if (!empty($GLOBALS['show_as_php'])) {
                echo '&quot;;';
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
    } // end of the 'PMA_showMessage()' function


    /**
     * Displays a link to the official MySQL documentation (short)
     *
     * @param   string  an anchor to move to
     *
     * @return  string  the html link
     *
     * @access  public
     */
    function PMA_showDocuShort($link)
    {
        if (!empty($GLOBALS['cfg']['ManualBaseShort'])) {
            return '[<a href="' . $GLOBALS['cfg']['ManualBaseShort'] . '/' . $link .'" target="mysql_doc">' . $GLOBALS['strDocu'] . '</a>]';
        }
    } // end of the 'PMA_showDocuShort()' function


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
     * @version  1.2 - 18 July 2002
     */
    function PMA_formatByteDown($value, $limes = 6, $comma = 0)
    {
        $dh           = pow(10, $comma);
        $li           = pow(10, $limes);
        $return_value = $value;
        $unit         = $GLOBALS['byteUnits'][0];

        for ( $d = 6, $ex = 15; $d >= 1; $d--, $ex-=3 ) {
            if (isset($GLOBALS['byteUnits'][$d]) && $value >= $li * pow(10, $ex)) {
                $value = round($value / ( pow(1024, $d) / $dh) ) /$dh;
                $unit = $GLOBALS['byteUnits'][$d];
                break 1;
            } // end if
        } // end for

        if ($unit != $GLOBALS['byteUnits'][0]) {
            $return_value = number_format($value, $comma, $GLOBALS['number_decimal_separator'], $GLOBALS['number_thousands_separator']);
        } else {
            $return_value = number_format($value, 0, $GLOBALS['number_decimal_separator'], $GLOBALS['number_thousands_separator']);
        }

        return array($return_value, $unit);
    } // end of the 'PMA_formatByteDown' function


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
    function PMA_checkReservedWords($the_name, $error_url)
    {
        // The name contains caracters <> a-z, A-Z and "_" -> not a reserved
        // word
        if (!ereg('^[a-zA-Z_]+$', $the_name)) {
            return TRUE;
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
                    PMA_mysqlDie(sprintf($GLOBALS['strInvalidName'], $the_name), '', FALSE, $error_url);
                } // end if
            } // end for
        } // end if
    } // end of the 'PMA_checkReservedWords' function


    /**
     * Writes localised date
     *
     * @param   string   the current timestamp
     *
     * @return  string   the formatted date
     *
     * @access  public
     */
    function PMA_localisedDate($timestamp = -1)
    {
        global $datefmt, $month, $day_of_week;

        if ($timestamp == -1) {
            $timestamp = time();
        }

        $date = ereg_replace('%[aA]', $day_of_week[(int)strftime('%w', $timestamp)], $datefmt);
        $date = ereg_replace('%[bB]', $month[(int)strftime('%m', $timestamp)-1], $date);

        return strftime($date, $timestamp);
    } // end of the 'PMA_localisedDate()' function


    /**
     * Prints out a tab for tabbed navigation.
     * If the variables $link and $args ar left empty, an inactive tab is created
     *
     * @param   string  the text to be displayed as link
     * @param   string  main link file, e.g. "test.php3"
     * @param   string  link arguments
     * @param   string  link attributes
     *
     * @return  string  two table cells, the first beeing a separator, the second the tab itself
     *
     * @access  public
     */
    function PMA_printTab($text, $link, $args = '', $attr = '') {
        global $PHP_SELF;
        global $db_details_links_count_tabs;

        if (basename($PHP_SELF) == $link
            && ($text != $GLOBALS['strEmpty'] && $text != $GLOBALS['strDrop'])) {
            $bgcolor = 'silver';
        } else {
            $bgcolor = '#DFDFDF';
        }

        $db_details_links_count_tabs++;
        if (!empty($attr)) {
            $attr = ' ' . $attr;
        }

        $out     = "\n" . '        '
                 . '<td bgcolor="' . $bgcolor . '" align="center" width="64" nowrap="nowrap" class="tab">'
                 . "\n" . '            ';
        if (strlen($link) > 0) {
            $out .= '<a href="' . $link . '?' . $args . '"' . $attr . '>'
                 .  '<b>' . $text . '</b></a>';
        } else {
            $out .= '<b>' . $text . '</b>';
        }
        $out     .= "\n" . '        '
                 .  '</td>'
                 .  "\n" . '        '
                 .  '<td width="8">&nbsp;</td>';

        return $out;
    } // end of the 'PMA_printTab()' function



    // Kanji encoding convert feature appended by Y.Kawada (2002/2/20)
    if (PMA_PHP_INT_VERSION >= 40006
        && @function_exists('mb_convert_encoding')
        && strpos(' ' . $lang, 'ja-')
        && file_exists('./libraries/kanji-encoding.lib.php3')) {
        include('./libraries/kanji-encoding.lib.php3');
    } // end if

} // $__PMA_COMMON_LIB__
?>
