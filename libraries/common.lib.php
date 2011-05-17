<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Misc functions used all over the scripts.
 *
 * @package phpMyAdmin
 */

/**
 * Exponential expression / raise number into power
 *
 * @uses    function_exists()
 * @uses    bcpow()
 * @uses    gmp_pow()
 * @uses    gmp_strval()
 * @uses    pow()
 * @param   number  $base
 * @param   number  $exp
 * @param   string  pow function use, or false for auto-detect
 * @return  mixed  string or float
 */
function PMA_pow($base, $exp, $use_function = false)
{
    static $pow_function = null;

    if (null == $pow_function) {
        if (function_exists('bcpow')) {
            // BCMath Arbitrary Precision Mathematics Function
            $pow_function = 'bcpow';
        } elseif (function_exists('gmp_pow')) {
            // GMP Function
            $pow_function = 'gmp_pow';
        } else {
            // PHP function
            $pow_function = 'pow';
        }
    }

    if (! $use_function) {
        $use_function = $pow_function;
    }

    if ($exp < 0 && 'pow' != $use_function) {
        return false;
    }
    switch ($use_function) {
        case 'bcpow' :
            // bcscale() needed for testing PMA_pow() with base values < 1
            bcscale(10);
            $pow = bcpow($base, $exp);
            break;
        case 'gmp_pow' :
             $pow = gmp_strval(gmp_pow($base, $exp));
            break;
        case 'pow' :
            $base = (float) $base;
            $exp = (int) $exp;
            $pow = pow($base, $exp);
            break;
        default:
            $pow = $use_function($base, $exp);
    }

    return $pow;
}

/**
 * string PMA_getIcon(string $icon)
 *
 * @uses    $GLOBALS['pmaThemeImage']
 * @uses    $GLOBALS['cfg']['PropertiesIconic']
 * @uses    htmlspecialchars()
 * @param   string  $icon   name of icon file
 * @param   string  $alternate alternate text
 * @param   boolean $container include in container
 * @param   boolean $$force_text whether to force alternate text to be displayed
 * @return          html img tag
 */
function PMA_getIcon($icon, $alternate = '', $container = false, $force_text = false)
{
    $include_icon = false;
    $include_text = false;
    $include_box  = false;
    $alternate    = htmlspecialchars($alternate);
    $button       = '';

    if ($GLOBALS['cfg']['PropertiesIconic']) {
         $include_icon = true;
    }

    if ($force_text
     || ! (true === $GLOBALS['cfg']['PropertiesIconic'])
     || ! $include_icon) {
        // $cfg['PropertiesIconic'] is false or both
        // OR we have no $include_icon
        $include_text = true;
    }

    if ($include_text && $include_icon && $container) {
        // we have icon, text and request for container
        $include_box = true;
    }

    // Always use a span (we rely on this in js/sql.js)
    $button .= '<span class="nowrap">';

    if ($include_icon) {
        $button .= '<img src="' . $GLOBALS['pmaThemeImage'] . $icon . '"'
            . ' title="' . $alternate . '" alt="' . $alternate . '"'
            . ' class="icon" width="16" height="16" />';
    }

    if ($include_icon && $include_text) {
        $button .= ' ';
    }

    if ($include_text) {
        $button .= $alternate;
    }

    $button .= '</span>';

    return $button;
}

/**
 * Displays the maximum size for an upload
 *
 * @uses    PMA_formatByteDown()
 * @uses    sprintf()
 * @param   integer  the size
 *
 * @return  string   the message
 *
 * @access  public
 */
function PMA_displayMaximumUploadSize($max_upload_size)
{
    // I have to reduce the second parameter (sensitiveness) from 6 to 4
    // to avoid weird results like 512 kKib
    list($max_size, $max_unit) = PMA_formatByteDown($max_upload_size, 4);
    return '(' . sprintf(__('Max: %s%s'), $max_size, $max_unit) . ')';
}

/**
 * Generates a hidden field which should indicate to the browser
 * the maximum size for upload
 *
 * @param   integer  the size
 *
 * @return  string   the INPUT field
 *
 * @access  public
 */
 function PMA_generateHiddenMaxFileSize($max_size)
 {
     return '<input type="hidden" name="MAX_FILE_SIZE" value="' .$max_size . '" />';
 }

/**
 * Add slashes before "'" and "\" characters so a value containing them can
 * be used in a sql comparison.
 *
 * @uses    str_replace()
 * @param   string   the string to slash
 * @param   boolean  whether the string will be used in a 'LIKE' clause
 *                   (it then requires two more escaped sequences) or not
 * @param   boolean  whether to treat cr/lfs as escape-worthy entities
 *                   (converts \n to \\n, \r to \\r)
 *
 * @param   boolean  whether this function is used as part of the
 *                   "Create PHP code" dialog
 *
 * @return  string   the slashed string
 *
 * @access  public
 */
function PMA_sqlAddslashes($a_string = '', $is_like = false, $crlf = false, $php_code = false)
{
    if ($is_like) {
        $a_string = str_replace('\\', '\\\\\\\\', $a_string);
    } else {
        $a_string = str_replace('\\', '\\\\', $a_string);
    }

    if ($crlf) {
        $a_string = str_replace("\n", '\n', $a_string);
        $a_string = str_replace("\r", '\r', $a_string);
        $a_string = str_replace("\t", '\t', $a_string);
    }

    if ($php_code) {
        $a_string = str_replace('\'', '\\\'', $a_string);
    } else {
        $a_string = str_replace('\'', '\'\'', $a_string);
    }

    return $a_string;
} // end of the 'PMA_sqlAddslashes()' function


/**
 * Add slashes before "_" and "%" characters for using them in MySQL
 * database, table and field names.
 * Note: This function does not escape backslashes!
 *
 * @uses    str_replace()
 * @param   string   the string to escape
 *
 * @return  string   the escaped string
 *
 * @access  public
 */
function PMA_escape_mysql_wildcards($name)
{
    $name = str_replace('_', '\\_', $name);
    $name = str_replace('%', '\\%', $name);

    return $name;
} // end of the 'PMA_escape_mysql_wildcards()' function

/**
 * removes slashes before "_" and "%" characters
 * Note: This function does not unescape backslashes!
 *
 * @uses    str_replace()
 * @param   string   $name  the string to escape
 * @return  string   the escaped string
 * @access  public
 */
function PMA_unescape_mysql_wildcards($name)
{
    $name = str_replace('\\_', '_', $name);
    $name = str_replace('\\%', '%', $name);

    return $name;
} // end of the 'PMA_unescape_mysql_wildcards()' function

/**
 * removes quotes (',",`) from a quoted string
 *
 * checks if the sting is quoted and removes this quotes
 *
 * @uses    str_replace()
 * @uses    substr()
 * @param   string  $quoted_string  string to remove quotes from
 * @param   string  $quote          type of quote to remove
 * @return  string  unqoted string
 */
function PMA_unQuote($quoted_string, $quote = null)
{
    $quotes = array();

    if (null === $quote) {
        $quotes[] = '`';
        $quotes[] = '"';
        $quotes[] = "'";
    } else {
        $quotes[] = $quote;
    }

    foreach ($quotes as $quote) {
        if (substr($quoted_string, 0, 1) === $quote
         && substr($quoted_string, -1, 1) === $quote) {
             $unquoted_string = substr($quoted_string, 1, -1);
             // replace escaped quotes
             $unquoted_string = str_replace($quote . $quote, $quote, $unquoted_string);
             return $unquoted_string;
         }
    }

    return $quoted_string;
}

/**
 * format sql strings
 *
 * @todo    move into PMA_Sql
 * @uses    PMA_SQP_isError()
 * @uses    PMA_SQP_formatHtml()
 * @uses    PMA_SQP_formatNone()
 * @uses    is_array()
 * @param   mixed    pre-parsed SQL structure
 *
 * @return  string   the formatted sql
 *
 * @global  array    the configuration array
 * @global  boolean  whether the current statement is a multiple one or not
 *
 * @access  public
 *
 */
function PMA_formatSql($parsed_sql, $unparsed_sql = '')
{
    global $cfg;

    // Check that we actually have a valid set of parsed data
    // well, not quite
    // first check for the SQL parser having hit an error
    if (PMA_SQP_isError()) {
        return htmlspecialchars($parsed_sql['raw']);
    }
    // then check for an array
    if (! is_array($parsed_sql)) {
        // We don't so just return the input directly
        // This is intended to be used for when the SQL Parser is turned off
        $formatted_sql = '<pre>' . "\n"
                        . (($cfg['SQP']['fmtType'] == 'none' && $unparsed_sql != '') ? $unparsed_sql : $parsed_sql) . "\n"
                        . '</pre>';
        return $formatted_sql;
    }

    $formatted_sql        = '';

    switch ($cfg['SQP']['fmtType']) {
        case 'none':
            if ($unparsed_sql != '') {
                $formatted_sql = '<span class="inner_sql"><pre>' . "\n" . PMA_SQP_formatNone(array('raw' => $unparsed_sql)) . "\n" . '</pre></span>';
            } else {
                $formatted_sql = PMA_SQP_formatNone($parsed_sql);
            }
            break;
        case 'html':
            $formatted_sql = PMA_SQP_formatHtml($parsed_sql, 'color');
            break;
        case 'text':
            $formatted_sql = PMA_SQP_formatHtml($parsed_sql, 'text');
            break;
        default:
            break;
    } // end switch

    return $formatted_sql;
} // end of the "PMA_formatSql()" function


/**
 * Displays a link to the official MySQL documentation
 *
 * @uses    $cfg['MySQLManualType']
 * @uses    $cfg['MySQLManualBase']
 * @uses    $cfg['ReplaceHelpImg']
 * @uses    $GLOBALS['pmaThemeImage']
 * @uses    PMA_MYSQL_INT_VERSION
 * @uses    strtolower()
 * @uses    str_replace()
 * @param string  chapter of "HTML, one page per chapter" documentation
 * @param string  contains name of page/anchor that is being linked
 * @param bool    whether to use big icon (like in left frame)
 * @param string  anchor to page part
 *
 * @return  string  the html link
 *
 * @access  public
 */
function PMA_showMySQLDocu($chapter, $link, $big_icon = false, $anchor = '', $just_open = false)
{
    global $cfg;

    if ($cfg['MySQLManualType'] == 'none' || empty($cfg['MySQLManualBase'])) {
        return '';
    }

    // Fixup for newly used names:
    $chapter = str_replace('_', '-', strtolower($chapter));
    $link = str_replace('_', '-', strtolower($link));

    switch ($cfg['MySQLManualType']) {
        case 'chapters':
            if (empty($chapter)) {
                $chapter = 'index';
            }
            if (empty($anchor)) {
                $anchor = $link;
            }
            $url = $cfg['MySQLManualBase'] . '/' . $chapter . '.html#' . $anchor;
            break;
        case 'big':
            if (empty($anchor)) {
                $anchor = $link;
            }
            $url = $cfg['MySQLManualBase'] . '#' . $anchor;
            break;
        case 'searchable':
            if (empty($link)) {
                $link = 'index';
            }
            $url = $cfg['MySQLManualBase'] . '/' . $link . '.html';
            if (!empty($anchor)) {
                $url .= '#' . $anchor;
            }
            break;
        case 'viewable':
        default:
            if (empty($link)) {
                $link = 'index';
            }
            $mysql = '5.0';
            $lang = 'en';
            if (defined('PMA_MYSQL_INT_VERSION')) {
                if (PMA_MYSQL_INT_VERSION >= 50500) {
                    $mysql = '5.5';
                    /* l10n: Language to use for MySQL 5.5 documentation, please use only languages which do exist in official documentation.  */
                    $lang = _pgettext('MySQL 5.5 documentation language', 'en');
                } else if (PMA_MYSQL_INT_VERSION >= 50100) {
                    $mysql = '5.1';
                    /* l10n: Language to use for MySQL 5.1 documentation, please use only languages which do exist in official documentation.  */
                    $lang = _pgettext('MySQL 5.1 documentation language', 'en');
                } elseif (PMA_MYSQL_INT_VERSION >= 50000) {
                    $mysql = '5.0';
                    /* l10n: Language to use for MySQL 5.0 documentation, please use only languages which do exist in official documentation. */
                    $lang = _pgettext('MySQL 5.0 documentation language', 'en');
                }
            }
            $url = $cfg['MySQLManualBase'] . '/' . $mysql . '/' . $lang . '/' . $link . '.html';
            if (!empty($anchor)) {
                $url .= '#' . $anchor;
            }
            break;
    }

    if ($just_open) {
        return '<a href="' . PMA_linkURL($url) . '" target="mysql_doc">';
    } elseif ($big_icon) {
        return '<a href="' . PMA_linkURL($url) . '" target="mysql_doc"><img class="icon" src="' . $GLOBALS['pmaThemeImage'] . 'b_sqlhelp.png" width="16" height="16" alt="' . __('Documentation') . '" title="' . __('Documentation') . '" /></a>';
    } elseif ($GLOBALS['cfg']['ReplaceHelpImg']) {
        return '<a href="' . PMA_linkURL($url) . '" target="mysql_doc"><img class="icon" src="' . $GLOBALS['pmaThemeImage'] . 'b_help.png" width="11" height="11" alt="' . __('Documentation') . '" title="' . __('Documentation') . '" /></a>';
    } else {
        return '[<a href="' . PMA_linkURL($url) . '" target="mysql_doc">' . __('Documentation') . '</a>]';
    }
} // end of the 'PMA_showMySQLDocu()' function


/**
 * Displays a link to the phpMyAdmin documentation
 *
 * @param string  anchor in documentation
 *
 * @return  string  the html link
 *
 * @access  public
 */
function PMA_showDocu($anchor) {
    if ($GLOBALS['cfg']['ReplaceHelpImg']) {
        return '<a href="Documentation.html#' . $anchor . '" target="documentation"><img class="icon" src="' . $GLOBALS['pmaThemeImage'] . 'b_help.png" width="11" height="11" alt="' . __('Documentation') . '" title="' . __('Documentation') . '" /></a>';
    } else {
        return '[<a href="Documentation.html#' . $anchor . '" target="documentation">' . __('Documentation') . '</a>]';
    }
} // end of the 'PMA_showDocu()' function

/**
 * Displays a link to the PHP documentation
 *
 * @param string  anchor in documentation
 *
 * @return  string  the html link
 *
 * @access  public
 */
function PMA_showPHPDocu($target) {
    $url = PMA_getPHPDocLink($target);

    if ($GLOBALS['cfg']['ReplaceHelpImg']) {
        return '<a href="' . $url . '" target="documentation"><img class="icon" src="' . $GLOBALS['pmaThemeImage'] . 'b_help.png" width="11" height="11" alt="' . __('Documentation') . '" title="' . __('Documentation') . '" /></a>';
    } else {
        return '[<a href="' . $url . '" target="documentation">' . __('Documentation') . '</a>]';
    }
} // end of the 'PMA_showPHPDocu()' function

/**
 * returns HTML for a footnote marker and add the messsage to the footnotes
 *
 * @uses    $GLOBALS['footnotes']
 * @param   string   the error message
 * @return  string html code for a footnote marker
 * @access  public
 */
function PMA_showHint($message, $bbcode = false, $type = 'notice')
{
    if ($message instanceof PMA_Message) {
        $key = $message->getHash();
        $type = $message->getLevel();
    } else {
        $key = md5($message);
    }

    if (! isset($GLOBALS['footnotes'][$key])) {
        if (empty($GLOBALS['footnotes']) || ! is_array($GLOBALS['footnotes'])) {
            $GLOBALS['footnotes'] = array();
        }
        $nr = count($GLOBALS['footnotes']) + 1;
        // this is the first instance of this message
        $instance = 1;
        $GLOBALS['footnotes'][$key] = array(
            'note'      => $message,
            'type'      => $type,
            'nr'        => $nr,
            'instance'  => $instance
        );
    } else {
        $nr = $GLOBALS['footnotes'][$key]['nr'];
        // another instance of this message (to ensure ids are unique)
        $instance = ++$GLOBALS['footnotes'][$key]['instance'];
    }

    if ($bbcode) {
        return '[sup]' . $nr . '[/sup]';
    }

    // footnotemarker used in js/tooltip.js
    return '<sup class="footnotemarker">' . $nr . '</sup>' .
    '<img class="footnotemarker" id="footnote_' . $nr . '_' . $instance . '" src="' .
    $GLOBALS['pmaThemeImage'] . 'b_help.png" alt="" />';
}

/**
 * Displays a MySQL error message in the right frame.
 *
 * @uses    footer.inc.php
 * @uses    header.inc.php
 * @uses    $GLOBALS['sql_query']
 * @uses    $GLOBALS['pmaThemeImage']
 * @uses    $GLOBALS['cfg']['PropertiesIconic']
 * @uses    $GLOBALS['cfg']['MaxCharactersInDisplayedSQL']
 * @uses    PMA_backquote()
 * @uses    PMA_DBI_getError()
 * @uses    PMA_formatSql()
 * @uses    PMA_generate_common_hidden_inputs()
 * @uses    PMA_generate_common_url()
 * @uses    PMA_showMySQLDocu()
 * @uses    PMA_sqlAddslashes()
 * @uses    PMA_SQP_isError()
 * @uses    PMA_SQP_parse()
 * @uses    PMA_SQP_getErrorString()
 * @uses    strtolower()
 * @uses    urlencode()
 * @uses    str_replace()
 * @uses    nl2br()
 * @uses    substr()
 * @uses    preg_replace()
 * @uses    preg_match()
 * @uses    explode()
 * @uses    implode()
 * @uses    is_array()
 * @uses    function_exists()
 * @uses    htmlspecialchars()
 * @uses    trim()
 * @uses    strstr()
 * @param   string   the error message
 * @param   string   the sql query that failed
 * @param   boolean  whether to show a "modify" link or not
 * @param   string   the "back" link url (full path is not required)
 * @param   boolean  EXIT the page?
 *
 * @global  string    the curent table
 * @global  string    the current db
 *
 * @access  public
 */
function PMA_mysqlDie($error_message = '', $the_query = '',
                        $is_modify_link = true, $back_url = '', $exit = true)
{
    global $table, $db;

    /**
     * start http output, display html headers
     */
    require_once './libraries/header.inc.php';

    $error_msg_output = '';

    if (!$error_message) {
        $error_message = PMA_DBI_getError();
    }
    if (!$the_query && !empty($GLOBALS['sql_query'])) {
        $the_query = $GLOBALS['sql_query'];
    }

    // --- Added to solve bug #641765
    if (!function_exists('PMA_SQP_isError') || PMA_SQP_isError()) {
        $formatted_sql = htmlspecialchars($the_query);
    } elseif (empty($the_query) || trim($the_query) == '') {
        $formatted_sql = '';
    } else {
        if (strlen($the_query) > $GLOBALS['cfg']['MaxCharactersInDisplayedSQL']) {
            $formatted_sql = htmlspecialchars(substr($the_query, 0, $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'])) . '[...]';
        } else {
            $formatted_sql = PMA_formatSql(PMA_SQP_parse($the_query), $the_query);
        }
    }
    // ---
    $error_msg_output .= "\n" . '<!-- PMA-SQL-ERROR -->' . "\n";
    $error_msg_output .= '    <div class="error"><h1>' . __('Error') . '</h1>' . "\n";
    // if the config password is wrong, or the MySQL server does not
    // respond, do not show the query that would reveal the
    // username/password
    if (!empty($the_query) && !strstr($the_query, 'connect')) {
        // --- Added to solve bug #641765
        if (function_exists('PMA_SQP_isError') && PMA_SQP_isError()) {
            $error_msg_output .= PMA_SQP_getErrorString() . "\n";
            $error_msg_output .= '<br />' . "\n";
        }
        // ---
        // modified to show the help on sql errors
        $error_msg_output .= '    <p><strong>' . __('SQL query') . ':</strong>' . "\n";
        if (strstr(strtolower($formatted_sql), 'select')) { // please show me help to the error on select
            $error_msg_output .= PMA_showMySQLDocu('SQL-Syntax', 'SELECT');
        }
        if ($is_modify_link) {
            $_url_params = array(
                'sql_query' => $the_query,
                'show_query' => 1,
            );
            if (strlen($table)) {
                $_url_params['db'] = $db;
                $_url_params['table'] = $table;
                $doedit_goto = '<a href="tbl_sql.php?' . PMA_generate_common_url($_url_params) . '">';
            } elseif (strlen($db)) {
                $_url_params['db'] = $db;
                $doedit_goto = '<a href="db_sql.php?' . PMA_generate_common_url($_url_params) . '">';
            } else {
                $doedit_goto = '<a href="server_sql.php?' . PMA_generate_common_url($_url_params) . '">';
            }

            $error_msg_output .= $doedit_goto
               . PMA_getIcon('b_edit.png', __('Edit'))
               . '</a>';
        } // end if
        $error_msg_output .= '    </p>' . "\n"
            .'    <p>' . "\n"
            .'        ' . $formatted_sql . "\n"
            .'    </p>' . "\n";
    } // end if

    if (!empty($error_message)) {
        $error_message = preg_replace("@((\015\012)|(\015)|(\012)){3,}@", "\n\n", $error_message);
    }
    // modified to show the help on error-returns
    // (now error-messages-server)
    $error_msg_output .= '<p>' . "\n"
            . '    <strong>' . __('MySQL said: ') . '</strong>'
            . PMA_showMySQLDocu('Error-messages-server', 'Error-messages-server')
            . "\n"
            . '</p>' . "\n";

    // The error message will be displayed within a CODE segment.
    // To preserve original formatting, but allow wordwrapping, we do a couple of replacements

    // Replace all non-single blanks with their HTML-counterpart
    $error_message = str_replace('  ', '&nbsp;&nbsp;', $error_message);
    // Replace TAB-characters with their HTML-counterpart
    $error_message = str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', $error_message);
    // Replace linebreaks
    $error_message = nl2br($error_message);

    $error_msg_output .= '<code>' . "\n"
        . $error_message . "\n"
        . '</code><br />' . "\n";
    $error_msg_output .= '</div>';

    $_SESSION['Import_message']['message'] = $error_msg_output;

    if ($exit) {
       /**
        * If in an Ajax request
        * - avoid displaying a Back link
        * - use PMA_ajaxResponse() to transmit the message and exit
        */
       if($GLOBALS['is_ajax_request'] == true) {
           PMA_ajaxResponse($error_msg_output, false);
       }
        if (! empty($back_url)) {
            if (strstr($back_url, '?')) {
                $back_url .= '&amp;no_history=true';
            } else {
                $back_url .= '?no_history=true';
            }

            $_SESSION['Import_message']['go_back_url'] = $back_url;

            $error_msg_output .= '<fieldset class="tblFooters">';
            $error_msg_output .= '[ <a href="' . $back_url . '">' . __('Back') . '</a> ]';
            $error_msg_output .= '</fieldset>' . "\n\n";
       }

       echo $error_msg_output;
       /**
        * display footer and exit
        */
       require './libraries/footer.inc.php';
    } else {
        echo $error_msg_output;
    }
} // end of the 'PMA_mysqlDie()' function

/**
 * returns array with tables of given db with extended information and grouped
 *
 * @uses    $cfg['LeftFrameTableSeparator']
 * @uses    $cfg['LeftFrameTableLevel']
 * @uses    $cfg['ShowTooltipAliasTB']
 * @uses    $cfg['NaturalOrder']
 * @uses    PMA_backquote()
 * @uses    count()
 * @uses    array_merge
 * @uses    uksort()
 * @uses    strstr()
 * @uses    explode()
 * @param   string   $db     name of db
 * @param   string   $tables name of tables
 * @param   integer  $limit_offset   list offset
 * @param   int|bool $limit_count    max tables to return
 * @return  array    (recursive) grouped table list
 */
function PMA_getTableList($db, $tables = null, $limit_offset = 0, $limit_count = false)
{
    $sep = $GLOBALS['cfg']['LeftFrameTableSeparator'];

    if (null === $tables) {
        $tables = PMA_DBI_get_tables_full($db, false, false, null, $limit_offset, $limit_count);
        if ($GLOBALS['cfg']['NaturalOrder']) {
            uksort($tables, 'strnatcasecmp');
        }
    }

    if (count($tables) < 1) {
        return $tables;
    }

    $default = array(
        'Name'      => '',
        'Rows'      => 0,
        'Comment'   => '',
        'disp_name' => '',
    );

    $table_groups = array();

    // for blobstreaming - list of blobstreaming tables

    // load PMA configuration
    $PMA_Config = $GLOBALS['PMA_Config'];

    foreach ($tables as $table_name => $table) {
        // if BS tables exist
        if (PMA_BS_IsHiddenTable($table_name)) {
            continue;
        }

        // check for correct row count
        if (null === $table['Rows']) {
            // Do not check exact row count here,
            // if row count is invalid possibly the table is defect
            // and this would break left frame;
            // but we can check row count if this is a view or the
            // information_schema database
            // since PMA_Table::countRecords() returns a limited row count
            // in this case.

            // set this because PMA_Table::countRecords() can use it
            $tbl_is_view = $table['TABLE_TYPE'] == 'VIEW';

            if ($tbl_is_view || strtolower($db) == 'information_schema'
                    || (PMA_DRIZZLE && strtolower($db) == 'data_dictionary')) {
                $table['Rows'] = PMA_Table::countRecords($db, $table['Name'], false, true);
            }
        }

        // in $group we save the reference to the place in $table_groups
        // where to store the table info
        if ($GLOBALS['cfg']['LeftFrameDBTree']
            && $sep && strstr($table_name, $sep))
        {
            $parts = explode($sep, $table_name);

            $group =& $table_groups;
            $i = 0;
            $group_name_full = '';
            $parts_cnt = count($parts) - 1;
            while ($i < $parts_cnt
              && $i < $GLOBALS['cfg']['LeftFrameTableLevel']) {
                $group_name = $parts[$i] . $sep;
                $group_name_full .= $group_name;

                if (! isset($group[$group_name])) {
                    $group[$group_name] = array();
                    $group[$group_name]['is' . $sep . 'group'] = true;
                    $group[$group_name]['tab' . $sep . 'count'] = 1;
                    $group[$group_name]['tab' . $sep . 'group'] = $group_name_full;
                } elseif (! isset($group[$group_name]['is' . $sep . 'group'])) {
                    $table = $group[$group_name];
                    $group[$group_name] = array();
                    $group[$group_name][$group_name] = $table;
                    unset($table);
                    $group[$group_name]['is' . $sep . 'group'] = true;
                    $group[$group_name]['tab' . $sep . 'count'] = 1;
                    $group[$group_name]['tab' . $sep . 'group'] = $group_name_full;
                } else {
                    $group[$group_name]['tab' . $sep . 'count']++;
                }
                $group =& $group[$group_name];
                $i++;
            }
        } else {
            if (! isset($table_groups[$table_name])) {
                $table_groups[$table_name] = array();
            }
            $group =& $table_groups;
        }


        if ($GLOBALS['cfg']['ShowTooltipAliasTB']
          && $GLOBALS['cfg']['ShowTooltipAliasTB'] !== 'nested') {
            // switch tooltip and name
            $table['Comment'] = $table['Name'];
            $table['disp_name'] = $table['Comment'];
        } else {
            $table['disp_name'] = $table['Name'];
        }

        $group[$table_name] = array_merge($default, $table);
    }

    return $table_groups;
}

/* ----------------------- Set of misc functions ----------------------- */


/**
 * Adds backquotes on both sides of a database, table or field name.
 * and escapes backquotes inside the name with another backquote
 *
 * example:
 * <code>
 * echo PMA_backquote('owner`s db'); // `owner``s db`
 *
 * </code>
 *
 * @uses    PMA_backquote()
 * @uses    is_array()
 * @uses    strlen()
 * @uses    str_replace()
 * @param   mixed    $a_name    the database, table or field name to "backquote"
 *                              or array of it
 * @param   boolean  $do_it     a flag to bypass this function (used by dump
 *                              functions)
 * @return  mixed    the "backquoted" database, table or field name if the
 *                   current MySQL release is >= 3.23.6, the original one
 *                   else
 * @access  public
 */
function PMA_backquote($a_name, $do_it = true)
{
    if (is_array($a_name)) {
        foreach ($a_name as &$data) {
            $data = PMA_backquote($data, $do_it);
        }
        return $a_name;
    }

    if (! $do_it) {
        global $PMA_SQPdata_forbidden_word;
        global $PMA_SQPdata_forbidden_word_cnt;

        if(! PMA_STR_binarySearchInArr(strtoupper($a_name), $PMA_SQPdata_forbidden_word, $PMA_SQPdata_forbidden_word_cnt)) {
            return $a_name;
        }
    }

    // '0' is also empty for php :-(
    if (strlen($a_name) && $a_name !== '*') {
        return '`' . str_replace('`', '``', $a_name) . '`';
    } else {
        return $a_name;
    }
} // end of the 'PMA_backquote()' function


/**
 * Defines the <CR><LF> value depending on the user OS.
 *
 * @uses    PMA_USR_OS
 * @return  string   the <CR><LF> value to use
 *
 * @access  public
 */
function PMA_whichCrlf()
{
    $the_crlf = "\n";

    // The 'PMA_USR_OS' constant is defined in "./libraries/Config.class.php"
    // Win case
    if (PMA_USR_OS == 'Win') {
        $the_crlf = "\r\n";
    }
    // Others
    else {
        $the_crlf = "\n";
    }

    return $the_crlf;
} // end of the 'PMA_whichCrlf()' function

/**
 * Reloads navigation if needed.
 *
 * @param   $jsonly prints out pure JavaScript
 * @uses    $GLOBALS['reload']
 * @uses    $GLOBALS['db']
 * @uses    PMA_generate_common_url()
 * @global  array  configuration
 *
 * @access  public
 */
function PMA_reloadNavigation($jsonly=false)
{
    global $cfg;

    // Reloads the navigation frame via JavaScript if required
    if (isset($GLOBALS['reload']) && $GLOBALS['reload']) {
        // one of the reasons for a reload is when a table is dropped
        // in this case, get rid of the table limit offset, otherwise
        // we have a problem when dropping a table on the last page
        // and the offset becomes greater than the total number of tables
        unset($_SESSION['tmp_user_values']['table_limit_offset']);
        echo "\n";
        $reload_url = './navigation.php?' . PMA_generate_common_url($GLOBALS['db'], '', '&');
	if (!$jsonly)
	  echo '<script type="text/javascript">' . PHP_EOL;
	?>
//<![CDATA[
if (typeof(window.parent) != 'undefined'
    && typeof(window.parent.frame_navigation) != 'undefined'
    && window.parent.goTo) {
    window.parent.goTo('<?php echo $reload_url; ?>');
}
//]]>
<?php
if (!$jsonly)
  echo '</script>' . PHP_EOL;

        unset($GLOBALS['reload']);
    }
}

/**
 * displays the message and the query
 * usually the message is the result of the query executed
 *
 * @param   string  $message    the message to display
 * @param   string  $sql_query  the query to display
 * @param   string  $type       the type (level) of the message
 * @param   boolean $is_view    is this a message after a VIEW operation?
 * @global  array   the configuration array
 * @uses    $cfg
 * @access  public
 */
function PMA_showMessage($message, $sql_query = null, $type = 'notice', $is_view = false)
{
    /*
     * PMA_ajaxResponse uses this function to collect the string of HTML generated
     * for showing the message.  Use output buffering to collect it and return it
     * in a string.  In some special cases on sql.php, buffering has to be disabled
     * and hence we check with $GLOBALS['buffer_message']
     */
    if( $GLOBALS['is_ajax_request'] == true && ! isset($GLOBALS['buffer_message']) ) {
        ob_start();
    }
    global $cfg;

    if (null === $sql_query) {
        if (! empty($GLOBALS['display_query'])) {
            $sql_query = $GLOBALS['display_query'];
        } elseif ($cfg['SQP']['fmtType'] == 'none' && ! empty($GLOBALS['unparsed_sql'])) {
            $sql_query = $GLOBALS['unparsed_sql'];
        } elseif (! empty($GLOBALS['sql_query'])) {
            $sql_query = $GLOBALS['sql_query'];
        } else {
            $sql_query = '';
        }
    }

    if (isset($GLOBALS['using_bookmark_message'])) {
        $GLOBALS['using_bookmark_message']->display();
        unset($GLOBALS['using_bookmark_message']);
    }

    // Corrects the tooltip text via JS if required
    // @todo this is REALLY the wrong place to do this - very unexpected here
    if (! $is_view && strlen($GLOBALS['table']) && $cfg['ShowTooltip']) {
        $tooltip = PMA_Table::sGetToolTip($GLOBALS['db'], $GLOBALS['table']);
        $uni_tbl = PMA_jsFormat($GLOBALS['db'] . '.' . $GLOBALS['table'], false);
        echo "\n";
        echo '<script type="text/javascript">' . "\n";
        echo '//<![CDATA[' . "\n";
        echo "if (window.parent.updateTableTitle) window.parent.updateTableTitle('" . $uni_tbl . "', '" . PMA_jsFormat($tooltip, false) . "');" . "\n";
        echo '//]]>' . "\n";
        echo '</script>' . "\n";
    } // end if ... elseif

    // Checks if the table needs to be repaired after a TRUNCATE query.
    // @todo what about $GLOBALS['display_query']???
    // @todo this is REALLY the wrong place to do this - very unexpected here
    if (strlen($GLOBALS['table'])
     && $GLOBALS['sql_query'] == 'TRUNCATE TABLE ' . PMA_backquote($GLOBALS['table'])) {
        if (PMA_Table::sGetStatusInfo($GLOBALS['db'], $GLOBALS['table'], 'Index_length') > 1024) {
            PMA_DBI_try_query('REPAIR TABLE ' . PMA_backquote($GLOBALS['table']));
        }
    }
    unset($tbl_status);

    // In an Ajax request, $GLOBALS['cell_align_left'] may not be defined. Hence,
    // check for it's presence before using it
    echo '<div id="result_query" align="' . ( isset($GLOBALS['cell_align_left']) ? $GLOBALS['cell_align_left'] : '' ) . '">' . "\n";

    if ($message instanceof PMA_Message) {
        if (isset($GLOBALS['special_message'])) {
            $message->addMessage($GLOBALS['special_message']);
            unset($GLOBALS['special_message']);
        }
        $message->display();
        $type = $message->getLevel();
    } else {
        echo '<div class="' . $type . '">';
        echo PMA_sanitize($message);
        if (isset($GLOBALS['special_message'])) {
            echo PMA_sanitize($GLOBALS['special_message']);
            unset($GLOBALS['special_message']);
        }
        echo '</div>';
    }

    if ($cfg['ShowSQL'] == true && ! empty($sql_query)) {
        // Html format the query to be displayed
        // If we want to show some sql code it is easiest to create it here
        /* SQL-Parser-Analyzer */

        if (! empty($GLOBALS['show_as_php'])) {
            $new_line = '\\n"<br />' . "\n"
                . '&nbsp;&nbsp;&nbsp;&nbsp;. "';
            $query_base = htmlspecialchars(addslashes($sql_query));
            $query_base = preg_replace('/((\015\012)|(\015)|(\012))/', $new_line, $query_base);
        } else {
            $query_base = $sql_query;
        }

        $query_too_big = false;

        if (strlen($query_base) > $cfg['MaxCharactersInDisplayedSQL']) {
            // when the query is large (for example an INSERT of binary
            // data), the parser chokes; so avoid parsing the query
            $query_too_big = true;
            $shortened_query_base = nl2br(htmlspecialchars(substr($sql_query, 0, $cfg['MaxCharactersInDisplayedSQL']) . '[...]'));
        } elseif (! empty($GLOBALS['parsed_sql'])
         && $query_base == $GLOBALS['parsed_sql']['raw']) {
            // (here, use "! empty" because when deleting a bookmark,
            // $GLOBALS['parsed_sql'] is set but empty
            $parsed_sql = $GLOBALS['parsed_sql'];
        } else {
            // Parse SQL if needed
            $parsed_sql = PMA_SQP_parse($query_base);
            if (PMA_SQP_isError()) {
                unset($parsed_sql);
            }
        }

        // Analyze it
        if (isset($parsed_sql)) {
            $analyzed_display_query = PMA_SQP_analyze($parsed_sql);
            // Here we append the LIMIT added for navigation, to
            // enable its display. Adding it higher in the code
            // to $sql_query would create a problem when
            // using the Refresh or Edit links.

            // Only append it on SELECTs.

            /**
             * @todo what would be the best to do when someone hits Refresh:
             * use the current LIMITs ?
             */

            if (isset($analyzed_display_query[0]['queryflags']['select_from'])
             && isset($GLOBALS['sql_limit_to_append'])) {
                $query_base = $analyzed_display_query[0]['section_before_limit']
                    . "\n" . $GLOBALS['sql_limit_to_append']
                    . $analyzed_display_query[0]['section_after_limit'];
                // Need to reparse query
                $parsed_sql = PMA_SQP_parse($query_base);
            }
        }

        if (! empty($GLOBALS['show_as_php'])) {
            $query_base = '$sql  = "' . $query_base;
        } elseif (! empty($GLOBALS['validatequery'])) {
            try {
                $query_base = PMA_validateSQL($query_base);
            } catch (Exception $e) {
                PMA_Message::error(__('Failed to connect to SQL validator!'))->display();
            }
        } elseif (isset($parsed_sql)) {
            $query_base = PMA_formatSql($parsed_sql, $query_base);
        }

        // Prepares links that may be displayed to edit/explain the query
        // (don't go to default pages, we must go to the page
        // where the query box is available)

        // Basic url query part
        $url_params = array();
        if (! isset($GLOBALS['db'])) {
            $GLOBALS['db'] = '';
        }
        if (strlen($GLOBALS['db'])) {
            $url_params['db'] = $GLOBALS['db'];
            if (strlen($GLOBALS['table'])) {
                $url_params['table'] = $GLOBALS['table'];
                $edit_link = 'tbl_sql.php';
            } else {
                $edit_link = 'db_sql.php';
            }
        } else {
            $edit_link = 'server_sql.php';
        }

        // Want to have the query explained (Mike Beck 2002-05-22)
        // but only explain a SELECT (that has not been explained)
        /* SQL-Parser-Analyzer */
        $explain_link = '';
        if (! empty($cfg['SQLQuery']['Explain']) && ! $query_too_big) {
            $explain_params = $url_params;
            // Detect if we are validating as well
            // To preserve the validate uRL data
            if (! empty($GLOBALS['validatequery'])) {
                $explain_params['validatequery'] = 1;
            }
            $is_select = false;
            if (preg_match('@^SELECT[[:space:]]+@i', $sql_query)) {
                $explain_params['sql_query'] = 'EXPLAIN ' . $sql_query;
                $_message = __('Explain SQL');
                $is_select = true;
            } elseif (preg_match('@^EXPLAIN[[:space:]]+SELECT[[:space:]]+@i', $sql_query)) {
                $explain_params['sql_query'] = substr($sql_query, 8);
                $_message = __('Skip Explain SQL');
            }
            if (isset($explain_params['sql_query'])) {
                $explain_link = 'import.php' . PMA_generate_common_url($explain_params);
                $explain_link = ' [' . PMA_linkOrButton($explain_link, $_message) . ']';
            }
        } //show explain

        $url_params['sql_query']  = $sql_query;
        $url_params['show_query'] = 1;

        // even if the query is big and was truncated, offer the chance
        // to edit it (unless it's enormous, see PMA_linkOrButton() )
        if (! empty($cfg['SQLQuery']['Edit'])) {
            if ($cfg['EditInWindow'] == true) {
                $onclick = 'window.parent.focus_querywindow(\'' . PMA_jsFormat($sql_query, false) . '\'); return false;';
            } else {
                $onclick = '';
            }

            $edit_link .= PMA_generate_common_url($url_params) . '#querybox';
            $edit_link = ' [' . PMA_linkOrButton($edit_link, __('Edit'), array('onclick' => $onclick)) . ']';
        } else {
            $edit_link = '';
        }

        $url_qpart = PMA_generate_common_url($url_params);

        // Also we would like to get the SQL formed in some nice
        // php-code (Mike Beck 2002-05-22)
        if (! empty($cfg['SQLQuery']['ShowAsPHP']) && ! $query_too_big) {
            $php_params = $url_params;

            if (! empty($GLOBALS['show_as_php'])) {
                $_message = __('Without PHP Code');
            } else {
                $php_params['show_as_php'] = 1;
                $_message = __('Create PHP Code');
            }

            $php_link = 'import.php' . PMA_generate_common_url($php_params);
            $php_link = ' [' . PMA_linkOrButton($php_link, $_message) . ']';

            if (isset($GLOBALS['show_as_php'])) {
                $runquery_link = 'import.php' . PMA_generate_common_url($url_params);
                $php_link .= ' [' . PMA_linkOrButton($runquery_link, __('Submit Query')) . ']';
            }
        } else {
            $php_link = '';
        } //show as php

        // Refresh query
        if (! empty($cfg['SQLQuery']['Refresh'])
         && preg_match('@^(SELECT|SHOW)[[:space:]]+@i', $sql_query)) {
            $refresh_link = 'import.php' . PMA_generate_common_url($url_params);
            $refresh_link = ' [' . PMA_linkOrButton($refresh_link, __('Refresh')) . ']';
        } else {
            $refresh_link = '';
        } //show as php

        if (! empty($cfg['SQLValidator']['use'])
         && ! empty($cfg['SQLQuery']['Validate'])) {
            $validate_params = $url_params;
            if (!empty($GLOBALS['validatequery'])) {
                $validate_message = __('Skip Validate SQL') ;
            } else {
                $validate_params['validatequery'] = 1;
                $validate_message = __('Validate SQL') ;
            }

            $validate_link = 'import.php' . PMA_generate_common_url($validate_params);
            $validate_link = ' [' . PMA_linkOrButton($validate_link, $validate_message) . ']';
        } else {
            $validate_link = '';
        } //validator

        if (!empty($GLOBALS['validatequery'])) {
            echo '<div class="sqlvalidate">';
        } else {
            echo '<code class="sql">';
        }
        if ($query_too_big) {
            echo $shortened_query_base;
        } else {
            echo $query_base;
        }

        //Clean up the end of the PHP
        if (! empty($GLOBALS['show_as_php'])) {
            echo '";';
        }
        if (!empty($GLOBALS['validatequery'])) {
            echo '</div>';
        } else {
            echo '</code>';
        }

        echo '<div class="tools">';
        // avoid displaying a Profiling checkbox that could
        // be checked, which would reexecute an INSERT, for example
        if (! empty($refresh_link)) {
            PMA_profilingCheckbox($sql_query);
        }
        // if needed, generate an invisible form that contains controls for the
        // Inline link; this way, the behavior of the Inline link does not
        // depend on the profiling support or on the refresh link
        if (empty($refresh_link) || ! PMA_profilingSupported()) {
            echo '<form action="sql.php" method="post">';
            echo PMA_generate_common_hidden_inputs($GLOBALS['db'], $GLOBALS['table']);
            echo '<input type="hidden" name="sql_query" value="' . htmlspecialchars($sql_query) . '" />';
            echo '</form>';
        }

        // in the tools div, only display the Inline link when not in ajax
        // mode because 1) it currently does not work and 2) we would
        // have two similar mechanisms on the page for the same goal
        if ($is_select || $GLOBALS['is_ajax_request'] === false) {
        // see in js/functions.js the jQuery code attached to id inline_edit
        // document.write conflicts with jQuery, hence used $().append()
            echo "<script type=\"text/javascript\">\n" .
                "//<![CDATA[\n" .
                "$('.tools form').last().after('[<a href=\"#\" title=\"" .
                PMA_escapeJsString(__('Inline edit of this query')) .
                "\" class=\"inline_edit_sql\">" .
                PMA_escapeJsString(__('Inline')) .
                "</a>]');\n" .
                "//]]>\n" .
                "</script>";
        }
        echo $edit_link . $explain_link . $php_link . $refresh_link . $validate_link;
        echo '</div>';
    }
    echo '</div>';
    if ($GLOBALS['is_ajax_request'] === false) {
        echo '<br class="clearfloat" />';
    }

    // If we are in an Ajax request, we have most probably been called in
    // PMA_ajaxResponse().  Hence, collect the buffer contents and return it
    // to PMA_ajaxResponse(), which will encode it for JSON.
    if( $GLOBALS['is_ajax_request'] == true && ! isset($GLOBALS['buffer_message']) ) {
        $buffer_contents =  ob_get_contents();
        ob_end_clean();
        return $buffer_contents;
    }
} // end of the 'PMA_showMessage()' function

/**
 * Verifies if current MySQL server supports profiling
 *
 * @uses    $_SESSION['profiling_supported'] for caching
 * @uses    $GLOBALS['server']
 * @uses    PMA_DBI_fetch_value()
 * @uses    PMA_MYSQL_INT_VERSION
 * @uses    defined()
 * @access  public
 * @return  boolean whether profiling is supported
 *
 */
function PMA_profilingSupported()
{
    if (! PMA_cacheExists('profiling_supported', true)) {
        // 5.0.37 has profiling but for example, 5.1.20 does not
        // (avoid a trip to the server for MySQL before 5.0.37)
        // and do not set a constant as we might be switching servers
        if (defined('PMA_MYSQL_INT_VERSION')
         && PMA_MYSQL_INT_VERSION >= 50037
         && PMA_DBI_fetch_value("SHOW VARIABLES LIKE 'profiling'")) {
            PMA_cacheSet('profiling_supported', true, true);
        } else {
            PMA_cacheSet('profiling_supported', false, true);
        }
    }

    return PMA_cacheGet('profiling_supported', true);
}

/**
 * Displays a form with the Profiling checkbox
 *
 * @param   string  $sql_query
 * @access  public
 *
 */
function PMA_profilingCheckbox($sql_query)
{
    if (PMA_profilingSupported()) {
        echo '<form action="sql.php" method="post">' . "\n";
        echo PMA_generate_common_hidden_inputs($GLOBALS['db'], $GLOBALS['table']);
        echo '<input type="hidden" name="sql_query" value="' . htmlspecialchars($sql_query) . '" />' . "\n";
        echo '<input type="hidden" name="profiling_form" value="1" />' . "\n";
        PMA_display_html_checkbox('profiling', __('Profiling'), isset($_SESSION['profiling']), true);
        echo '<noscript><input type="submit" value="' . __('Go') . '" /></noscript>' . "\n";
        echo '</form>' . "\n";
    }
}

/**
 * Displays the results of SHOW PROFILE
 *
 * @param    array   the results
 * @param    boolean show chart
 * @access  public
 *
 */
function PMA_profilingResults($profiling_results, $show_chart = false)
{
    echo '<fieldset><legend>' . __('Profiling') . '</legend>' . "\n";
    echo '<div style="float: left;">';
    echo '<table>' . "\n";
    echo ' <tr>' .  "\n";
    echo '  <th>' . __('Status') . '</th>' . "\n";
    echo '  <th>' . __('Time') . '</th>' . "\n";
    echo ' </tr>' .  "\n";

    foreach($profiling_results as $one_result) {
        echo ' <tr>' .  "\n";
        echo '<td>' . $one_result['Status'] . '</td>' .  "\n";
        echo '<td>' . $one_result['Duration'] . '</td>' .  "\n";
    }

    echo '</table>' . "\n";
    echo '</div>';

    if ($show_chart) {
        require_once './libraries/chart.lib.php';
        echo '<div style="float: left;">';
        PMA_chart_profiling($profiling_results);
        echo '</div>';
    }

    echo '</fieldset>' . "\n";
}

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
 * @version  1.2 - 18 July 2002
 */
function PMA_formatByteDown($value, $limes = 6, $comma = 0)
{
    /* l10n: shortcuts for Byte, Kilo, Mega, Giga, Tera, Peta, Exa+ */
    $byteUnits = array(__('B'), __('KiB'), __('MiB'), __('GiB'), __('TiB'), __('PiB'), __('EiB'));

    $dh           = PMA_pow(10, $comma);
    $li           = PMA_pow(10, $limes);
    $return_value = $value;
    $unit         = $byteUnits[0];

    for ($d = 6, $ex = 15; $d >= 1; $d--, $ex-=3) {
        if (isset($byteUnits[$d]) && $value >= $li * PMA_pow(10, $ex)) {
            // use 1024.0 to avoid integer overflow on 64-bit machines
            $value = round($value / (PMA_pow(1024, $d) / $dh)) /$dh;
            $unit = $byteUnits[$d];
            break 1;
        } // end if
    } // end for

    if ($unit != $byteUnits[0]) {
        // if the unit is not bytes (as represented in current language)
        // reformat with max length of 5
        // 4th parameter=true means do not reformat if value < 1
        $return_value = PMA_formatNumber($value, 5, $comma, true);
    } else {
        // do not reformat, just handle the locale
        $return_value = PMA_formatNumber($value, 0);
    }

    return array(trim($return_value), $unit);
} // end of the 'PMA_formatByteDown' function

/**
 * Changes thousands and decimal separators to locale specific values.
 */
function PMA_localizeNumber($value)
{
    return str_replace(
        array(',', '.'),
        array(
            /* l10n: Thousands separator */
            __(','),
            /* l10n: Decimal separator */
            __('.'),
            ),
        $value);
}

/**
 * Formats $value to the given length and appends SI prefixes
 * $comma is not substracted from the length
 * with a $length of 0 no truncation occurs, number is only formated
 * to the current locale
 *
 * examples:
 * <code>
 * echo PMA_formatNumber(123456789, 6);     // 123,457 k
 * echo PMA_formatNumber(-123456789, 4, 2); //    -123.46 M
 * echo PMA_formatNumber(-0.003, 6);        //      -3 m
 * echo PMA_formatNumber(0.003, 3, 3);      //       0.003
 * echo PMA_formatNumber(0.00003, 3, 2);    //       0.03 m
 * echo PMA_formatNumber(0, 6);             //       0
 *
 * </code>
 * @param   double   $value     the value to format
 * @param   integer  $length    the max length
 * @param   integer  $comma     the number of decimals to retain
 * @param   boolean  $only_down do not reformat numbers below 1
 *
 * @return  string   the formatted value and its unit
 *
 * @access  public
 *
 * @version 1.1.0 - 2005-10-27
 */
function PMA_formatNumber($value, $length = 3, $comma = 0, $only_down = false)
{
    //number_format is not multibyte safe, str_replace is safe
    if ($length === 0) {
        return PMA_localizeNumber(number_format($value, $comma));
    }

    // this units needs no translation, ISO
    $units = array(
        -8 => 'y',
        -7 => 'z',
        -6 => 'a',
        -5 => 'f',
        -4 => 'p',
        -3 => 'n',
        -2 => '&micro;',
        -1 => 'm',
        0 => ' ',
        1 => 'k',
        2 => 'M',
        3 => 'G',
        4 => 'T',
        5 => 'P',
        6 => 'E',
        7 => 'Z',
        8 => 'Y'
    );

    // we need at least 3 digits to be displayed
    if (3 > $length + $comma) {
        $length = 3 - $comma;
    }

    // check for negative value to retain sign
    if ($value < 0) {
        $sign = '-';
        $value = abs($value);
    } else {
        $sign = '';
    }

    $dh = PMA_pow(10, $comma);
    $li = PMA_pow(10, $length);
    $unit = $units[0];

    if ($value >= 1) {
        for ($d = 8; $d >= 0; $d--) {
            if (isset($units[$d]) && $value >= $li * PMA_pow(1000, $d-1)) {
                $value = round($value / (PMA_pow(1000, $d) / $dh)) /$dh;
                $unit = $units[$d];
                break 1;
            } // end if
        } // end for
    } elseif (!$only_down && (float) $value !== 0.0) {
        for ($d = -8; $d <= 8; $d++) {
            // force using pow() because of the negative exponent
            if (isset($units[$d]) && $value <= $li * PMA_pow(1000, $d-1, 'pow')) {
                $value = round($value / (PMA_pow(1000, $d, 'pow') / $dh)) /$dh;
                $unit = $units[$d];
                break 1;
            } // end if
        } // end for
    } // end if ($value >= 1) elseif (!$only_down && (float) $value !== 0.0)

    //number_format is not multibyte safe, str_replace is safe
    $value = PMA_localizeNumber(number_format($value, $comma));

    return $sign . $value . ' ' . $unit;
} // end of the 'PMA_formatNumber' function

/**
 * Returns the number of bytes when a formatted size is given
 *
 * @param   string   $size     the size expression (for example 8MB)
 * @uses    PMA_pow()
 * @return  integer  The numerical part of the expression (for example 8)
 */
function PMA_extractValueFromFormattedSize($formatted_size)
{
    $return_value = -1;

    if (preg_match('/^[0-9]+GB$/', $formatted_size)) {
        $return_value = substr($formatted_size, 0, -2) * PMA_pow(1024, 3);
    } elseif (preg_match('/^[0-9]+MB$/', $formatted_size)) {
        $return_value = substr($formatted_size, 0, -2) * PMA_pow(1024, 2);
    } elseif (preg_match('/^[0-9]+K$/', $formatted_size)) {
        $return_value = substr($formatted_size, 0, -1) * PMA_pow(1024, 1);
    }
    return $return_value;
}// end of the 'PMA_extractValueFromFormattedSize' function

/**
 * Writes localised date
 *
 * @param   string   the current timestamp
 *
 * @return  string   the formatted date
 *
 * @access  public
 */
function PMA_localisedDate($timestamp = -1, $format = '')
{
    $month = array(
/* l10n: Short month name */
        __('Jan'),
/* l10n: Short month name */
        __('Feb'),
/* l10n: Short month name */
        __('Mar'),
/* l10n: Short month name */
        __('Apr'),
/* l10n: Short month name */
        _pgettext('Short month name', 'May'),
/* l10n: Short month name */
        __('Jun'),
/* l10n: Short month name */
        __('Jul'),
/* l10n: Short month name */
        __('Aug'),
/* l10n: Short month name */
        __('Sep'),
/* l10n: Short month name */
        __('Oct'),
/* l10n: Short month name */
        __('Nov'),
/* l10n: Short month name */
        __('Dec'));
    $day_of_week = array(
/* l10n: Short week day name */
        __('Sun'),
/* l10n: Short week day name */
        __('Mon'),
/* l10n: Short week day name */
        __('Tue'),
/* l10n: Short week day name */
        __('Wed'),
/* l10n: Short week day name */
        __('Thu'),
/* l10n: Short week day name */
        __('Fri'),
/* l10n: Short week day name */
        __('Sat'));

    if ($format == '') {
        /* l10n: See http://www.php.net/manual/en/function.strftime.php to define the format string */
        $format = __('%B %d, %Y at %I:%M %p');
    }

    if ($timestamp == -1) {
        $timestamp = time();
    }

    $date = preg_replace('@%[aA]@', $day_of_week[(int)strftime('%w', $timestamp)], $format);
    $date = preg_replace('@%[bB]@', $month[(int)strftime('%m', $timestamp)-1], $date);

    return strftime($date, $timestamp);
} // end of the 'PMA_localisedDate()' function


/**
 * returns a tab for tabbed navigation.
 * If the variables $link and $args ar left empty, an inactive tab is created
 *
 * @uses    $GLOBALS['PMA_PHP_SELF']
 * @uses    $GLOBALS['active_page']
 * @uses    $GLOBALS['url_query']
 * @uses    $cfg['MainPageIconic']
 * @uses    $GLOBALS['pmaThemeImage']
 * @uses    PMA_generate_common_url()
 * @uses    E_USER_NOTICE
 * @uses    htmlentities()
 * @uses    urlencode()
 * @uses    sprintf()
 * @uses    trigger_error()
 * @uses    array_merge()
 * @uses    basename()
 * @param   array   $tab    array with all options
 * @param   array   $url_params
 * @return  string  html code for one tab, a link if valid otherwise a span
 * @access  public
 */
function PMA_generate_html_tab($tab, $url_params = array())
{
    // default values
    $defaults = array(
        'text'      => '',
        'class'     => '',
        'active'    => null,
        'link'      => '',
        'sep'       => '?',
        'attr'      => '',
        'args'      => '',
        'warning'   => '',
        'fragment'  => '',
        'id'        => '',
    );

    $tab = array_merge($defaults, $tab);

    // determine additionnal style-class
    if (empty($tab['class'])) {
        if (! empty($tab['active'])
         || PMA_isValid($GLOBALS['active_page'], 'identical', $tab['link'])) {
            $tab['class'] = 'active';
        } elseif (is_null($tab['active']) && empty($GLOBALS['active_page'])
          && basename($GLOBALS['PMA_PHP_SELF']) == $tab['link']
          && empty($tab['warning'])) {
            $tab['class'] = 'active';
        }
    }

    if (!empty($tab['warning'])) {
        $tab['class'] .= ' error';
        $tab['attr'] .= ' title="' . htmlspecialchars($tab['warning']) . '"';
    }

	// If there are any tab specific URL parameters, merge those with the general URL parameters
	if(! empty($tab['url_params']) && is_array($tab['url_params'])) {
		$url_params = array_merge($url_params, $tab['url_params']);
	}

    // build the link
    if (!empty($tab['link'])) {
        $tab['link'] = htmlentities($tab['link']);
        $tab['link'] = $tab['link'] . PMA_generate_common_url($url_params);
        if (! empty($tab['args'])) {
            foreach ($tab['args'] as $param => $value) {
                $tab['link'] .= PMA_get_arg_separator('html') . urlencode($param) . '='
                    . urlencode($value);
            }
        }
    }

    if (! empty($tab['fragment'])) {
        $tab['link'] .= $tab['fragment'];
    }

    // display icon, even if iconic is disabled but the link-text is missing
    if (($GLOBALS['cfg']['MainPageIconic'] || empty($tab['text']))
        && isset($tab['icon'])) {
        // avoid generating an alt tag, because it only illustrates
        // the text that follows and if browser does not display
        // images, the text is duplicated
        $image = '<img class="icon" src="' . htmlentities($GLOBALS['pmaThemeImage'])
            .'%1$s" width="16" height="16" alt="" />%2$s';
        $tab['text'] = sprintf($image, htmlentities($tab['icon']), $tab['text']);
    }
    // check to not display an empty link-text
    elseif (empty($tab['text'])) {
        $tab['text'] = '?';
        trigger_error('empty linktext in function ' . __FUNCTION__ . '()',
            E_USER_NOTICE);
    }

    //Set the id for the tab, if set in the params
    $id_string = ( empty($tab['id']) ? '' : ' id="'.$tab['id'].'" ' );
    $out = '<li' . ($tab['class'] == 'active' ? ' class="active"' : '') . '>';

    if (!empty($tab['link'])) {
        $out .= '<a class="tab' . htmlentities($tab['class']) . '"'
            .$id_string
            .' href="' . $tab['link'] . '" ' . $tab['attr'] . '>'
            . $tab['text'] . '</a>';
    } else {
        $out .= '<span class="tab' . htmlentities($tab['class']) . '"'.$id_string.'>'
            . $tab['text'] . '</span>';
    }

    $out .= '</li>';
    return $out;
} // end of the 'PMA_generate_html_tab()' function

/**
 * returns html-code for a tab navigation
 *
 * @uses    PMA_generate_html_tab()
 * @uses    htmlentities()
 * @param   array   $tabs   one element per tab
 * @param   string  $url_params
 * @return  string  html-code for tab-navigation
 */
function PMA_generate_html_tabs($tabs, $url_params)
{
    $tag_id = 'topmenu';
    $tab_navigation =
         '<div id="' . htmlentities($tag_id) . 'container">' . "\n"
        .'<ul id="' . htmlentities($tag_id) . '">' . "\n";

    foreach ($tabs as $tab) {
        $tab_navigation .= PMA_generate_html_tab($tab, $url_params);
    }

    $tab_navigation .=
         '</ul>' . "\n"
        .'<div class="clearfloat"></div>'
        .'</div>' . "\n";

    return $tab_navigation;
}


/**
 * Displays a link, or a button if the link's URL is too large, to
 * accommodate some browsers' limitations
 *
 * @param  string  the URL
 * @param  string  the link message
 * @param  mixed   $tag_params  string: js confirmation
 *                              array: additional tag params (f.e. style="")
 * @param  boolean $new_form    we set this to false when we are already in
 *                              a  form, to avoid generating nested forms
 *
 * @return string  the results to be echoed or saved in an array
 */
function PMA_linkOrButton($url, $message, $tag_params = array(),
    $new_form = true, $strip_img = false, $target = '')
{
    $url_length = strlen($url);
    // with this we should be able to catch case of image upload
    // into a (MEDIUM) BLOB; not worth generating even a form for these
    if ($url_length > $GLOBALS['cfg']['LinkLengthLimit'] * 100) {
        return '';
    }

    if (! is_array($tag_params)) {
        $tmp = $tag_params;
        $tag_params = array();
        if (!empty($tmp)) {
            $tag_params['onclick'] = 'return confirmLink(this, \'' . PMA_escapeJsString($tmp) . '\')';
        }
        unset($tmp);
    }
    if (! empty($target)) {
        $tag_params['target'] = htmlentities($target);
    }

    $tag_params_strings = array();
    foreach ($tag_params as $par_name => $par_value) {
        // htmlspecialchars() only on non javascript
        $par_value = substr($par_name, 0, 2) == 'on'
            ? $par_value
            : htmlspecialchars($par_value);
        $tag_params_strings[] = $par_name . '="' . $par_value . '"';
    }

    if ($url_length <= $GLOBALS['cfg']['LinkLengthLimit']) {
        // no whitespace within an <a> else Safari will make it part of the link
        $ret = "\n" . '<a href="' . $url . '" '
            . implode(' ', $tag_params_strings) . '>'
            . $message . '</a>' . "\n";
    } else {
        // no spaces (linebreaks) at all
        // or after the hidden fields
        // IE will display them all

        // add class=link to submit button
        if (empty($tag_params['class'])) {
            $tag_params['class'] = 'link';
        }

        // decode encoded url separators
        $separator   = PMA_get_arg_separator();
        // on most places separator is still hard coded ...
        if ($separator !== '&') {
            // ... so always replace & with $separator
            $url         = str_replace(htmlentities('&'), $separator, $url);
            $url         = str_replace('&', $separator, $url);
        }
        $url         = str_replace(htmlentities($separator), $separator, $url);
        // end decode

        $url_parts   = parse_url($url);
        $query_parts = explode($separator, $url_parts['query']);
        if ($new_form) {
            $ret = '<form action="' . $url_parts['path'] . '" class="link"'
                 . ' method="post"' . $target . ' style="display: inline;">';
            $subname_open   = '';
            $subname_close  = '';
            $submit_name    = '';
        } else {
            $query_parts[] = 'redirect=' . $url_parts['path'];
            if (empty($GLOBALS['subform_counter'])) {
                $GLOBALS['subform_counter'] = 0;
            }
            $GLOBALS['subform_counter']++;
            $ret            = '';
            $subname_open   = 'subform[' . $GLOBALS['subform_counter'] . '][';
            $subname_close  = ']';
            $submit_name    = ' name="usesubform[' . $GLOBALS['subform_counter'] . ']"';
        }
        foreach ($query_parts as $query_pair) {
            list($eachvar, $eachval) = explode('=', $query_pair);
            $ret .= '<input type="hidden" name="' . $subname_open . $eachvar
                . $subname_close . '" value="'
                . htmlspecialchars(urldecode($eachval)) . '" />';
        } // end while

        if (stristr($message, '<img')) {
            if ($strip_img) {
                $message = trim(strip_tags($message));
                $ret .= '<input type="submit"' . $submit_name . ' '
                    . implode(' ', $tag_params_strings)
                    . ' value="' . htmlspecialchars($message) . '" />';
            } else {
                $displayed_message = htmlspecialchars(
                        preg_replace('/^.*\salt="([^"]*)".*$/si', '\1',
                            $message));
                $ret .= '<input type="image"' . $submit_name . ' '
                    . implode(' ', $tag_params_strings)
                    . ' src="' . preg_replace(
                        '/^.*\ssrc="([^"]*)".*$/si', '\1', $message) . '"'
                        . ' value="' . $displayed_message . '" title="' . $displayed_message . '" />';
                // Here we cannot obey PropertiesIconic completely as a
                // generated link would have a length over LinkLengthLimit
                // but we can at least show the message.
                // If PropertiesIconic is false or 'both'
                if ($GLOBALS['cfg']['PropertiesIconic'] !== true) {
                    $ret .= ' <span class="clickprevimage">' . $displayed_message . '</span>';
                }
            }
        } else {
            $message = trim(strip_tags($message));
            $ret .= '<input type="submit"' . $submit_name . ' '
                . implode(' ', $tag_params_strings)
                . ' value="' . htmlspecialchars($message) . '" />';
        }
        if ($new_form) {
            $ret .= '</form>';
        }
    } // end if... else...

        return $ret;
} // end of the 'PMA_linkOrButton()' function


/**
 * Returns a given timespan value in a readable format.
 *
 * @uses    sprintf()
 * @uses    floor()
 * @param  int     the timespan
 *
 * @return string  the formatted value
 */
function PMA_timespanFormat($seconds)
{
    $return_string = '';
    $days = floor($seconds / 86400);
    if ($days > 0) {
        $seconds -= $days * 86400;
    }
    $hours = floor($seconds / 3600);
    if ($days > 0 || $hours > 0) {
        $seconds -= $hours * 3600;
    }
    $minutes = floor($seconds / 60);
    if ($days > 0 || $hours > 0 || $minutes > 0) {
        $seconds -= $minutes * 60;
    }
    return sprintf(__('%s days, %s hours, %s minutes and %s seconds'), (string)$days, (string)$hours, (string)$minutes, (string)$seconds);
}

/**
 * Takes a string and outputs each character on a line for itself. Used
 * mainly for horizontalflipped display mode.
 * Takes care of special html-characters.
 * Fulfills todo-item
 * http://sf.net/tracker/?func=detail&aid=544361&group_id=23067&atid=377411
 *
 * @todo    add a multibyte safe function PMA_STR_split()
 * @uses    strlen
 * @param   string   The string
 * @param   string   The Separator (defaults to "<br />\n")
 *
 * @access  public
 * @return  string      The flipped string
 */
function PMA_flipstring($string, $Separator = "<br />\n")
{
    $format_string = '';
    $charbuff = false;

    for ($i = 0, $str_len = strlen($string); $i < $str_len; $i++) {
        $char = $string{$i};
        $append = false;

        if ($char == '&') {
            $format_string .= $charbuff;
            $charbuff = $char;
        } elseif ($char == ';' && !empty($charbuff)) {
            $format_string .= $charbuff . $char;
            $charbuff = false;
            $append = true;
        } elseif (! empty($charbuff)) {
            $charbuff .= $char;
        } else {
            $format_string .= $char;
            $append = true;
        }

        // do not add separator after the last character
        if ($append && ($i != $str_len - 1)) {
            $format_string .= $Separator;
        }
    }

    return $format_string;
}


/**
 * Function added to avoid path disclosures.
 * Called by each script that needs parameters, it displays
 * an error message and, by default, stops the execution.
 *
 * Not sure we could use a strMissingParameter message here,
 * would have to check if the error message file is always available
 *
 * @todo    localize error message
 * @todo    use PMA_fatalError() if $die === true?
 * @uses    PMA_getenv()
 * @uses    header_meta_style.inc.php
 * @uses    $GLOBALS['PMA_PHP_SELF']
 * basename
 * @param   array   The names of the parameters needed by the calling
 *                  script.
 * @param   boolean Stop the execution?
 *                  (Set this manually to false in the calling script
 *                   until you know all needed parameters to check).
 * @param   boolean Whether to include this list in checking for special params.
 * @global  string  path to current script
 * @global  boolean flag whether any special variable was required
 *
 * @access  public
 */
function PMA_checkParameters($params, $die = true, $request = true)
{
    global $checked_special;

    if (! isset($checked_special)) {
        $checked_special = false;
    }

    $reported_script_name = basename($GLOBALS['PMA_PHP_SELF']);
    $found_error = false;
    $error_message = '';

    foreach ($params as $param) {
        if ($request && $param != 'db' && $param != 'table') {
            $checked_special = true;
        }

        if (! isset($GLOBALS[$param])) {
            $error_message .= $reported_script_name
                . ': Missing parameter: ' . $param
                . PMA_showDocu('faqmissingparameters')
                . '<br />';
            $found_error = true;
        }
    }
    if ($found_error) {
        /**
         * display html meta tags
         */
        require_once './libraries/header_meta_style.inc.php';
        echo '</head><body><p>' . $error_message . '</p></body></html>';
        if ($die) {
            exit();
        }
    }
} // end function

/**
 * Function to generate unique condition for specified row.
 *
 * @uses    $GLOBALS['analyzed_sql'][0]
 * @uses    PMA_DBI_field_flags()
 * @uses    PMA_backquote()
 * @uses    PMA_sqlAddslashes()
 * @uses    PMA_printable_bit_value()
 * @uses    stristr()
 * @uses    bin2hex()
 * @uses    preg_replace()
 * @param   resource    $handle         current query result
 * @param   integer     $fields_cnt     number of fields
 * @param   array       $fields_meta    meta information about fields
 * @param   array       $row            current row
 * @param   boolean     $force_unique   generate condition only on pk or unique
 *
 * @access  public
 * @return  string     the calculated condition and whether condition is unique
 */
function PMA_getUniqueCondition($handle, $fields_cnt, $fields_meta, $row, $force_unique=false)
{
    $primary_key          = '';
    $unique_key           = '';
    $nonprimary_condition = '';
    $preferred_condition = '';

    for ($i = 0; $i < $fields_cnt; ++$i) {
        $condition   = '';
        $field_flags = PMA_DBI_field_flags($handle, $i);
        $meta        = $fields_meta[$i];

        // do not use a column alias in a condition
        if (! isset($meta->orgname) || ! strlen($meta->orgname)) {
            $meta->orgname = $meta->name;

            if (isset($GLOBALS['analyzed_sql'][0]['select_expr'])
              && is_array($GLOBALS['analyzed_sql'][0]['select_expr'])) {
                foreach ($GLOBALS['analyzed_sql'][0]['select_expr']
                  as $select_expr) {
                    // need (string) === (string)
                    // '' !== 0 but '' == 0
                    if ((string) $select_expr['alias'] === (string) $meta->name) {
                        $meta->orgname = $select_expr['column'];
                        break;
                    } // end if
                } // end foreach
            }
        }

        // Do not use a table alias in a condition.
        // Test case is:
        // select * from galerie x WHERE
        //(select count(*) from galerie y where y.datum=x.datum)>1
        //
        // But orgtable is present only with mysqli extension so the
        // fix is only for mysqli.
        // Also, do not use the original table name if we are dealing with
        // a view because this view might be updatable.
        // (The isView() verification should not be costly in most cases
        // because there is some caching in the function).
        if (isset($meta->orgtable) && $meta->table != $meta->orgtable && ! PMA_Table::isView($GLOBALS['db'], $meta->table)) {
            $meta->table = $meta->orgtable;
        }

        // to fix the bug where float fields (primary or not)
        // can't be matched because of the imprecision of
        // floating comparison, use CONCAT
        // (also, the syntax "CONCAT(field) IS NULL"
        // that we need on the next "if" will work)
        if ($meta->type == 'real') {
            $condition = ' CONCAT(' . PMA_backquote($meta->table) . '.'
                . PMA_backquote($meta->orgname) . ') ';
        } else {
            $condition = ' ' . PMA_backquote($meta->table) . '.'
                . PMA_backquote($meta->orgname) . ' ';
        } // end if... else...

        if (! isset($row[$i]) || is_null($row[$i])) {
            $condition .= 'IS NULL AND';
        } else {
            // timestamp is numeric on some MySQL 4.1
            // for real we use CONCAT above and it should compare to string
            if ($meta->numeric && $meta->type != 'timestamp' && $meta->type != 'real') {
                $condition .= '= ' . $row[$i] . ' AND';
            } elseif (($meta->type == 'blob' || $meta->type == 'string')
                // hexify only if this is a true not empty BLOB or a BINARY
                 && stristr($field_flags, 'BINARY')
                 && !empty($row[$i])) {
                    // do not waste memory building a too big condition
                    if (strlen($row[$i]) < 1000) {
                        // use a CAST if possible, to avoid problems
                        // if the field contains wildcard characters % or _
                        $condition .= '= CAST(0x' . bin2hex($row[$i])
                            . ' AS BINARY) AND';
                    } else {
                        // this blob won't be part of the final condition
                        $condition = '';
                    }
            } elseif ($meta->type == 'bit') {
                $condition .= "= b'" . PMA_printable_bit_value($row[$i], $meta->length) . "' AND";
            } else {
                $condition .= '= \''
                    . PMA_sqlAddslashes($row[$i], false, true) . '\' AND';
            }
        }
        if ($meta->primary_key > 0) {
            $primary_key .= $condition;
        } elseif ($meta->unique_key > 0) {
            $unique_key  .= $condition;
        }
        $nonprimary_condition .= $condition;
    } // end for

    // Correction University of Virginia 19991216:
    // prefer primary or unique keys for condition,
    // but use conjunction of all values if no primary key
    $clause_is_unique = true;
    if ($primary_key) {
        $preferred_condition = $primary_key;
    } elseif ($unique_key) {
        $preferred_condition = $unique_key;
    } elseif (! $force_unique) {
        $preferred_condition = $nonprimary_condition;
        $clause_is_unique = false;
    }

    $where_clause = trim(preg_replace('|\s?AND$|', '', $preferred_condition));
    return(array($where_clause, $clause_is_unique));
} // end function

/**
 * Generate a button or image tag
 *
 * @uses    PMA_USR_BROWSER_AGENT
 * @uses    $GLOBALS['pmaThemeImage']
 * @uses    $GLOBALS['cfg']['PropertiesIconic']
 * @param   string      name of button element
 * @param   string      class of button element
 * @param   string      name of image element
 * @param   string      text to display
 * @param   string      image to display
 *
 * @access  public
 */
function PMA_buttonOrImage($button_name, $button_class, $image_name, $text,
    $image, $value = '')
{
    if ($value == '') {
        $value = $text;
    }
    if (false === $GLOBALS['cfg']['PropertiesIconic']) {
        echo ' <input type="submit" name="' . $button_name . '"'
                .' value="' . htmlspecialchars($value) . '"'
                .' title="' . htmlspecialchars($text) . '" />' . "\n";
        return;
    }

    /* Opera has trouble with <input type="image"> */
    /* IE has trouble with <button> */
    if (PMA_USR_BROWSER_AGENT != 'IE') {
        echo '<button class="' . $button_class . '" type="submit"'
            .' name="' . $button_name . '" value="' . htmlspecialchars($value) . '"'
            .' title="' . htmlspecialchars($text) . '">' . "\n"
            . PMA_getIcon($image, $text)
            .'</button>' . "\n";
    } else {
        echo '<input type="image" name="' . $image_name . '" value="'
            . htmlspecialchars($value) . '" title="' . htmlspecialchars($text) . '" src="' . $GLOBALS['pmaThemeImage']
            . $image . '" />'
            . ($GLOBALS['cfg']['PropertiesIconic'] === 'both' ? '&nbsp;' . htmlspecialchars($text) : '') . "\n";
    }
} // end function

/**
 * Generate a pagination selector for browsing resultsets
 *
 * @uses    range()
 * @param   string      Number of rows in the pagination set
 * @param   string      current page number
 * @param   string      number of total pages
 * @param   string      If the number of pages is lower than this
 *                      variable, no pages will be omitted in
 *                      pagination
 * @param   string      How many rows at the beginning should always
 *                      be shown?
 * @param   string      How many rows at the end should always
 *                      be shown?
 * @param   string      Percentage of calculation page offsets to
 *                      hop to a next page
 * @param   string      Near the current page, how many pages should
 *                      be considered "nearby" and displayed as
 *                      well?
 * @param   string      The prompt to display (sometimes empty)
 *
 * @access  public
 */
function PMA_pageselector($rows, $pageNow = 1, $nbTotalPage = 1,
    $showAll = 200, $sliceStart = 5, $sliceEnd = 5, $percent = 20,
    $range = 10, $prompt = '')
{
    $increment = floor($nbTotalPage / $percent);
    $pageNowMinusRange = ($pageNow - $range);
    $pageNowPlusRange = ($pageNow + $range);

    $gotopage = $prompt . ' <select id="pageselector" ';
    if ($GLOBALS['cfg']['AjaxEnable']) {
        $gotopage .= ' class="ajax"';
    }
    $gotopage .= ' name="pos" >' . "\n";
    if ($nbTotalPage < $showAll) {
        $pages = range(1, $nbTotalPage);
    } else {
        $pages = array();

        // Always show first X pages
        for ($i = 1; $i <= $sliceStart; $i++) {
            $pages[] = $i;
        }

        // Always show last X pages
        for ($i = $nbTotalPage - $sliceEnd; $i <= $nbTotalPage; $i++) {
            $pages[] = $i;
        }

        // Based on the number of results we add the specified
        // $percent percentage to each page number,
        // so that we have a representing page number every now and then to
        // immediately jump to specific pages.
        // As soon as we get near our currently chosen page ($pageNow -
        // $range), every page number will be shown.
        $i = $sliceStart;
        $x = $nbTotalPage - $sliceEnd;
        $met_boundary = false;
        while ($i <= $x) {
            if ($i >= $pageNowMinusRange && $i <= $pageNowPlusRange) {
                // If our pageselector comes near the current page, we use 1
                // counter increments
                $i++;
                $met_boundary = true;
            } else {
                // We add the percentage increment to our current page to
                // hop to the next one in range
                $i += $increment;

                // Make sure that we do not cross our boundaries.
                if ($i > $pageNowMinusRange && ! $met_boundary) {
                    $i = $pageNowMinusRange;
                }
            }

            if ($i > 0 && $i <= $x) {
                $pages[] = $i;
            }
        }

        // Since because of ellipsing of the current page some numbers may be double,
        // we unify our array:
        sort($pages);
        $pages = array_unique($pages);
    }

    foreach ($pages as $i) {
        if ($i == $pageNow) {
            $selected = 'selected="selected" style="font-weight: bold"';
        } else {
            $selected = '';
        }
        $gotopage .= '                <option ' . $selected . ' value="' . (($i - 1) * $rows) . '">' . $i . '</option>' . "\n";
    }

    $gotopage .= ' </select><noscript><input type="submit" value="' . __('Go') . '" /></noscript>';

    return $gotopage;
} // end function


/**
 * Generate navigation for a list
 *
 * @todo    use $pos from $_url_params
 * @uses    range()
 * @param   integer     number of elements in the list
 * @param   integer     current position in the list
 * @param   array       url parameters
 * @param   string      script name for form target
 * @param   string      target frame
 * @param   integer     maximum number of elements to display from the list
 *
 * @access  public
 */
function PMA_listNavigator($count, $pos, $_url_params, $script, $frame, $max_count) {

    if ($max_count < $count) {
        echo 'frame_navigation' == $frame ? '<div id="navidbpageselector">' . "\n" : '';
        echo __('Page number:');
        echo 'frame_navigation' == $frame ? '<br />' : ' ';

        // Move to the beginning or to the previous page
        if ($pos > 0) {
            // patch #474210 - part 1
            if ($GLOBALS['cfg']['NavigationBarIconic']) {
                $caption1 = '&lt;&lt;';
                $caption2 = ' &lt; ';
                $title1   = ' title="' . __('Begin') . '"';
                $title2   = ' title="' . __('Previous') . '"';
            } else {
                $caption1 = __('Begin') . ' &lt;&lt;';
                $caption2 = __('Previous') . ' &lt;';
                $title1   = '';
                $title2   = '';
            } // end if... else...
            $_url_params['pos'] = 0;
            echo '<a' . $title1 . ' href="' . $script
                . PMA_generate_common_url($_url_params) . '" target="' . $frame . '">'
                . $caption1 . '</a>';
            $_url_params['pos'] = $pos - $max_count;
            echo '<a' . $title2 . ' href="' . $script
                . PMA_generate_common_url($_url_params) . '" target="' . $frame . '">'
                . $caption2 . '</a>';
        }

        echo "\n", '<form action="./', basename($script), '" method="post" target="', $frame, '">', "\n";
        echo PMA_generate_common_hidden_inputs($_url_params);
        echo PMA_pageselector(
                $max_count,
                floor(($pos + 1) / $max_count) + 1,
                ceil($count / $max_count));
        echo '</form>';

        if ($pos + $max_count < $count) {
            if ($GLOBALS['cfg']['NavigationBarIconic']) {
                $caption3 = ' &gt; ';
                $caption4 = '&gt;&gt;';
                $title3   = ' title="' . __('Next') . '"';
                $title4   = ' title="' . __('End') . '"';
            } else {
                $caption3 = '&gt; ' . __('Next');
                $caption4 = '&gt;&gt; ' . __('End');
                $title3   = '';
                $title4   = '';
            } // end if... else...
            $_url_params['pos'] = $pos + $max_count;
            echo '<a' . $title3 . ' href="' . $script
                . PMA_generate_common_url($_url_params) . '" target="' . $frame . '">'
                . $caption3 . '</a>';
            $_url_params['pos'] = floor($count / $max_count) * $max_count;
            if ($_url_params['pos'] == $count) {
                $_url_params['pos'] = $count - $max_count;
            }
            echo '<a' . $title4 . ' href="' . $script
                . PMA_generate_common_url($_url_params) . '" target="' . $frame . '">'
                . $caption4 . '</a>';
        }
        echo "\n";
        if ('frame_navigation' == $frame) {
            echo '</div>' . "\n";
        }
    }
}

/**
 * replaces %u in given path with current user name
 *
 * example:
 * <code>
 * $user_dir = PMA_userDir('/var/pma_tmp/%u/'); // '/var/pma_tmp/root/'
 *
 * </code>
 * @uses    $cfg['Server']['user']
 * @uses    substr()
 * @uses    str_replace()
 * @param   string  $dir with wildcard for user
 * @return  string  per user directory
 */
function PMA_userDir($dir)
{
    // add trailing slash
    if (substr($dir, -1) != '/') {
        $dir .= '/';
    }

    return str_replace('%u', $GLOBALS['cfg']['Server']['user'], $dir);
}

/**
 * returns html code for db link to default db page
 *
 * @uses    $cfg['DefaultTabDatabase']
 * @uses    $GLOBALS['db']
 * @uses    PMA_generate_common_url()
 * @uses    PMA_unescape_mysql_wildcards()
 * @uses    strlen()
 * @uses    sprintf()
 * @uses    htmlspecialchars()
 * @param   string  $database
 * @return  string  html link to default db page
 */
function PMA_getDbLink($database = null)
{
    if (! strlen($database)) {
        if (! strlen($GLOBALS['db'])) {
            return '';
        }
        $database = $GLOBALS['db'];
    } else {
        $database = PMA_unescape_mysql_wildcards($database);
    }

    return '<a href="' . $GLOBALS['cfg']['DefaultTabDatabase'] . '?' . PMA_generate_common_url($database) . '"'
        .' title="' . sprintf(__('Jump to database &quot;%s&quot;.'), htmlspecialchars($database)) . '">'
        .htmlspecialchars($database) . '</a>';
}

/**
 * Displays a lightbulb hint explaining a known external bug
 * that affects a functionality
 *
 * @uses    PMA_MYSQL_INT_VERSION
 * @uses    PMA_showHint()
 * @uses    sprintf()
 * @param   string  $functionality localized message explaining the func.
 * @param   string  $component  'mysql' (eventually, 'php')
 * @param   string  $minimum_version of this component
 * @param   string  $bugref  bug reference for this component
 */
function PMA_externalBug($functionality, $component, $minimum_version, $bugref)
{
    if ($component == 'mysql' && PMA_MYSQL_INT_VERSION < $minimum_version) {
        echo PMA_showHint(sprintf(__('The %s functionality is affected by a known bug, see %s'), $functionality, PMA_linkURL('http://bugs.mysql.com/') . $bugref));
    }
}

/**
 * Generates and echoes an HTML checkbox
 *
 * @param   string  $html_field_name the checkbox HTML field
 * @param   string  $label
 * @param   boolean $checked is it initially checked?
 * @param   boolean $onclick should it submit the form on click?
 */
function PMA_display_html_checkbox($html_field_name, $label, $checked, $onclick) {

    echo '<input type="checkbox" name="' . $html_field_name . '" id="' . $html_field_name . '"' . ($checked ? ' checked="checked"' : '') . ($onclick ? ' onclick="this.form.submit();"' : '') . ' /><label for="' . $html_field_name . '">' . $label . '</label>';
}

/**
 * Generates and echoes a set of radio HTML fields
 *
 * @uses    htmlspecialchars()
 * @param   string  $html_field_name the radio HTML field
 * @param   array   $choices the choices values and labels
 * @param   string  $checked_choice the choice to check by default
 * @param   boolean $line_break whether to add an HTML line break after a choice
 * @param   boolean $escape_label whether to use htmlspecialchars() on label
 * @param   string  $class enclose each choice with a div of this class
 */
function PMA_display_html_radio($html_field_name, $choices, $checked_choice = '', $line_break = true, $escape_label = true, $class='') {
    foreach ($choices as $choice_value => $choice_label) {
        if (! empty($class)) {
            echo '<div class="' . $class . '">';
        }
        $html_field_id = $html_field_name . '_' . $choice_value;
        echo '<input type="radio" name="' . $html_field_name . '" id="' . $html_field_id . '" value="' . htmlspecialchars($choice_value) . '"';
        if ($choice_value == $checked_choice) {
            echo ' checked="checked"';
        }
        echo ' />' . "\n";
        echo '<label for="' . $html_field_id . '">' . ($escape_label ? htmlspecialchars($choice_label)  : $choice_label) . '</label>';
        if ($line_break) {
            echo '<br />';
        }
        if (! empty($class)) {
            echo '</div>';
        }
        echo "\n";
    }
}

/**
 * Generates and returns an HTML dropdown
 *
 * @uses    htmlspecialchars()
 * @param   string  $select_name
 * @param   array   $choices the choices values
 * @param   string  $active_choice the choice to select by default
 * @param   string  $id the id of the select element; can be different in case
 *                  the dropdown is present more than once on the page
 * @todo    support titles
 */
function PMA_generate_html_dropdown($select_name, $choices, $active_choice, $id)
{
    $result = '<select name="' . htmlspecialchars($select_name) . '" id="' . htmlspecialchars($id) . '">';
    foreach ($choices as $one_choice_value => $one_choice_label) {
        $result .= '<option value="' . htmlspecialchars($one_choice_value) . '"';
        if ($one_choice_value == $active_choice) {
            $result .= ' selected="selected"';
        }
        $result .= '>' . htmlspecialchars($one_choice_label) . '</option>';
    }
    $result .= '</select>';
    return $result;
}

/**
 * Generates a slider effect (jQjuery)
 * Takes care of generating the initial <div> and the link
 * controlling the slider; you have to generate the </div> yourself
 * after the sliding section.
 *
 * @uses    $GLOBALS['cfg']['InitialSlidersState']
 * @param   string  $id the id of the <div> on which to apply the effect
 * @param   string  $message the message to show as a link
 */
function PMA_generate_slider_effect($id, $message)
{
    if ($GLOBALS['cfg']['InitialSlidersState'] == 'disabled') {
        echo '<div id="' . $id . '">';
        return;
    }
    /**
     * Bad hack on the next line. document.write() conflicts with jQuery, hence,
     * opening the <div> with PHP itself instead of JavaScript.
     *
     * @todo find a better solution that uses $.append(), the recommended method
     * maybe by using an additional param, the id of the div to append to
     */
    ?>
<div id="<?php echo $id; ?>" <?php echo $GLOBALS['cfg']['InitialSlidersState'] == 'closed' ? ' style="display: none; overflow:auto;"' : ''; ?> class="pma_auto_slider" title="<?php echo htmlspecialchars($message); ?>">
    <?php
}

/**
 * Clears cache content which needs to be refreshed on user change.
 */
function PMA_clearUserCache() {
    PMA_cacheUnset('is_superuser', true);
}

/**
 * Verifies if something is cached in the session
 *
 * @param string $var
 * @param scalar $server
 * @return boolean
 */
function PMA_cacheExists($var, $server = 0)
{
    if (true === $server) {
        $server = $GLOBALS['server'];
    }
    return isset($_SESSION['cache']['server_' . $server][$var]);
}

/**
 * Gets cached information from the session
 *
 * @param string $var
 * @param scalar $server
 * @return mixed
 */
function PMA_cacheGet($var, $server = 0)
{
    if (true === $server) {
        $server = $GLOBALS['server'];
    }
    if (isset($_SESSION['cache']['server_' . $server][$var])) {
        return $_SESSION['cache']['server_' . $server][$var];
    } else {
        return null;
    }
}

/**
 * Caches information in the session
 *
 * @param string $var
 * @param mixed $val
 * @param integer $server
 * @return mixed
 */
function PMA_cacheSet($var, $val = null, $server = 0)
{
    if (true === $server) {
        $server = $GLOBALS['server'];
    }
    $_SESSION['cache']['server_' . $server][$var] = $val;
}

/**
 * Removes cached information from the session
 *
 * @param string $var
 * @param scalar $server
 */
function PMA_cacheUnset($var, $server = 0)
{
    if (true === $server) {
        $server = $GLOBALS['server'];
    }
    unset($_SESSION['cache']['server_' . $server][$var]);
}

/**
 * Converts a bit value to printable format;
 * in MySQL a BIT field can be from 1 to 64 bits so we need this
 * function because in PHP, decbin() supports only 32 bits
 *
 * @uses    ceil()
 * @uses    decbin()
 * @uses    ord()
 * @uses    substr()
 * @uses    sprintf()
 * @param   numeric $value coming from a BIT field
 * @param   integer $length
 * @return  string  the printable value
 */
function PMA_printable_bit_value($value, $length) {
    $printable = '';
    for ($i = 0, $len_ceiled = ceil($length / 8); $i < $len_ceiled; $i++) {
        $printable .= sprintf('%08d', decbin(ord(substr($value, $i, 1))));
    }
    $printable = substr($printable, -$length);
    return $printable;
}

/**
 * Verifies whether the value contains a non-printable character
 *
 * @uses    preg_match()
 * @param   string $value
 * @return  boolean
 */
function PMA_contains_nonprintable_ascii($value) {
    return preg_match('@[^[:print:]]@', $value);
}

/**
 * Converts a BIT type default value
 * for example, b'010' becomes 010
 *
 * @uses    strtr()
 * @param   string $bit_default_value
 * @return  string the converted value
 */
function PMA_convert_bit_default_value($bit_default_value) {
    return strtr($bit_default_value, array("b" => "", "'" => ""));
}

/**
 * Extracts the various parts from a field type spec
 *
 * @uses    strpos()
 * @uses    chop()
 * @uses    substr()
 * @param   string $fieldspec
 * @return  array associative array containing type, spec_in_brackets
 *          and possibly enum_set_values (another array)
 */
function PMA_extractFieldSpec($fieldspec) {
    $first_bracket_pos = strpos($fieldspec, '(');
    if ($first_bracket_pos) {
        $spec_in_brackets = chop(substr($fieldspec, $first_bracket_pos + 1, (strrpos($fieldspec, ')') - $first_bracket_pos - 1)));
        // convert to lowercase just to be sure
        $type = strtolower(chop(substr($fieldspec, 0, $first_bracket_pos)));
    } else {
        $type = $fieldspec;
        $spec_in_brackets = '';
    }

    if ('enum' == $type || 'set' == $type) {
        // Define our working vars
        $enum_set_values = array();
        $working = "";
        $in_string = false;
        $index = 0;

        // While there is another character to process
        while (isset($fieldspec[$index])) {
            // Grab the char to look at
            $char = $fieldspec[$index];

            // If it is a single quote, needs to be handled specially
            if ($char == "'") {
                // If we are not currently in a string, begin one
                if (! $in_string) {
                    $in_string = true;
                    $working = "";
                // Otherwise, it may be either an end of a string, or a 'double quote' which can be handled as-is
                } else {
                // Check out the next character (if possible)
                    $has_next = isset($fieldspec[$index + 1]);
                    $next = $has_next ? $fieldspec[$index + 1] : null;

                // If we have reached the end of our 'working' string (because there are no more chars, or the next char is not another quote)
                    if (! $has_next || $next != "'") {
                        $enum_set_values[] = $working;
                        $in_string = false;

                    // Otherwise, this is a 'double quote', and can be added to the working string
                    } elseif ($next == "'") {
                        $working .= "'";
                        // Skip the next char; we already know what it is
                        $index++;
                    }
                }
            // escaping of a quote?
            } elseif ('\\' == $char && isset($fieldspec[$index + 1]) && "'" == $fieldspec[$index + 1]) {
                $working .= "'";
                $index++;
            // Otherwise, add it to our working string like normal
            } else {
                $working .= $char;
            }
            // Increment character index
            $index++;
        } // end while
    } else {
        $enum_set_values = array();
    }

    return array(
        'type' => $type,
        'spec_in_brackets' => $spec_in_brackets,
        'enum_set_values'  => $enum_set_values
    );
}

/**
 * Verifies if this table's engine supports foreign keys
 *
 * @uses    strtoupper()
 * @param   string $engine
 * @return  boolean
 */
function PMA_foreignkey_supported($engine) {
    $engine = strtoupper($engine);
    if ('INNODB' == $engine || 'PBXT' == $engine) {
        return true;
    } else {
        return false;
    }
}

/**
 * Replaces some characters by a displayable equivalent
 *
 * @uses    str_replace()
 * @param   string $content
 * @return  string the content with characters replaced
 */
function PMA_replace_binary_contents($content) {
    $result = str_replace("\x00", '\0', $content);
    $result = str_replace("\x08", '\b', $result);
    $result = str_replace("\x0a", '\n', $result);
    $result = str_replace("\x0d", '\r', $result);
    $result = str_replace("\x1a", '\Z', $result);
    return $result;
}

/**
 *
 * If the string starts with a \r\n pair (0x0d0a) add an extra \n
 *
 * @uses    strpos()
 * @return  string with the chars replaced
 */

function PMA_duplicateFirstNewline($string){
    $first_occurence = strpos($string, "\r\n");
    if ($first_occurence === 0){
        $string = "\n".$string;
    }
    return $string;
}

/**
 * get the action word corresponding to a script name
 * in order to display it as a title in navigation panel
 *
 * @uses    $GLOBALS
 * @param   string  a valid value for $cfg['LeftDefaultTabTable']
 *                  or $cfg['DefaultTabTable']
 *                  or $cfg['DefaultTabDatabase']
 */
function PMA_getTitleForTarget($target) {

$mapping = array(
	// Values for $cfg['DefaultTabTable']
	'tbl_structure.php' =>  __('Structure'),
	'tbl_sql.php' => __('SQL'),
	'tbl_select.php' =>__('Search'),
	'tbl_change.php' =>__('Insert'),
	'sql.php' => __('Browse'),

	// Values for $cfg['DefaultTabDatabase']
	'db_structure.php' => __('Structure'),
	'db_sql.php' => __('SQL'),
	'db_search.php' => __('Search'),
	'db_operations.php' => __('Operations'),
);
    return $mapping[$target];
}

/**
 * Formats user string, expading @VARIABLES@, accepting strftime format string.
 *
 * @param string Text where to do expansion.
 * @param function Function to call for escaping variable values.
 * @param array Array with overrides for default parameters (obtained from GLOBALS).
 */
function PMA_expandUserString($string, $escape = NULL, $updates = array()) {
    /* Content */
    $vars['http_host'] = PMA_getenv('HTTP_HOST') ? PMA_getenv('HTTP_HOST') : '';
    $vars['server_name'] = $GLOBALS['cfg']['Server']['host'];
    $vars['server_verbose'] = $GLOBALS['cfg']['Server']['verbose'];
    $vars['server_verbose_or_name'] = !empty($GLOBALS['cfg']['Server']['verbose']) ? $GLOBALS['cfg']['Server']['verbose'] : $GLOBALS['cfg']['Server']['host'];
    $vars['database'] = $GLOBALS['db'];
    $vars['table'] = $GLOBALS['table'];
    $vars['phpmyadmin_version'] = 'phpMyAdmin ' . PMA_VERSION;

    /* Update forced variables */
    foreach($updates as $key => $val) {
        $vars[$key] = $val;
    }

    /* Replacement mapping */
    /*
     * The __VAR__ ones are for backward compatibility, because user
     * might still have it in cookies.
     */
    $replace = array(
        '@HTTP_HOST@' => $vars['http_host'],
        '@SERVER@' => $vars['server_name'],
        '__SERVER__' => $vars['server_name'],
        '@VERBOSE@' => $vars['server_verbose'],
        '@VSERVER@' => $vars['server_verbose_or_name'],
        '@DATABASE@' => $vars['database'],
        '__DB__' => $vars['database'],
        '@TABLE@' => $vars['table'],
        '__TABLE__' => $vars['table'],
        '@PHPMYADMIN@' => $vars['phpmyadmin_version'],
        );

    /* Optional escaping */
    if (!is_null($escape)) {
        foreach($replace as $key => $val) {
            $replace[$key] = $escape($val);
        }
    }

    /* Fetch fields list if required */
    if (strpos($string, '@FIELDS@') !== FALSE) {
        $fields_list = PMA_DBI_fetch_result(
            'SHOW COLUMNS FROM ' . PMA_backquote($GLOBALS['db'])
            . '.' . PMA_backquote($GLOBALS['table']));

        $field_names = array();
        foreach ($fields_list as $field) {
            if (!is_null($escape)) {
                $field_names[] = $escape($field['Field']);
            } else {
                $field_names[] = $field['Field'];
            }
        }

        $replace['@FIELDS@'] = implode(',', $field_names);
    }

    /* Do the replacement */
    return str_replace(array_keys($replace), array_values($replace), strftime($string));
}

/**
 * function that generates a json output for an ajax request and ends script
 * execution
 *
 * @param   boolean success whether the ajax request was successfull
 * @param   string  message string containing the html of the message
 * @param   array   extra_data  optional - any other data as part of the json request
 *
 * @uses    header()
 * @uses    json_encode()
 */
function PMA_ajaxResponse($message, $success = true, $extra_data = array())
{
    $response = array();
    if( $success == true ) {
        $response['success'] = true;
        if ($message instanceof PMA_Message) {
            $response['message'] = $message->getDisplay();
        }
        else {
            $response['message'] = $message;
        }
    }
    else {
        $response['success'] = false;
        if($message instanceof PMA_Message) {
            $response['error'] = $message->getDisplay();
        }
        else {
            $response['error'] = $message;
        }
    }

    // If extra_data has been provided, append it to the response array
    if( ! empty($extra_data) && count($extra_data) > 0 ) {
        $response = array_merge($response, $extra_data);
    }

    // Set the Content-Type header to JSON so that jQuery parses the
    // response correctly.
    //
    // At this point, other headers might have been sent;
    // even if $GLOBALS['is_header_sent'] is true,
    // we have to send these additional headers.
    header('Cache-Control: no-cache');
    header("Content-Type: application/json");

    echo json_encode($response);
    exit;
}

/**
 * Display the form used to browse anywhere on the local server for the file to import
 */
function PMA_browseUploadFile($max_upload_size) {
    $uid = uniqid("");
    echo '<label for="radio_import_file">' . __("Browse your computer:") . '</label>';
    echo '<div id="upload_form_status" style="display: none;"></div>';
    echo '<div id="upload_form_status_info" style="display: none;"></div>';
    echo '<input type="file" name="import_file" id="input_import_file" />';
    echo PMA_displayMaximumUploadSize($max_upload_size) . "\n";
    // some browsers should respect this :)
    echo PMA_generateHiddenMaxFileSize($max_upload_size) . "\n";
}

/**
 * Display the form used to select a file to import from the server upload directory
 */
function PMA_selectUploadFile($import_list, $uploaddir) {
	echo '<label for="radio_local_import_file">' . sprintf(__("Select from the web server upload directory <b>%s</b>:"), htmlspecialchars(PMA_userDir($uploaddir))) . '</label>';
	$extensions = '';
    foreach ($import_list as $key => $val) {
        if (!empty($extensions)) {
            $extensions .= '|';
        }
        $extensions .= $val['extension'];
    }
    $matcher = '@\.(' . $extensions . ')(\.(' . PMA_supportedDecompressions() . '))?$@';

    $files = PMA_getFileSelectOptions(PMA_userDir($uploaddir), $matcher, (isset($timeout_passed) && $timeout_passed && isset($local_import_file)) ? $local_import_file : '');
    if ($files === FALSE) {
        PMA_Message::error(__('The directory you set for upload work cannot be reached'))->display();
    } elseif (!empty($files)) {
        echo "\n";
        echo '    <select style="margin: 5px" size="1" name="local_import_file" id="select_local_import_file">' . "\n";
        echo '        <option value="">&nbsp;</option>' . "\n";
        echo $files;
        echo '    </select>' . "\n";
    } elseif (empty ($files)) {
        echo '<i>' . __('There are no files to upload') . '</i>';
    }
}

/**
 * Build titles and icons for action links
 *
 * @return   array   the action titles
 * @uses     PMA_getIcon()
 */
function PMA_buildActionTitles() {
    $titles = array();

    $titles['Browse']     = PMA_getIcon('b_browse.png', __('Browse'), true);
    $titles['NoBrowse']   = PMA_getIcon('bd_browse.png', __('Browse'), true);
    $titles['Search']     = PMA_getIcon('b_select.png', __('Search'), true);
    $titles['NoSearch']   = PMA_getIcon('bd_select.png', __('Search'), true);
    $titles['Insert']     = PMA_getIcon('b_insrow.png', __('Insert'), true);
    $titles['NoInsert']   = PMA_getIcon('bd_insrow.png', __('Insert'), true);
    $titles['Structure']  = PMA_getIcon('b_props.png', __('Structure'), true);
    $titles['Drop']       = PMA_getIcon('b_drop.png', __('Drop'), true);
    $titles['NoDrop']     = PMA_getIcon('bd_drop.png', __('Drop'), true);
    $titles['Empty']      = PMA_getIcon('b_empty.png', __('Empty'), true);
    $titles['NoEmpty']    = PMA_getIcon('bd_empty.png', __('Empty'), true);
    $titles['Edit']       = PMA_getIcon('b_edit.png', __('Edit'), true);
    return $titles;
}
?>
