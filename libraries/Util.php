<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Hold the PMA\libraries\Util class
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries;

use PMA\libraries\plugins\ImportPlugin;
use SqlParser\Context;
use SqlParser\Lexer;
use SqlParser\Parser;
use SqlParser\Token;
use stdClass;
use SqlParser\Utils\Error as ParserError;

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Misc functions used all over the scripts.
 *
 * @package PhpMyAdmin
 */
class Util
{

    /**
     * Detects which function to use for pow.
     *
     * @return string Function name.
     */
    public static function detectPow()
    {
        if (function_exists('bcpow')) {
            // BCMath Arbitrary Precision Mathematics Function
            return 'bcpow';
        } elseif (function_exists('gmp_pow')) {
            // GMP Function
            return 'gmp_pow';
        } else {
            // PHP function
            return 'pow';
        }
    }

    /**
     * Exponential expression / raise number into power
     *
     * @param string $base         base to raise
     * @param string $exp          exponent to use
     * @param string $use_function pow function to use, or false for auto-detect
     *
     * @return mixed string or float
     */
    public static function pow($base, $exp, $use_function = '')
    {
        static $pow_function = null;

        if ($pow_function == null) {
            $pow_function = self::detectPow();
        }

        if (! $use_function) {
            if ($exp < 0) {
                $use_function = 'pow';
            } else {
                $use_function = $pow_function;
            }
        }

        if (($exp < 0) && ($use_function != 'pow')) {
            return false;
        }

        switch ($use_function) {
        case 'bcpow' :
            // bcscale() needed for testing pow() with base values < 1
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
     * Checks whether configuration value tells to show icons.
     *
     * @param string $value Configuration option name
     *
     * @return boolean Whether to show icons.
     */
    public static function showIcons($value)
    {
        return in_array($GLOBALS['cfg'][$value], array('icons', 'both'));
    }

    /**
     * Checks whether configuration value tells to show text.
     *
     * @param string $value Configuration option name
     *
     * @return boolean Whether to show text.
     */
    public static function showText($value)
    {
        return in_array($GLOBALS['cfg'][$value], array('text', 'both'));
    }

    /**
     * Returns an HTML IMG tag for a particular icon from a theme,
     * which may be an actual file or an icon from a sprite.
     * This function takes into account the ActionLinksMode
     * configuration setting and wraps the image tag in a span tag.
     *
     * @param string  $icon          name of icon file
     * @param string  $alternate     alternate text
     * @param boolean $force_text    whether to force alternate text to be displayed
     * @param boolean $menu_icon     whether this icon is for the menu bar or not
     * @param string  $control_param which directive controls the display
     *
     * @return string an html snippet
     */
    public static function getIcon(
        $icon,
        $alternate = '',
        $force_text = false,
        $menu_icon = false,
        $control_param = 'ActionLinksMode'
    ) {
        $include_icon = $include_text = false;
        if (self::showIcons($control_param)) {
            $include_icon = true;
        }
        if ($force_text
            || self::showText($control_param)
        ) {
            $include_text = true;
        }
        // Sometimes use a span (we rely on this in js/sql.js). But for menu bar
        // we don't need a span
        $button = $menu_icon ? '' : '<span class="nowrap">';
        if ($include_icon) {
            $button .= self::getImage($icon, $alternate);
        }
        if ($include_icon && $include_text) {
            $button .= '&nbsp;';
        }
        if ($include_text) {
            $button .= $alternate;
        }
        $button .= $menu_icon ? '' : '</span>';

        return $button;
    }

    /**
     * Returns an HTML IMG tag for a particular image from a theme,
     * which may be an actual file or an icon from a sprite
     *
     * @param string $image      The name of the file to get
     * @param string $alternate  Used to set 'alt' and 'title' attributes
     *                           of the image
     * @param array  $attributes An associative array of other attributes
     *
     * @return string an html IMG tag
     */
    public static function getImage($image, $alternate = '', $attributes = array())
    {
        static $sprites; // cached list of available sprites (if any)
        if (defined('TESTSUITE')) {
            // prevent caching in testsuite
            unset($sprites);
        }

        $is_sprite = false;
        $alternate = htmlspecialchars($alternate);

        // If it's the first time this function is called
        if (! isset($sprites)) {
            $sprites = array();
            // Try to load the list of sprites
            if (isset($_SESSION['PMA_Theme'])) {
                $sprite_file = $_SESSION['PMA_Theme']->getPath() . '/sprites.lib.php';
                if (is_readable($sprite_file)) {
                    include_once $sprite_file;
                    $sprites = PMA_sprites();
                }
            }
        }

        // Check if we have the requested image as a sprite
        //  and set $url accordingly
        $class = str_replace(array('.gif','.png'), '', $image);
        if (array_key_exists($class, $sprites)) {
            $is_sprite = true;
            $url = (defined('PMA_TEST_THEME') ? '../' : '') . 'themes/dot.gif';
        } elseif (isset($GLOBALS['pmaThemeImage'])) {
            $url = $GLOBALS['pmaThemeImage'] . $image;
        } else {
            $url = './themes/pmahomme/' . $image;
        }

        // set class attribute
        if ($is_sprite) {
            if (isset($attributes['class'])) {
                $attributes['class'] = "icon ic_$class " . $attributes['class'];
            } else {
                $attributes['class'] = "icon ic_$class";
            }
        }

        // set all other attributes
        $attr_str = '';
        foreach ($attributes as $key => $value) {
            if (! in_array($key, array('alt', 'title'))) {
                $attr_str .= " $key=\"$value\"";
            }
        }

        // override the alt attribute
        if (isset($attributes['alt'])) {
            $alt = $attributes['alt'];
        } else {
            $alt = $alternate;
        }

        // override the title attribute
        if (isset($attributes['title'])) {
            $title = $attributes['title'];
        } else {
            $title = $alternate;
        }

        // generate the IMG tag
        $template = '<img src="%s" title="%s" alt="%s"%s />';
        $retval = sprintf($template, $url, $title, $alt, $attr_str);

        return $retval;
    }

    /**
     * Returns the formatted maximum size for an upload
     *
     * @param integer $max_upload_size the size
     *
     * @return string the message
     *
     * @access  public
     */
    public static function getFormattedMaximumUploadSize($max_upload_size)
    {
        // I have to reduce the second parameter (sensitiveness) from 6 to 4
        // to avoid weird results like 512 kKib
        list($max_size, $max_unit) = self::formatByteDown($max_upload_size, 4);
        return '(' . sprintf(__('Max: %s%s'), $max_size, $max_unit) . ')';
    }

    /**
     * Generates a hidden field which should indicate to the browser
     * the maximum size for upload
     *
     * @param integer $max_size the size
     *
     * @return string the INPUT field
     *
     * @access  public
     */
    public static function generateHiddenMaxFileSize($max_size)
    {
        return '<input type="hidden" name="MAX_FILE_SIZE" value="'
            . $max_size . '" />';
    }

    /**
     * Add slashes before "'" and "\" characters so a value containing them can
     * be used in a sql comparison.
     *
     * @param string $a_string the string to slash
     * @param bool   $is_like  whether the string will be used in a 'LIKE' clause
     *                         (it then requires two more escaped sequences) or not
     * @param bool   $crlf     whether to treat cr/lfs as escape-worthy entities
     *                         (converts \n to \\n, \r to \\r)
     * @param bool   $php_code whether this function is used as part of the
     *                         "Create PHP code" dialog
     *
     * @return string   the slashed string
     *
     * @access  public
     */
    public static function sqlAddSlashes(
        $a_string = '',
        $is_like = false,
        $crlf = false,
        $php_code = false
    ) {
        if ($is_like) {
            $a_string = str_replace('\\', '\\\\\\\\', $a_string);
        } else {
            $a_string = str_replace('\\', '\\\\', $a_string);
        }

        if ($crlf) {
            $a_string = strtr(
                $a_string,
                array("\n" => '\n', "\r" => '\r', "\t" => '\t')
            );
        }

        if ($php_code) {
            $a_string = str_replace('\'', '\\\'', $a_string);
        } else {
            $a_string = str_replace('\'', '\\\'', $a_string);
        }

        return $a_string;
    } // end of the 'sqlAddSlashes()' function

    /**
     * Add slashes before "_" and "%" characters for using them in MySQL
     * database, table and field names.
     * Note: This function does not escape backslashes!
     *
     * @param string $name the string to escape
     *
     * @return string the escaped string
     *
     * @access  public
     */
    public static function escapeMysqlWildcards($name)
    {
        return strtr($name, array('_' => '\\_', '%' => '\\%'));
    } // end of the 'escapeMysqlWildcards()' function

    /**
     * removes slashes before "_" and "%" characters
     * Note: This function does not unescape backslashes!
     *
     * @param string $name the string to escape
     *
     * @return string   the escaped string
     *
     * @access  public
     */
    public static function unescapeMysqlWildcards($name)
    {
        return strtr($name, array('\\_' => '_', '\\%' => '%'));
    } // end of the 'unescapeMysqlWildcards()' function

    /**
     * removes quotes (',",`) from a quoted string
     *
     * checks if the string is quoted and removes this quotes
     *
     * @param string $quoted_string string to remove quotes from
     * @param string $quote         type of quote to remove
     *
     * @return string unqoted string
     */
    public static function unQuote($quoted_string, $quote = null)
    {
        $quotes = array();

        if ($quote === null) {
            $quotes[] = '`';
            $quotes[] = '"';
            $quotes[] = "'";
        } else {
            $quotes[] = $quote;
        }

        foreach ($quotes as $quote) {
            if (mb_substr($quoted_string, 0, 1) === $quote
                && mb_substr($quoted_string, -1, 1) === $quote
            ) {
                $unquoted_string = mb_substr($quoted_string, 1, -1);
                // replace escaped quotes
                $unquoted_string = str_replace(
                    $quote . $quote,
                    $quote,
                    $unquoted_string
                );
                return $unquoted_string;
            }
        }

        return $quoted_string;
    }

    /**
     * format sql strings
     *
     * @param string  $sqlQuery raw SQL string
     * @param boolean $truncate truncate the query if it is too long
     *
     * @return string the formatted sql
     *
     * @global array  $cfg the configuration array
     *
     * @access  public
     * @todo    move into PMA_Sql
     */
    public static function formatSql($sqlQuery, $truncate = false)
    {
        global $cfg;

        if ($truncate
            && mb_strlen($sqlQuery) > $cfg['MaxCharactersInDisplayedSQL']
        ) {
            $sqlQuery = mb_substr(
                $sqlQuery,
                0,
                $cfg['MaxCharactersInDisplayedSQL']
            ) . '[...]';
        }
        return '<code class="sql"><pre>' . "\n"
            . htmlspecialchars($sqlQuery) . "\n"
            . '</pre></code>';
    } // end of the "formatSql()" function

    /**
     * Displays a link to the documentation as an icon
     *
     * @param string  $link   documentation link
     * @param string  $target optional link target
     * @param boolean $bbcode optional flag indicating whether to output bbcode
     *
     * @return string the html link
     *
     * @access public
     */
    public static function showDocLink($link, $target = 'documentation', $bbcode = false)
    {
        if($bbcode){
            return "[a@$link@$target][dochelpicon][/a]";
        }else{
            return '<a href="' . $link . '" target="' . $target . '">'
                . self::getImage('b_help.png', __('Documentation'))
                . '</a>';
        }
    } // end of the 'showDocLink()' function

    /**
     * Get a URL link to the official MySQL documentation
     *
     * @param string $link   contains name of page/anchor that is being linked
     * @param string $anchor anchor to page part
     *
     * @return string  the URL link
     *
     * @access  public
     */
    public static function getMySQLDocuURL($link, $anchor = '')
    {
        // Fixup for newly used names:
        $link = str_replace('_', '-', mb_strtolower($link));

        if (empty($link)) {
            $link = 'index';
        }
        $mysql = '5.5';
        $lang = 'en';
        if (defined('PMA_MYSQL_INT_VERSION')) {
            if (PMA_MYSQL_INT_VERSION >= 50700) {
                $mysql = '5.7';
            } elseif (PMA_MYSQL_INT_VERSION >= 50600) {
                $mysql = '5.6';
            } elseif (PMA_MYSQL_INT_VERSION >= 50500) {
                $mysql = '5.5';
            }
        }
        $url = 'https://dev.mysql.com/doc/refman/'
            . $mysql . '/' . $lang . '/' . $link . '.html';
        if (! empty($anchor)) {
            $url .= '#' . $anchor;
        }

        return PMA_linkURL($url);
    }

    /**
     * Displays a link to the official MySQL documentation
     *
     * @param string $link      contains name of page/anchor that is being linked
     * @param bool   $big_icon  whether to use big icon (like in left frame)
     * @param string $anchor    anchor to page part
     * @param bool   $just_open whether only the opening <a> tag should be returned
     *
     * @return string  the html link
     *
     * @access  public
     */
    public static function showMySQLDocu(
        $link,
        $big_icon = false,
        $anchor = '',
        $just_open = false
    ) {
        $url = self::getMySQLDocuURL($link, $anchor);
        $open_link = '<a href="' . $url . '" target="mysql_doc">';
        if ($just_open) {
            return $open_link;
        } elseif ($big_icon) {
            return $open_link
                . self::getImage('b_sqlhelp.png', __('Documentation')) . '</a>';
        } else {
            return self::showDocLink($url, 'mysql_doc');
        }
    } // end of the 'showMySQLDocu()' function

    /**
     * Returns link to documentation.
     *
     * @param string $page   Page in documentation
     * @param string $anchor Optional anchor in page
     *
     * @return string URL
     */
    public static function getDocuLink($page, $anchor = '')
    {
        /* Construct base URL */
        $url =  $page . '.html';
        if (!empty($anchor)) {
            $url .= '#' . $anchor;
        }

        /* Check if we have built local documentation */
        if (defined('TESTSUITE')) {
            /* Provide consistent URL for testsuite */
            return PMA_linkURL('https://docs.phpmyadmin.net/en/latest/' . $url);
        } elseif (@file_exists('doc/html/index.html')) {
            if (defined('PMA_SETUP')) {
                return '../doc/html/' . $url;
            } else {
                return './doc/html/' . $url;
            }
        } else {
            /* TODO: Should link to correct branch for released versions */
            return PMA_linkURL('https://docs.phpmyadmin.net/en/latest/' . $url);
        }
    }

    /**
     * Displays a link to the phpMyAdmin documentation
     *
     * @param string  $page   Page in documentation
     * @param string  $anchor Optional anchor in page
     * @param boolean $bbcode Optional flag indicating whether to output bbcode
     *
     * @return string  the html link
     *
     * @access  public
     */
    public static function showDocu($page, $anchor = '', $bbcode = false)
    {
        return self::showDocLink(self::getDocuLink($page, $anchor), 'documentation', $bbcode);
    } // end of the 'showDocu()' function

    /**
     * Displays a link to the PHP documentation
     *
     * @param string $target anchor in documentation
     *
     * @return string  the html link
     *
     * @access  public
     */
    public static function showPHPDocu($target)
    {
        $url = PMA_getPHPDocLink($target);

        return self::showDocLink($url);
    } // end of the 'showPHPDocu()' function

    /**
     * Returns HTML code for a tooltip
     *
     * @param string $message the message for the tooltip
     *
     * @return string
     *
     * @access  public
     */
    public static function showHint($message)
    {
        if ($GLOBALS['cfg']['ShowHint']) {
            $classClause = ' class="pma_hint"';
        } else {
            $classClause = '';
        }
        return '<span' . $classClause . '>'
            . self::getImage('b_help.png')
            . '<span class="hide">' . $message . '</span>'
            . '</span>';
    }

    /**
     * Displays a MySQL error message in the main panel when $exit is true.
     * Returns the error message otherwise.
     *
     * @param string|bool $server_msg     Server's error message.
     * @param string      $sql_query      The SQL query that failed.
     * @param bool        $is_modify_link Whether to show a "modify" link or not.
     * @param string      $back_url       URL for the "back" link (full path is
     *                                    not required).
     * @param bool        $exit           Whether execution should be stopped or
     *                                    the error message should be returned.
     *
     * @return string
     *
     * @global string $table The current table.
     * @global string $db    The current database.
     *
     * @access public
     */
    public static function mysqlDie(
        $server_msg = '',
        $sql_query = '',
        $is_modify_link = true,
        $back_url = '',
        $exit = true
    ) {
        global $table, $db;

        /**
         * Error message to be built.
         * @var string $error_msg
         */
        $error_msg = '';

        // Checking for any server errors.
        if (empty($server_msg)) {
            $server_msg = $GLOBALS['dbi']->getError();
        }

        // Finding the query that failed, if not specified.
        if ((empty($sql_query) && (!empty($GLOBALS['sql_query'])))) {
            $sql_query = $GLOBALS['sql_query'];
        }
        $sql_query = trim($sql_query);

        /**
         * The lexer used for analysis.
         * @var Lexer $lexer
         */
        $lexer = new Lexer($sql_query);

        /**
         * The parser used for analysis.
         * @var Parser $parser
         */
        $parser = new Parser($lexer->list);

        /**
         * The errors found by the lexer and the parser.
         * @var array $errors
         */
        $errors = ParserError::get(array($lexer, $parser));

        if (empty($sql_query)) {
            $formatted_sql = '';
        } elseif (count($errors)) {
            $formatted_sql = htmlspecialchars($sql_query);
        } else {
            $formatted_sql = self::formatSql($sql_query, true);
        }

        $error_msg .= '<div class="error"><h1>' . __('Error') . '</h1>';

        // For security reasons, if the MySQL refuses the connection, the query
        // is hidden so no details are revealed.
        if ((!empty($sql_query)) && (!(mb_strstr($sql_query, 'connect')))) {
            // Static analysis errors.
            if (!empty($errors)) {
                $error_msg .= '<p><strong>' . __('Static analysis:')
                    . '</strong></p>';
                $error_msg .= '<p>' . sprintf(
                    __('%d errors were found during analysis.'),
                    count($errors)
                ) . '</p>';
                $error_msg .= '<p><ol>';
                $error_msg .= implode(
                    ParserError::format(
                        $errors,
                        '<li>%2$s (near "%4$s" at position %5$d)</li>'
                    )
                );
                $error_msg .= '</ol></p>';
            }

            // Display the SQL query and link to MySQL documentation.
            $error_msg .= '<p><strong>' . __('SQL query:') . '</strong>' . "\n";
            $formattedSqlToLower = mb_strtolower($formatted_sql);

            // TODO: Show documentation for all statement types.
            if (mb_strstr($formattedSqlToLower, 'select')) {
                // please show me help to the error on select
                $error_msg .= self::showMySQLDocu('SELECT');
            }

            if ($is_modify_link) {
                $_url_params = array(
                    'sql_query' => $sql_query,
                    'show_query' => 1,
                );
                if (mb_strlen($table)) {
                    $_url_params['db'] = $db;
                    $_url_params['table'] = $table;
                    $doedit_goto = '<a href="tbl_sql.php'
                        . PMA_URL_getCommon($_url_params) . '">';
                } elseif (mb_strlen($db)) {
                    $_url_params['db'] = $db;
                    $doedit_goto = '<a href="db_sql.php'
                        . PMA_URL_getCommon($_url_params) . '">';
                } else {
                    $doedit_goto = '<a href="server_sql.php'
                        . PMA_URL_getCommon($_url_params) . '">';
                }

                $error_msg .= $doedit_goto
                   . self::getIcon('b_edit.png', __('Edit'))
                   . '</a>';
            }

            $error_msg .= '    </p>' . "\n"
                . '<p>' . "\n"
                . $formatted_sql . "\n"
                . '</p>' . "\n";
        }

        // Display server's error.
        if (!empty($server_msg)) {
            $server_msg = preg_replace(
                "@((\015\012)|(\015)|(\012)){3,}@",
                "\n\n",
                $server_msg
            );

            // Adds a link to MySQL documentation.
            $error_msg .= '<p>' . "\n"
                . '    <strong>' . __('MySQL said: ') . '</strong>'
                . self::showMySQLDocu('Error-messages-server')
                . "\n"
                . '</p>' . "\n";

            // The error message will be displayed within a CODE segment.
            // To preserve original formatting, but allow word-wrapping,
            // a couple of replacements are done.
            // All non-single blanks and  TAB-characters are replaced with their
            // HTML-counterpart
            $server_msg = str_replace(
                array('  ', "\t"),
                array('&nbsp;&nbsp;', '&nbsp;&nbsp;&nbsp;&nbsp;'),
                $server_msg
            );

            // Replace line breaks
            $server_msg = nl2br($server_msg);

            $error_msg .= '<code>' . $server_msg . '</code><br/>';
        }

        $error_msg .= '</div>';
        $_SESSION['Import_message']['message'] = $error_msg;

        if (!$exit) {
            return $error_msg;
        }

        /**
         * If this is an AJAX request, there is no "Back" link and
         * `Response()` is used to send the response.
         */
        if (!empty($GLOBALS['is_ajax_request'])) {
            $response = Response::getInstance();
            $response->setRequestStatus(false);
            $response->addJSON('message', $error_msg);
            exit;
        }

        if (!empty($back_url)) {
            if (mb_strstr($back_url, '?')) {
                $back_url .= '&amp;no_history=true';
            } else {
                $back_url .= '?no_history=true';
            }

            $_SESSION['Import_message']['go_back_url'] = $back_url;

            $error_msg .= '<fieldset class="tblFooters">'
                . '[ <a href="' . $back_url . '">' . __('Back') . '</a> ]'
                . '</fieldset>' . "\n\n";
        }

        exit($error_msg);
    }

    /**
     * Check the correct row count
     *
     * @param string $db    the db name
     * @param array  $table the table infos
     *
     * @return int $rowCount the possibly modified row count
     *
     */
    private static function _checkRowCount($db, $table)
    {
        $rowCount = 0;

        if ($table['Rows'] === null) {
            // Do not check exact row count here,
            // if row count is invalid possibly the table is defect
            // and this would break the navigation panel;
            // but we can check row count if this is a view or the
            // information_schema database
            // since Table::countRecords() returns a limited row count
            // in this case.

            // set this because Table::countRecords() can use it
            $tbl_is_view = $table['TABLE_TYPE'] == 'VIEW';

            if ($tbl_is_view || $GLOBALS['dbi']->isSystemSchema($db)) {
                $rowCount = $GLOBALS['dbi']
                    ->getTable($db, $table['Name'])
                    ->countRecords();
            }
        }
        return $rowCount;
    }

    /**
     * returns array with tables of given db with extended information and grouped
     *
     * @param string   $db           name of db
     * @param string   $tables       name of tables
     * @param integer  $limit_offset list offset
     * @param int|bool $limit_count  max tables to return
     *
     * @return array    (recursive) grouped table list
     */
    public static function getTableList(
        $db,
        $tables = null,
        $limit_offset = 0,
        $limit_count = false
    ) {
        $sep = $GLOBALS['cfg']['NavigationTreeTableSeparator'];

        if ($tables === null) {
            $tables = $GLOBALS['dbi']->getTablesFull(
                $db,
                '',
                false,
                null,
                $limit_offset,
                $limit_count
            );
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

        foreach ($tables as $table_name => $table) {
            $table['Rows'] = self::_checkRowCount($db, $table);

            // in $group we save the reference to the place in $table_groups
            // where to store the table info
            if ($GLOBALS['cfg']['NavigationTreeEnableGrouping']
                && $sep && mb_strstr($table_name, $sep)
            ) {
                $parts = explode($sep, $table_name);

                $group =& $table_groups;
                $i = 0;
                $group_name_full = '';
                $parts_cnt = count($parts) - 1;

                while (($i < $parts_cnt)
                    && ($i < $GLOBALS['cfg']['NavigationTreeTableLevel'])
                ) {
                    $group_name = $parts[$i] . $sep;
                    $group_name_full .= $group_name;

                    if (! isset($group[$group_name])) {
                        $group[$group_name] = array();
                        $group[$group_name]['is' . $sep . 'group'] = true;
                        $group[$group_name]['tab' . $sep . 'count'] = 1;
                        $group[$group_name]['tab' . $sep . 'group']
                            = $group_name_full;

                    } elseif (! isset($group[$group_name]['is' . $sep . 'group'])) {
                        $table = $group[$group_name];
                        $group[$group_name] = array();
                        $group[$group_name][$group_name] = $table;
                        $group[$group_name]['is' . $sep . 'group'] = true;
                        $group[$group_name]['tab' . $sep . 'count'] = 1;
                        $group[$group_name]['tab' . $sep . 'group']
                            = $group_name_full;

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

            $table['disp_name'] = $table['Name'];
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
     * echo backquote('owner`s db'); // `owner``s db`
     *
     * </code>
     *
     * @param mixed   $a_name the database, table or field name to "backquote"
     *                        or array of it
     * @param boolean $do_it  a flag to bypass this function (used by dump
     *                        functions)
     *
     * @return mixed    the "backquoted" database, table or field name
     *
     * @access  public
     */
    public static function backquote($a_name, $do_it = true)
    {
        if (is_array($a_name)) {
            foreach ($a_name as &$data) {
                $data = self::backquote($data, $do_it);
            }
            return $a_name;
        }

        if (! $do_it) {
            if (!(Context::isKeyword($a_name) & Token::FLAG_KEYWORD_RESERVED)
            ) {
                return $a_name;
            }
        }

        // '0' is also empty for php :-(
        if (mb_strlen($a_name) && $a_name !== '*') {
            return '`' . str_replace('`', '``', $a_name) . '`';
        } else {
            return $a_name;
        }
    } // end of the 'backquote()' function

    /**
     * Adds backquotes on both sides of a database, table or field name.
     * in compatibility mode
     *
     * example:
     * <code>
     * echo backquoteCompat('owner`s db'); // `owner``s db`
     *
     * </code>
     *
     * @param mixed   $a_name        the database, table or field name to
     *                               "backquote" or array of it
     * @param string  $compatibility string compatibility mode (used by dump
     *                               functions)
     * @param boolean $do_it         a flag to bypass this function (used by dump
     *                               functions)
     *
     * @return mixed the "backquoted" database, table or field name
     *
     * @access  public
     */
    public static function backquoteCompat(
        $a_name,
        $compatibility = 'MSSQL',
        $do_it = true
    ) {
        if (is_array($a_name)) {
            foreach ($a_name as &$data) {
                $data = self::backquoteCompat($data, $compatibility, $do_it);
            }
            return $a_name;
        }

        if (! $do_it) {
            if (!Context::isKeyword($a_name)) {
                return $a_name;
            }
        }

        // @todo add more compatibility cases (ORACLE for example)
        switch ($compatibility) {
        case 'MSSQL':
            $quote = '"';
            break;
        default:
            $quote = "`";
            break;
        }

        // '0' is also empty for php :-(
        if (mb_strlen($a_name) && $a_name !== '*') {
            return $quote . $a_name . $quote;
        } else {
            return $a_name;
        }
    } // end of the 'backquoteCompat()' function

    /**
     * Defines the <CR><LF> value depending on the user OS.
     *
     * @return string   the <CR><LF> value to use
     *
     * @access  public
     */
    public static function whichCrlf()
    {
        // The 'PMA_USR_OS' constant is defined in "libraries/Config.php"
        // Win case
        if (PMA_USR_OS == 'Win') {
            $the_crlf = "\r\n";
        } else {
            // Others
            $the_crlf = "\n";
        }

        return $the_crlf;
    } // end of the 'whichCrlf()' function

    /**
     * Prepare the message and the query
     * usually the message is the result of the query executed
     *
     * @param Message|string $message   the message to display
     * @param string         $sql_query the query to display
     * @param string         $type      the type (level) of the message
     *
     * @return string
     *
     * @access  public
     */
    public static function getMessage(
        $message,
        $sql_query = null,
        $type = 'notice'
    ) {
        global $cfg;
        $retval = '';

        if (null === $sql_query) {
            if (! empty($GLOBALS['display_query'])) {
                $sql_query = $GLOBALS['display_query'];
            } elseif (! empty($GLOBALS['unparsed_sql'])) {
                $sql_query = $GLOBALS['unparsed_sql'];
            } elseif (! empty($GLOBALS['sql_query'])) {
                $sql_query = $GLOBALS['sql_query'];
            } else {
                $sql_query = '';
            }
        }

        $render_sql = $cfg['ShowSQL'] == true && ! empty($sql_query) && $sql_query !== ';';

        if (isset($GLOBALS['using_bookmark_message'])) {
            $retval .= $GLOBALS['using_bookmark_message']->getDisplay();
            unset($GLOBALS['using_bookmark_message']);
        }

        if ($render_sql) {
            $retval .= '<div class="result_query"'
                . ' style="text-align: ' . $GLOBALS['cell_align_left'] . '"'
                . '>' . "\n";
        }

        if ($message instanceof Message) {
            if (isset($GLOBALS['special_message'])) {
                $message->addMessage($GLOBALS['special_message']);
                unset($GLOBALS['special_message']);
            }
            $retval .= $message->getDisplay();
        } else {
            $retval .= '<div class="' . $type . '">';
            $retval .= PMA_sanitize($message);
            if (isset($GLOBALS['special_message'])) {
                $retval .= PMA_sanitize($GLOBALS['special_message']);
                unset($GLOBALS['special_message']);
            }
            $retval .= '</div>';
        }

        if ($render_sql) {
            // Html format the query to be displayed
            // If we want to show some sql code it is easiest to create it here
            /* SQL-Parser-Analyzer */

            if (! empty($GLOBALS['show_as_php'])) {
                $new_line = '\\n"<br />' . "\n"
                    . '&nbsp;&nbsp;&nbsp;&nbsp;. "';
                $query_base = htmlspecialchars(addslashes($sql_query));
                $query_base = preg_replace(
                    '/((\015\012)|(\015)|(\012))/',
                    $new_line,
                    $query_base
                );
            } else {
                $query_base = $sql_query;
            }

            $query_too_big = false;

            $queryLength = mb_strlen($query_base);
            if ($queryLength > $cfg['MaxCharactersInDisplayedSQL']) {
                // when the query is large (for example an INSERT of binary
                // data), the parser chokes; so avoid parsing the query
                $query_too_big = true;
                $shortened_query_base = nl2br(
                    htmlspecialchars(
                        mb_substr(
                            $sql_query,
                            0,
                            $cfg['MaxCharactersInDisplayedSQL']
                        ) . '[...]'
                    )
                );
            }

            if (! empty($GLOBALS['show_as_php'])) {
                $query_base = '$sql  = "' . $query_base;
            } elseif (isset($query_base)) {
                $query_base = self::formatSql($query_base);
            }

            // Prepares links that may be displayed to edit/explain the query
            // (don't go to default pages, we must go to the page
            // where the query box is available)

            // Basic url query part
            $url_params = array();
            if (! isset($GLOBALS['db'])) {
                $GLOBALS['db'] = '';
            }
            if (mb_strlen($GLOBALS['db'])) {
                $url_params['db'] = $GLOBALS['db'];
                if (mb_strlen($GLOBALS['table'])) {
                    $url_params['table'] = $GLOBALS['table'];
                    $edit_link = 'tbl_sql.php';
                } else {
                    $edit_link = 'db_sql.php';
                }
            } else {
                $edit_link = 'server_sql.php';
            }

            // Want to have the query explained
            // but only explain a SELECT (that has not been explained)
            /* SQL-Parser-Analyzer */
            $explain_link = '';
            $is_select = preg_match('@^SELECT[[:space:]]+@i', $sql_query);
            if (! empty($cfg['SQLQuery']['Explain']) && ! $query_too_big) {
                $explain_params = $url_params;
                if ($is_select) {
                    $explain_params['sql_query'] = 'EXPLAIN ' . $sql_query;
                    $explain_link = ' ['
                        . self::linkOrButton(
                            'import.php' . PMA_URL_getCommon($explain_params),
                            __('Explain SQL')
                        ) . ']';
                } elseif (preg_match(
                    '@^EXPLAIN[[:space:]]+SELECT[[:space:]]+@i',
                    $sql_query
                )) {
                    $explain_params['sql_query']
                        = mb_substr($sql_query, 8);
                    $explain_link = ' ['
                        . self::linkOrButton(
                            'import.php' . PMA_URL_getCommon($explain_params),
                            __('Skip Explain SQL')
                        ) . ']';
                    $url = 'https://mariadb.org/explain_analyzer/analyze/'
                        . '?client=phpMyAdmin&raw_explain='
                        . urlencode(self::_generateRowQueryOutput($sql_query));
                    $explain_link .= ' ['
                        . self::linkOrButton(
                            'url.php?url=' . urlencode($url),
                            sprintf(__('Analyze Explain at %s'), 'mariadb.org'),
                            array(),
                            true,
                            false,
                            '_blank'
                        ) . ']';
                }
            } //show explain

            $url_params['sql_query']  = $sql_query;
            $url_params['show_query'] = 1;

            // even if the query is big and was truncated, offer the chance
            // to edit it (unless it's enormous, see linkOrButton() )
            if (! empty($cfg['SQLQuery']['Edit'])) {
                $edit_link .= PMA_URL_getCommon($url_params) . '#querybox';
                $edit_link = ' ['
                    . self::linkOrButton($edit_link, __('Edit'))
                    . ']';
            } else {
                $edit_link = '';
            }

            // Also we would like to get the SQL formed in some nice
            // php-code
            if (! empty($cfg['SQLQuery']['ShowAsPHP']) && ! $query_too_big) {

                if (! empty($GLOBALS['show_as_php'])) {
                    $php_link = ' ['
                        . self::linkOrButton(
                            'import.php' . PMA_URL_getCommon($url_params),
                            __('Without PHP code'),
                            array(),
                            true,
                            false,
                            '',
                            true
                        )
                        . ']';

                    $php_link .= ' ['
                        . self::linkOrButton(
                            'import.php' . PMA_URL_getCommon($url_params),
                            __('Submit query'),
                            array(),
                            true,
                            false,
                            '',
                            true
                        )
                        . ']';
                } else {
                    $php_params = $url_params;
                    $php_params['show_as_php'] = 1;
                    $_message = __('Create PHP code');
                    $php_link = ' ['
                        . self::linkOrButton(
                            'import.php' . PMA_URL_getCommon($php_params),
                            $_message
                        )
                        . ']';
                }
            } else {
                $php_link = '';
            } //show as php

            // Refresh query
            if (! empty($cfg['SQLQuery']['Refresh'])
                && ! isset($GLOBALS['show_as_php']) // 'Submit query' does the same
                && preg_match('@^(SELECT|SHOW)[[:space:]]+@i', $sql_query)
            ) {
                $refresh_link = 'import.php' . PMA_URL_getCommon($url_params);
                $refresh_link = ' ['
                    . self::linkOrButton($refresh_link, __('Refresh')) . ']';
            } else {
                $refresh_link = '';
            } //refresh

            $retval .= '<div class="sqlOuter">';
            if ($query_too_big) {
                $retval .= $shortened_query_base;
            } else {
                $retval .= $query_base;
            }

            //Clean up the end of the PHP
            if (! empty($GLOBALS['show_as_php'])) {
                $retval .= '";';
            }
            $retval .= '</div>';

            $retval .= '<div class="tools print_ignore">';
            $retval .= '<form action="sql.php" method="post">';
            $retval .= PMA_URL_getHiddenInputs($GLOBALS['db'], $GLOBALS['table']);
            $retval .= '<input type="hidden" name="sql_query" value="'
                . htmlspecialchars($sql_query) . '" />';

            // avoid displaying a Profiling checkbox that could
            // be checked, which would reexecute an INSERT, for example
            if (! empty($refresh_link) && self::profilingSupported()) {
                $retval .= '<input type="hidden" name="profiling_form" value="1" />';
                $retval .= self::getCheckbox(
                    'profiling',
                    __('Profiling'),
                    isset($_SESSION['profiling']),
                    true
                );
            }
            $retval .= '</form>';

            /**
             * TODO: Should we have $cfg['SQLQuery']['InlineEdit']?
             */
            if (! empty($cfg['SQLQuery']['Edit']) && ! $query_too_big) {
                $inline_edit_link = ' ['
                    . self::linkOrButton(
                        '#',
                        _pgettext('Inline edit query', 'Edit inline'),
                        array('class' => 'inline_edit_sql')
                    )
                    . ']';
            } else {
                $inline_edit_link = '';
            }
            $retval .= $inline_edit_link . $edit_link . $explain_link . $php_link
                . $refresh_link;
            $retval .= '</div>';

            $retval .= '</div>';
        }


        return $retval;
    } // end of the 'getMessage()' function

    /**
     * Execute an EXPLAIN query and formats results similar to MySQL command line
     * utility.
     *
     * @param string $sqlQuery EXPLAIN query
     *
     * @return string query resuls
     */
    private static function _generateRowQueryOutput($sqlQuery)
    {
        $ret = '';
        $result = $GLOBALS['dbi']->query($sqlQuery);
        if ($result) {
            $devider = '+';
            $columnNames = '|';
            $fieldsMeta = $GLOBALS['dbi']->getFieldsMeta($result);
            foreach ($fieldsMeta as $meta) {
                $devider .= '---+';
                $columnNames .= ' ' . $meta->name . ' |';
            }
            $devider .= "\n";

            $ret .= $devider . $columnNames . "\n" . $devider;
            while ($row = $GLOBALS['dbi']->fetchRow($result)) {
                $values = '|';
                foreach ($row as $value) {
                    if (is_null($value)) {
                        $value = 'NULL';
                    }
                    $values .= ' ' . $value . ' |';
                }
                $ret .= $values . "\n";
            }
            $ret .= $devider;
        }
        return $ret;
    }

    /**
     * Verifies if current MySQL server supports profiling
     *
     * @access  public
     *
     * @return boolean whether profiling is supported
     */
    public static function profilingSupported()
    {
        if (!self::cacheExists('profiling_supported')) {
            // 5.0.37 has profiling but for example, 5.1.20 does not
            // (avoid a trip to the server for MySQL before 5.0.37)
            // and do not set a constant as we might be switching servers
            if (defined('PMA_MYSQL_INT_VERSION')
                && $GLOBALS['dbi']->fetchValue("SELECT @@have_profiling")
            ) {
                self::cacheSet('profiling_supported', true);
            } else {
                self::cacheSet('profiling_supported', false);
            }
        }

        return self::cacheGet('profiling_supported');
    }

    /**
     * Formats $value to byte view
     *
     * @param double|int $value the value to format
     * @param int        $limes the sensitiveness
     * @param int        $comma the number of decimals to retain
     *
     * @return array    the formatted value and its unit
     *
     * @access  public
     */
    public static function formatByteDown($value, $limes = 6, $comma = 0)
    {
        if ($value === null) {
            return null;
        }

        $byteUnits = array(
            /* l10n: shortcuts for Byte */
            __('B'),
            /* l10n: shortcuts for Kilobyte */
            __('KiB'),
            /* l10n: shortcuts for Megabyte */
            __('MiB'),
            /* l10n: shortcuts for Gigabyte */
            __('GiB'),
            /* l10n: shortcuts for Terabyte */
            __('TiB'),
            /* l10n: shortcuts for Petabyte */
            __('PiB'),
            /* l10n: shortcuts for Exabyte */
            __('EiB')
        );

        $dh   = self::pow(10, $comma);
        $li   = self::pow(10, $limes);
        $unit = $byteUnits[0];

        for ($d = 6, $ex = 15; $d >= 1; $d--, $ex-=3) {
            // cast to float to avoid overflow
            $unitSize = (float) $li * self::pow(10, $ex);
            if (isset($byteUnits[$d]) && $value >= $unitSize) {
                // use 1024.0 to avoid integer overflow on 64-bit machines
                $value = round($value / (self::pow(1024, $d) / $dh)) /$dh;
                $unit = $byteUnits[$d];
                break 1;
            } // end if
        } // end for

        if ($unit != $byteUnits[0]) {
            // if the unit is not bytes (as represented in current language)
            // reformat with max length of 5
            // 4th parameter=true means do not reformat if value < 1
            $return_value = self::formatNumber($value, 5, $comma, true);
        } else {
            // do not reformat, just handle the locale
            $return_value = self::formatNumber($value, 0);
        }

        return array(trim($return_value), $unit);
    } // end of the 'formatByteDown' function

    /**
     * Changes thousands and decimal separators to locale specific values.
     *
     * @param string $value the value
     *
     * @return string
     */
    public static function localizeNumber($value)
    {
        return str_replace(
            array(',', '.'),
            array(
                /* l10n: Thousands separator */
                __(','),
                /* l10n: Decimal separator */
                __('.'),
            ),
            $value
        );
    }

    /**
     * Formats $value to the given length and appends SI prefixes
     * with a $length of 0 no truncation occurs, number is only formatted
     * to the current locale
     *
     * examples:
     * <code>
     * echo formatNumber(123456789, 6);     // 123,457 k
     * echo formatNumber(-123456789, 4, 2); //    -123.46 M
     * echo formatNumber(-0.003, 6);        //      -3 m
     * echo formatNumber(0.003, 3, 3);      //       0.003
     * echo formatNumber(0.00003, 3, 2);    //       0.03 m
     * echo formatNumber(0, 6);             //       0
     * </code>
     *
     * @param double  $value          the value to format
     * @param integer $digits_left    number of digits left of the comma
     * @param integer $digits_right   number of digits right of the comma
     * @param boolean $only_down      do not reformat numbers below 1
     * @param boolean $noTrailingZero removes trailing zeros right of the comma
     *                                (default: true)
     *
     * @return string   the formatted value and its unit
     *
     * @access  public
     */
    public static function formatNumber(
        $value,
        $digits_left = 3,
        $digits_right = 0,
        $only_down = false,
        $noTrailingZero = true
    ) {
        if ($value == 0) {
            return '0';
        }

        $originalValue = $value;
        //number_format is not multibyte safe, str_replace is safe
        if ($digits_left === 0) {
            $value = number_format($value, $digits_right);
            if (($originalValue != 0) && (floatval($value) == 0)) {
                $value = ' <' . (1 / self::pow(10, $digits_right));
            }
            return self::localizeNumber($value);
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

        // check for negative value to retain sign
        if ($value < 0) {
            $sign = '-';
            $value = abs($value);
        } else {
            $sign = '';
        }

        $dh = self::pow(10, $digits_right);

        /*
         * This gives us the right SI prefix already,
         * but $digits_left parameter not incorporated
         */
        $d = floor(log10($value) / 3);
        /*
         * Lowering the SI prefix by 1 gives us an additional 3 zeros
         * So if we have 3,6,9,12.. free digits ($digits_left - $cur_digits)
         * to use, then lower the SI prefix
         */
        $cur_digits = floor(log10($value / self::pow(1000, $d, 'pow'))+1);
        if ($digits_left > $cur_digits) {
            $d -= floor(($digits_left - $cur_digits)/3);
        }

        if ($d < 0 && $only_down) {
            $d = 0;
        }

        $value = round($value / (self::pow(1000, $d, 'pow') / $dh)) /$dh;
        $unit = $units[$d];

        // If we don't want any zeros after the comma just add the thousand separator
        if ($noTrailingZero) {
            $localizedValue = self::localizeNumber(
                preg_replace('/(?<=\d)(?=(\d{3})+(?!\d))/', ',', $value)
            );
        } else {
            //number_format is not multibyte safe, str_replace is safe
            $localizedValue = self::localizeNumber(
                number_format($value, $digits_right)
            );
        }

        if ($originalValue != 0 && floatval($value) == 0) {
            return ' <' . self::localizeNumber((1 / self::pow(10, $digits_right)))
            . ' ' . $unit;
        }

        return $sign . $localizedValue . ' ' . $unit;
    } // end of the 'formatNumber' function

    /**
     * Returns the number of bytes when a formatted size is given
     *
     * @param string $formatted_size the size expression (for example 8MB)
     *
     * @return integer  The numerical part of the expression (for example 8)
     */
    public static function extractValueFromFormattedSize($formatted_size)
    {
        $return_value = -1;

        if (preg_match('/^[0-9]+GB$/', $formatted_size)) {
            $return_value = mb_substr($formatted_size, 0, -2)
                * self::pow(1024, 3);
        } elseif (preg_match('/^[0-9]+MB$/', $formatted_size)) {
            $return_value = mb_substr($formatted_size, 0, -2)
                * self::pow(1024, 2);
        } elseif (preg_match('/^[0-9]+K$/', $formatted_size)) {
            $return_value = mb_substr($formatted_size, 0, -1)
                * self::pow(1024, 1);
        }
        return $return_value;
    }// end of the 'extractValueFromFormattedSize' function

    /**
     * Writes localised date
     *
     * @param integer $timestamp the current timestamp
     * @param string  $format    format
     *
     * @return string   the formatted date
     *
     * @access  public
     */
    public static function localisedDate($timestamp = -1, $format = '')
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
            _pgettext('Short week day name', 'Sun'),
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
            /* l10n: See http://www.php.net/manual/en/function.strftime.php */
            $format = __('%B %d, %Y at %I:%M %p');
        }

        if ($timestamp == -1) {
            $timestamp = time();
        }

        $date = preg_replace(
            '@%[aA]@',
            $day_of_week[(int)strftime('%w', $timestamp)],
            $format
        );
        $date = preg_replace(
            '@%[bB]@',
            $month[(int)strftime('%m', $timestamp)-1],
            $date
        );

        $ret = strftime($date, $timestamp);
        // Some OSes such as Win8.1 Traditional Chinese version did not produce UTF-8
        // output here. See https://sourceforge.net/p/phpmyadmin/bugs/4207/
        if (mb_detect_encoding($ret, 'UTF-8', true) != 'UTF-8') {
            $ret = date('Y-m-d H:i:s', $timestamp);
        }

        return $ret;
    } // end of the 'localisedDate()' function

    /**
     * returns a tab for tabbed navigation.
     * If the variables $link and $args ar left empty, an inactive tab is created
     *
     * @param array $tab        array with all options
     * @param array $url_params tab specific URL parameters
     *
     * @return string  html code for one tab, a link if valid otherwise a span
     *
     * @access  public
     */
    public static function getHtmlTab($tab, $url_params = array())
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

        // determine additional style-class
        if (empty($tab['class'])) {
            if (! empty($tab['active'])
                || PMA_isValid($GLOBALS['active_page'], 'identical', $tab['link'])
            ) {
                $tab['class'] = 'active';
            } elseif (is_null($tab['active']) && empty($GLOBALS['active_page'])
                && (basename($GLOBALS['PMA_PHP_SELF']) == $tab['link'])
            ) {
                $tab['class'] = 'active';
            }
        }

        // If there are any tab specific URL parameters, merge those with
        // the general URL parameters
        if (! empty($tab['url_params']) && is_array($tab['url_params'])) {
            $url_params = array_merge($url_params, $tab['url_params']);
        }

        // build the link
        if (! empty($tab['link'])) {
            $tab['link'] = htmlentities($tab['link']);
            $tab['link'] = $tab['link'] . PMA_URL_getCommon($url_params);
            if (! empty($tab['args'])) {
                foreach ($tab['args'] as $param => $value) {
                    $tab['link'] .= PMA_URL_getArgSeparator('html')
                        . urlencode($param) . '=' . urlencode($value);
                }
            }
        }

        if (! empty($tab['fragment'])) {
            $tab['link'] .= $tab['fragment'];
        }

        // display icon
        if (isset($tab['icon'])) {
            // avoid generating an alt tag, because it only illustrates
            // the text that follows and if browser does not display
            // images, the text is duplicated
            $tab['text'] = self::getIcon(
                $tab['icon'],
                $tab['text'],
                false,
                true,
                'TabsMode'
            );

        } elseif (empty($tab['text'])) {
            // check to not display an empty link-text
            $tab['text'] = '?';
            trigger_error(
                'empty linktext in function ' . __FUNCTION__ . '()',
                E_USER_NOTICE
            );
        }

        //Set the id for the tab, if set in the params
        $tabId = (empty($tab['id']) ? null : $tab['id']);

        $item = array();
        if (!empty($tab['link'])) {
            $item = array(
                'content' => $tab['text'],
                'url' => array(
                    'href' => empty($tab['link']) ? null : $tab['link'],
                    'id' => $tabId,
                    'class' => 'tab' . htmlentities($tab['class']),
                ),
            );
        } else {
            $item['content'] = '<span class="tab' . htmlentities($tab['class']) . '"'
                . $tabId . '>' . $tab['text'] . '</span>';
        }

        $item['class'] = $tab['class'] == 'active' ? 'active' : '';

        return Template::get('list/item')
            ->render($item);
    } // end of the 'getHtmlTab()' function

    /**
     * returns html-code for a tab navigation
     *
     * @param array  $tabs       one element per tab
     * @param array  $url_params additional URL parameters
     * @param string $menu_id    HTML id attribute for the menu container
     * @param bool   $resizable  whether to add a "resizable" class
     *
     * @return string  html-code for tab-navigation
     */
    public static function getHtmlTabs(
        $tabs,
        $url_params,
        $menu_id,
        $resizable = false
    ) {
        $class = '';
        if ($resizable) {
            $class = ' class="resizable-menu"';
        }

        $tab_navigation = '<div id="' . htmlentities($menu_id)
            . 'container" class="menucontainer">'
            . '<ul id="' . htmlentities($menu_id) . '" ' . $class . '>';

        foreach ($tabs as $tab) {
            $tab_navigation .= self::getHtmlTab($tab, $url_params);
        }

        $tab_navigation .=
              '<div class="clearfloat"></div>'
            . '</ul>' . "\n"
            . '</div>' . "\n";

        return $tab_navigation;
    }

    /**
     * Displays a link, or a button if the link's URL is too large, to
     * accommodate some browsers' limitations
     *
     * @param string  $url          the URL
     * @param string  $message      the link message
     * @param mixed   $tag_params   string: js confirmation
     *                              array: additional tag params (f.e. style="")
     * @param boolean $new_form     we set this to false when we are already in
     *                              a  form, to avoid generating nested forms
     * @param boolean $strip_img    whether to strip the image
     * @param string  $target       target
     * @param boolean $force_button use a button even when the URL is not too long
     *
     * @return string  the results to be echoed or saved in an array
     */
    public static function linkOrButton(
        $url, $message, $tag_params = array(),
        $new_form = true, $strip_img = false, $target = '', $force_button = false
    ) {
        $url_length = mb_strlen($url);
        // with this we should be able to catch case of image upload
        // into a (MEDIUM) BLOB; not worth generating even a form for these
        if ($url_length > $GLOBALS['cfg']['LinkLengthLimit'] * 100) {
            return '';
        }

        if (! is_array($tag_params)) {
            $tmp = $tag_params;
            $tag_params = array();
            if (! empty($tmp)) {
                $tag_params['onclick'] = 'return confirmLink(this, \''
                    . PMA_escapeJsString($tmp) . '\')';
            }
            unset($tmp);
        }
        if (! empty($target)) {
            $tag_params['target'] = htmlentities($target);
        }

        $displayed_message = '';
        // Add text if not already added
        if (stristr($message, '<img')
            && (! $strip_img || ($GLOBALS['cfg']['ActionLinksMode'] == 'icons'))
            && (strip_tags($message) == $message)
        ) {
            $displayed_message = '<span>'
                . htmlspecialchars(
                    preg_replace('/^.*\salt="([^"]*)".*$/si', '\1', $message)
                )
                . '</span>';
        }

        // Suhosin: Check that each query parameter is not above maximum
        $in_suhosin_limits = true;
        if ($url_length <= $GLOBALS['cfg']['LinkLengthLimit']) {
            $suhosin_get_MaxValueLength = ini_get('suhosin.get.max_value_length');
            if ($suhosin_get_MaxValueLength) {
                $query_parts = self::splitURLQuery($url);
                foreach ($query_parts as $query_pair) {
                    if (strpos($query_pair, '=') === false) {
                        continue;
                    }

                    list(, $eachval) = explode('=', $query_pair);
                    if (mb_strlen($eachval) > $suhosin_get_MaxValueLength
                    ) {
                        $in_suhosin_limits = false;
                        break;
                    }
                }
            }
        }

        if (($url_length <= $GLOBALS['cfg']['LinkLengthLimit'])
            && $in_suhosin_limits
            && ! $force_button
        ) {
            $tag_params_strings = array();
            foreach ($tag_params as $par_name => $par_value) {
                // htmlspecialchars() only on non javascript
                $par_value = mb_substr($par_name, 0, 2) == 'on'
                    ? $par_value
                    : htmlspecialchars($par_value);
                $tag_params_strings[] = $par_name . '="' . $par_value . '"';
            }

            // no whitespace within an <a> else Safari will make it part of the link
            $ret = "\n" . '<a href="' . $url . '" '
                . implode(' ', $tag_params_strings) . '>'
                . $message . $displayed_message . '</a>' . "\n";
        } else {
            // no spaces (line breaks) at all
            // or after the hidden fields
            // IE will display them all

            if (! isset($query_parts)) {
                $query_parts = self::splitURLQuery($url);
            }
            $url_parts   = parse_url($url);

            if ($new_form) {
                if ($target) {
                    $target = ' target="' . $target . '"';
                }
                $ret = '<form action="' . $url_parts['path'] . '" class="link"'
                     . ' method="post"' . $target . ' style="display: inline;">';
                $subname_open   = '';
                $subname_close  = '';
                $submit_link    = '#';
            } else {
                $query_parts[] = 'redirect=' . $url_parts['path'];
                if (empty($GLOBALS['subform_counter'])) {
                    $GLOBALS['subform_counter'] = 0;
                }
                $GLOBALS['subform_counter']++;
                $ret            = '';
                $subname_open   = 'subform[' . $GLOBALS['subform_counter'] . '][';
                $subname_close  = ']';
                $submit_link    = '#usesubform[' . $GLOBALS['subform_counter']
                    . ']=1';
            }

            foreach ($query_parts as $query_pair) {
                list($eachvar, $eachval) = explode('=', $query_pair);
                $ret .= '<input type="hidden" name="' . $subname_open . $eachvar
                    . $subname_close . '" value="'
                    . htmlspecialchars(urldecode($eachval)) . '" />';
            } // end while

            if (empty($tag_params['class'])) {
                $tag_params['class'] = 'formLinkSubmit';
            } else {
                $tag_params['class'] .= ' formLinkSubmit';
            }

            $tag_params_strings = array();
            foreach ($tag_params as $par_name => $par_value) {
                // htmlspecialchars() only on non javascript
                $par_value = mb_substr($par_name, 0, 2) == 'on'
                    ? $par_value
                    : htmlspecialchars($par_value);
                $tag_params_strings[] = $par_name . '="' . $par_value . '"';
            }

            $ret .= "\n" . '<a href="' . $submit_link . '" '
                . implode(' ', $tag_params_strings) . '>'
                . $message . ' ' . $displayed_message . '</a>' . "\n";

            if ($new_form) {
                $ret .= '</form>';
            }
        } // end if... else...

        return $ret;
    } // end of the 'linkOrButton()' function

    /**
     * Splits a URL string by parameter
     *
     * @param string $url the URL
     *
     * @return array  the parameter/value pairs, for example [0] db=sakila
     */
    public static function splitURLQuery($url)
    {
        // decode encoded url separators
        $separator = PMA_URL_getArgSeparator();
        // on most places separator is still hard coded ...
        if ($separator !== '&') {
            // ... so always replace & with $separator
            $url = str_replace(htmlentities('&'), $separator, $url);
            $url = str_replace('&', $separator, $url);
        }

        $url = str_replace(htmlentities($separator), $separator, $url);
        // end decode

        $url_parts = parse_url($url);

        if (! empty($url_parts['query'])) {
            return explode($separator, $url_parts['query']);
        } else {
            return array();
        }
    }

    /**
     * Returns a given timespan value in a readable format.
     *
     * @param int $seconds the timespan
     *
     * @return string  the formatted value
     */
    public static function timespanFormat($seconds)
    {
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

        return sprintf(
            __('%s days, %s hours, %s minutes and %s seconds'),
            (string)$days,
            (string)$hours,
            (string)$minutes,
            (string)$seconds
        );
    }

    /**
     * Takes a string and outputs each character on a line for itself. Used
     * mainly for horizontalflipped display mode.
     * Takes care of special html-characters.
     * Fulfills https://sourceforge.net/p/phpmyadmin/feature-requests/164/
     *
     * @param string $string    The string
     * @param string $Separator The Separator (defaults to "<br />\n")
     *
     * @access  public
     * @todo    add a multibyte safe function $GLOBALS['String']->split()
     *
     * @return string      The flipped string
     */
    public static function flipstring($string, $Separator = "<br />\n")
    {
        $format_string = '';
        $charbuff = false;

        for ($i = 0, $str_len = mb_strlen($string);
             $i < $str_len;
             $i++
        ) {
            $char = $string{$i};
            $append = false;

            if ($char == '&') {
                $format_string .= $charbuff;
                $charbuff = $char;
            } elseif ($char == ';' && ! empty($charbuff)) {
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
     * @param string[] $params  The names of the parameters needed by the calling
     *                          script
     * @param bool     $request Whether to include this list in checking for
     *                          special params
     *
     * @return void
     *
     * @global boolean $checked_special flag whether any special variable
     *                                       was required
     *
     * @access public
     */
    public static function checkParameters($params, $request = true)
    {
        global $checked_special;

        if (! isset($checked_special)) {
            $checked_special = false;
        }

        $reported_script_name = basename($GLOBALS['PMA_PHP_SELF']);
        $found_error = false;
        $error_message = '';

        foreach ($params as $param) {
            if ($request && ($param != 'db') && ($param != 'table')) {
                $checked_special = true;
            }

            if (! isset($GLOBALS[$param])) {
                $error_message .= $reported_script_name
                    . ': ' . __('Missing parameter:') . ' '
                    . $param
                    . self::showDocu('faq', 'faqmissingparameters',true)
                    . '[br]';
                $found_error = true;
            }
        }
        if ($found_error) {
            PMA_fatalError($error_message, null, false);
        }
    } // end function

    /**
     * Function to generate unique condition for specified row.
     *
     * @param resource       $handle               current query result
     * @param integer        $fields_cnt           number of fields
     * @param array          $fields_meta          meta information about fields
     * @param array          $row                  current row
     * @param boolean        $force_unique         generate condition only on pk
     *                                             or unique
     * @param string|boolean $restrict_to_table    restrict the unique condition
     *                                             to this table or false if
     *                                             none
     * @param array          $analyzed_sql_results the analyzed query
     *
     * @access public
     *
     * @return array the calculated condition and whether condition is unique
     */
    public static function getUniqueCondition(
        $handle, $fields_cnt, $fields_meta, $row, $force_unique = false,
        $restrict_to_table = false, $analyzed_sql_results = null
    ) {
        $primary_key          = '';
        $unique_key           = '';
        $nonprimary_condition = '';
        $preferred_condition = '';
        $primary_key_array    = array();
        $unique_key_array     = array();
        $nonprimary_condition_array = array();
        $condition_array = array();

        for ($i = 0; $i < $fields_cnt; ++$i) {

            $con_val     = '';
            $field_flags = $GLOBALS['dbi']->fieldFlags($handle, $i);
            $meta        = $fields_meta[$i];

            // do not use a column alias in a condition
            if (! isset($meta->orgname) || ! mb_strlen($meta->orgname)) {
                $meta->orgname = $meta->name;

                if (!empty($analyzed_sql_results['statement']->expr)) {
                    foreach ($analyzed_sql_results['statement']->expr as $expr) {
                        if ((empty($expr->alias)) || (empty($expr->column))) {
                            continue;
                        }
                        if (strcasecmp($meta->name, $expr->alias) == 0) {
                            $meta->orgname = $expr->column;
                            break;
                        }
                    }
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
            if (isset($meta->orgtable)
                && ($meta->table != $meta->orgtable)
                && ! $GLOBALS['dbi']->getTable($GLOBALS['db'], $meta->table)->isView()
            ) {
                $meta->table = $meta->orgtable;
            }

            // If this field is not from the table which the unique clause needs
            // to be restricted to.
            if ($restrict_to_table && $restrict_to_table != $meta->table) {
                continue;
            }

            // to fix the bug where float fields (primary or not)
            // can't be matched because of the imprecision of
            // floating comparison, use CONCAT
            // (also, the syntax "CONCAT(field) IS NULL"
            // that we need on the next "if" will work)
            if ($meta->type == 'real') {
                $con_key = 'CONCAT(' . self::backquote($meta->table) . '.'
                    . self::backquote($meta->orgname) . ')';
            } else {
                $con_key = self::backquote($meta->table) . '.'
                    . self::backquote($meta->orgname);
            } // end if... else...
            $condition = ' ' . $con_key . ' ';

            if (! isset($row[$i]) || is_null($row[$i])) {
                $con_val = 'IS NULL';
            } else {
                // timestamp is numeric on some MySQL 4.1
                // for real we use CONCAT above and it should compare to string
                if ($meta->numeric
                    && ($meta->type != 'timestamp')
                    && ($meta->type != 'real')
                ) {
                    $con_val = '= ' . $row[$i];
                } elseif ((($meta->type == 'blob') || ($meta->type == 'string'))
                    && stristr($field_flags, 'BINARY')
                    && ! empty($row[$i])
                ) {
                    // hexify only if this is a true not empty BLOB or a BINARY

                    // do not waste memory building a too big condition
                    if (mb_strlen($row[$i]) < 1000) {
                        // use a CAST if possible, to avoid problems
                        // if the field contains wildcard characters % or _
                        $con_val = '= CAST(0x' . bin2hex($row[$i]) . ' AS BINARY)';
                    } elseif ($fields_cnt == 1) {
                        // when this blob is the only field present
                        // try settling with length comparison
                        $condition = ' CHAR_LENGTH(' . $con_key . ') ';
                        $con_val = ' = ' .  mb_strlen($row[$i]);
                    } else {
                        // this blob won't be part of the final condition
                        $con_val = null;
                    }
                } elseif (in_array($meta->type, self::getGISDatatypes())
                    && ! empty($row[$i])
                ) {
                    // do not build a too big condition
                    if (mb_strlen($row[$i]) < 5000) {
                        $condition .= '=0x' . bin2hex($row[$i]) . ' AND';
                    } else {
                        $condition = '';
                    }
                } elseif ($meta->type == 'bit') {
                    $con_val = "= b'"
                        . self::printableBitValue($row[$i], $meta->length) . "'";
                } else {
                    $con_val = '= \''
                        . self::sqlAddSlashes($row[$i], false, true) . '\'';
                }
            }

            if ($con_val != null) {

                $condition .= $con_val . ' AND';

                if ($meta->primary_key > 0) {
                    $primary_key .= $condition;
                    $primary_key_array[$con_key] = $con_val;
                } elseif ($meta->unique_key > 0) {
                    $unique_key  .= $condition;
                    $unique_key_array[$con_key] = $con_val;
                }

                $nonprimary_condition .= $condition;
                $nonprimary_condition_array[$con_key] = $con_val;
            }
        } // end for

        // Correction University of Virginia 19991216:
        // prefer primary or unique keys for condition,
        // but use conjunction of all values if no primary key
        $clause_is_unique = true;

        if ($primary_key) {
            $preferred_condition = $primary_key;
            $condition_array = $primary_key_array;

        } elseif ($unique_key) {
            $preferred_condition = $unique_key;
            $condition_array = $unique_key_array;

        } elseif (! $force_unique) {
            $preferred_condition = $nonprimary_condition;
            $condition_array = $nonprimary_condition_array;
            $clause_is_unique = false;
        }

        $where_clause = trim(preg_replace('|\s?AND$|', '', $preferred_condition));
        return(array($where_clause, $clause_is_unique, $condition_array));
    } // end function

    /**
     * Generate a button or image tag
     *
     * @param string $button_name  name of button element
     * @param string $button_class class of button or image element
     * @param string $image_name   name of image element
     * @param string $text         text to display
     * @param string $image        image to display
     * @param string $value        value
     *
     * @return string              html content
     *
     * @access  public
     */
    public static function getButtonOrImage(
        $button_name, $button_class, $image_name, $text, $image, $value = ''
    ) {
        if ($value == '') {
            $value = $text;
        }

        if ($GLOBALS['cfg']['ActionLinksMode'] == 'text') {
            return ' <input type="submit" name="' . $button_name . '"'
                . ' value="' . htmlspecialchars($value) . '"'
                . ' title="' . htmlspecialchars($text) . '" />' . "\n";
        }

        /* Opera has trouble with <input type="image"> */
        /* IE (before version 9) has trouble with <button> */
        if (PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER < 9) {
            return '<input type="image" name="' . $image_name
                . '" class="' . $button_class
                . '" value="' . htmlspecialchars($value)
                . '" title="' . htmlspecialchars($text)
                . '" src="' . $GLOBALS['pmaThemeImage'] . $image . '" />'
                . ($GLOBALS['cfg']['ActionLinksMode'] == 'both'
                    ? '&nbsp;' . htmlspecialchars($text)
                    : '') . "\n";
        } else {
            return '<button class="' . $button_class . '" type="submit"'
                . ' name="' . $button_name . '" value="' . htmlspecialchars($value)
                . '" title="' . htmlspecialchars($text) . '">' . "\n"
                . self::getIcon($image, $text)
                . '</button>' . "\n";
        }
    } // end function

    /**
     * Generate a pagination selector for browsing resultsets
     *
     * @param string $name        The name for the request parameter
     * @param int    $rows        Number of rows in the pagination set
     * @param int    $pageNow     current page number
     * @param int    $nbTotalPage number of total pages
     * @param int    $showAll     If the number of pages is lower than this
     *                            variable, no pages will be omitted in pagination
     * @param int    $sliceStart  How many rows at the beginning should always
     *                            be shown?
     * @param int    $sliceEnd    How many rows at the end should always be shown?
     * @param int    $percent     Percentage of calculation page offsets to hop to a
     *                            next page
     * @param int    $range       Near the current page, how many pages should
     *                            be considered "nearby" and displayed as well?
     * @param string $prompt      The prompt to display (sometimes empty)
     *
     * @return string
     *
     * @access  public
     */
    public static function pageselector(
        $name, $rows, $pageNow = 1, $nbTotalPage = 1, $showAll = 200,
        $sliceStart = 5,
        $sliceEnd = 5, $percent = 20, $range = 10, $prompt = ''
    ) {
        $increment = floor($nbTotalPage / $percent);
        $pageNowMinusRange = ($pageNow - $range);
        $pageNowPlusRange = ($pageNow + $range);

        $gotopage = $prompt . ' <select class="pageselector ajax"';

        $gotopage .= ' name="' . $name . '" >';
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

            /*
            Add page numbers with "geometrically increasing" distances.

            This helps me a lot when navigating through giant tables.

            Test case: table with 2.28 million sets, 76190 pages. Page of interest
            is between 72376 and 76190.
            Selecting page 72376.
            Now, old version enumerated only +/- 10 pages around 72376 and the
            percentage increment produced steps of about 3000.

            The following code adds page numbers +/- 2,4,8,16,32,64,128,256 etc.
            around the current page.
            */
            $i = $pageNow;
            $dist = 1;
            while ($i < $x) {
                $dist = 2 * $dist;
                $i = $pageNow + $dist;
                if ($i > 0 && $i <= $x) {
                    $pages[] = $i;
                }
            }

            $i = $pageNow;
            $dist = 1;
            while ($i > 0) {
                $dist = 2 * $dist;
                $i = $pageNow - $dist;
                if ($i > 0 && $i <= $x) {
                    $pages[] = $i;
                }
            }

            // Since because of ellipsing of the current page some numbers may be
            // double, we unify our array:
            sort($pages);
            $pages = array_unique($pages);
        }

        foreach ($pages as $i) {
            if ($i == $pageNow) {
                $selected = 'selected="selected" style="font-weight: bold"';
            } else {
                $selected = '';
            }
            $gotopage .= '                <option ' . $selected
                . ' value="' . (($i - 1) * $rows) . '">' . $i . '</option>' . "\n";
        }

        $gotopage .= ' </select>';

        return $gotopage;
    } // end function

    /**
     * Prepare navigation for a list
     *
     * @param int      $count       number of elements in the list
     * @param int      $pos         current position in the list
     * @param array    $_url_params url parameters
     * @param string   $script      script name for form target
     * @param string   $frame       target frame
     * @param int      $max_count   maximum number of elements to display from
     *                              the list
     * @param string   $name        the name for the request parameter
     * @param string[] $classes     additional classes for the container
     *
     * @return string $list_navigator_html the  html content
     *
     * @access  public
     *
     * @todo    use $pos from $_url_params
     */
    public static function getListNavigator(
        $count, $pos, $_url_params, $script, $frame, $max_count, $name = 'pos',
        $classes = array()
    ) {

        $class = $frame == 'frame_navigation' ? ' class="ajax"' : '';

        $list_navigator_html = '';

        if ($max_count < $count) {

            $classes[] = 'pageselector';
            $list_navigator_html .= '<div class="' . implode(' ', $classes) . '">';

            if ($frame != 'frame_navigation') {
                $list_navigator_html .= __('Page number:');
            }

            // Move to the beginning or to the previous page
            if ($pos > 0) {
                $caption1 = ''; $caption2 = '';
                if (self::showIcons('TableNavigationLinksMode')) {
                    $caption1 .= '&lt;&lt; ';
                    $caption2 .= '&lt; ';
                }
                if (self::showText('TableNavigationLinksMode')) {
                    $caption1 .= _pgettext('First page', 'Begin');
                    $caption2 .= _pgettext('Previous page', 'Previous');
                }
                $title1 = ' title="' . _pgettext('First page', 'Begin') . '"';
                $title2 = ' title="' . _pgettext('Previous page', 'Previous') . '"';

                $_url_params[$name] = 0;
                $list_navigator_html .= '<a' . $class . $title1 . ' href="' . $script
                    . PMA_URL_getCommon($_url_params) . '">' . $caption1
                    . '</a>';

                $_url_params[$name] = $pos - $max_count;
                $list_navigator_html .= ' <a' . $class . $title2
                    . ' href="' . $script . PMA_URL_getCommon($_url_params) . '">'
                    . $caption2 . '</a>';
            }

            $list_navigator_html .= '<form action="' . basename($script)
                . '" method="post">';

            $list_navigator_html .= PMA_URL_getHiddenInputs($_url_params);
            $list_navigator_html .= self::pageselector(
                $name,
                $max_count,
                floor(($pos + 1) / $max_count) + 1,
                ceil($count / $max_count)
            );
            $list_navigator_html .= '</form>';

            if ($pos + $max_count < $count) {
                $caption3 = ''; $caption4 = '';
                if (self::showText('TableNavigationLinksMode')) {
                    $caption3 .= _pgettext('Next page', 'Next');
                    $caption4 .= _pgettext('Last page', 'End');
                }
                if (self::showIcons('TableNavigationLinksMode')) {
                    $caption3 .= ' &gt;';
                    $caption4 .= ' &gt;&gt;';
                    if (! self::showText('TableNavigationLinksMode')) {

                    }
                }
                $title3 = ' title="' . _pgettext('Next page', 'Next') . '"';
                $title4 = ' title="' . _pgettext('Last page', 'End') . '"';

                $_url_params[$name] = $pos + $max_count;
                $list_navigator_html .= '<a' . $class . $title3 . ' href="' . $script
                    . PMA_URL_getCommon($_url_params) . '" >' . $caption3
                    . '</a>';

                $_url_params[$name] = floor($count / $max_count) * $max_count;
                if ($_url_params[$name] == $count) {
                    $_url_params[$name] = $count - $max_count;
                }

                $list_navigator_html .= ' <a' . $class . $title4
                    . ' href="' . $script . PMA_URL_getCommon($_url_params) . '" >'
                    . $caption4 . '</a>';
            }
            $list_navigator_html .= '</div>' . "\n";
        }

        return $list_navigator_html;
    }

    /**
     * replaces %u in given path with current user name
     *
     * example:
     * <code>
     * $user_dir = userDir('/var/pma_tmp/%u/'); // '/var/pma_tmp/root/'
     *
     * </code>
     *
     * @param string $dir with wildcard for user
     *
     * @return string  per user directory
     */
    public static function userDir($dir)
    {
        // add trailing slash
        if (mb_substr($dir, -1) != '/') {
            $dir .= '/';
        }

        return str_replace('%u', $GLOBALS['cfg']['Server']['user'], $dir);
    }

    /**
     * returns html code for db link to default db page
     *
     * @param string $database database
     *
     * @return string  html link to default db page
     */
    public static function getDbLink($database = null)
    {
        if (! mb_strlen($database)) {
            if (! mb_strlen($GLOBALS['db'])) {
                return '';
            }
            $database = $GLOBALS['db'];
        } else {
            $database = self::unescapeMysqlWildcards($database);
        }

        return '<a href="'
            . Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabDatabase'], 'database'
            )
            . PMA_URL_getCommon(array('db' => $database)) . '" title="'
            . htmlspecialchars(
                sprintf(
                    __('Jump to database "%s".'),
                    $database
                )
            )
            . '">' . htmlspecialchars($database) . '</a>';
    }

    /**
     * Prepare a lightbulb hint explaining a known external bug
     * that affects a functionality
     *
     * @param string $functionality   localized message explaining the func.
     * @param string $component       'mysql' (eventually, 'php')
     * @param string $minimum_version of this component
     * @param string $bugref          bug reference for this component
     *
     * @return String
     */
    public static function getExternalBug(
        $functionality, $component, $minimum_version, $bugref
    ) {
        $ext_but_html = '';
        if (($component == 'mysql') && (PMA_MYSQL_INT_VERSION < $minimum_version)) {
            $ext_but_html .= self::showHint(
                sprintf(
                    __('The %s functionality is affected by a known bug, see %s'),
                    $functionality,
                    PMA_linkURL('https://bugs.mysql.com/') . $bugref
                )
            );
        }
        return $ext_but_html;
    }

    /**
     * Returns a HTML checkbox
     *
     * @param string  $html_field_name the checkbox HTML field
     * @param string  $label           label for checkbox
     * @param boolean $checked         is it initially checked?
     * @param boolean $onclick         should it submit the form on click?
     * @param string  $html_field_id   id for the checkbox
     *
     * @return string                  HTML for the checkbox
     */
    public static function getCheckbox(
        $html_field_name, $label, $checked, $onclick, $html_field_id = ''
    ) {
        return '<input type="checkbox" name="' . $html_field_name . '"'
            . ($html_field_id ? ' id="' . $html_field_id . '"' : '')
            . ($checked ? ' checked="checked"' : '')
            . ($onclick ? ' class="autosubmit"' : '') . ' />'
            . '<label' . ($html_field_id ? ' for="' . $html_field_id . '"' : '')
            . '>' . $label . '</label>';
    }

    /**
     * Generates a set of radio HTML fields
     *
     * @param string  $html_field_name the radio HTML field
     * @param array   $choices         the choices values and labels
     * @param string  $checked_choice  the choice to check by default
     * @param boolean $line_break      whether to add HTML line break after a choice
     * @param boolean $escape_label    whether to use htmlspecialchars() on label
     * @param string  $class           enclose each choice with a div of this class
     * @param string  $id_prefix       prefix for the id attribute, name will be
     *                                 used if this is not supplied
     *
     * @return string                  set of html radio fiels
     */
    public static function getRadioFields(
        $html_field_name, $choices, $checked_choice = '',
        $line_break = true, $escape_label = true, $class = '',
        $id_prefix = ''
    ) {
        $radio_html = '';

        foreach ($choices as $choice_value => $choice_label) {

            if (! empty($class)) {
                $radio_html .= '<div class="' . $class . '">';
            }

            if (! $id_prefix) {
                $id_prefix = $html_field_name;
            }
            $html_field_id = $id_prefix . '_' . $choice_value;
            $radio_html .= '<input type="radio" name="' . $html_field_name . '" id="'
                        . $html_field_id . '" value="'
                        . htmlspecialchars($choice_value) . '"';

            if ($choice_value == $checked_choice) {
                $radio_html .= ' checked="checked"';
            }

            $radio_html .= ' />' . "\n"
                        . '<label for="' . $html_field_id . '">'
                        . ($escape_label
                        ? htmlspecialchars($choice_label)
                        : $choice_label)
                        . '</label>';

            if ($line_break) {
                $radio_html .= '<br />';
            }

            if (! empty($class)) {
                $radio_html .= '</div>';
            }
            $radio_html .= "\n";
        }

        return $radio_html;
    }

    /**
     * Generates and returns an HTML dropdown
     *
     * @param string $select_name   name for the select element
     * @param array  $choices       choices values
     * @param string $active_choice the choice to select by default
     * @param string $id            id of the select element; can be different in
     *                              case the dropdown is present more than once
     *                              on the page
     * @param string $class         class for the select element
     * @param string $placeholder   Placeholder for dropdown if nothing else
     *                              is selected
     *
     * @return string               html content
     *
     * @todo    support titles
     */
    public static function getDropdown(
        $select_name, $choices, $active_choice, $id, $class = '', $placeholder = null
    ) {
        $result = '<select'
            . ' name="' . htmlspecialchars($select_name) . '"'
            . ' id="' . htmlspecialchars($id) . '"'
            . (! empty($class) ? ' class="' . htmlspecialchars($class) . '"' : '')
            . '>';

        $resultOptions = '';
        $selected = false;

        foreach ($choices as $one_choice_value => $one_choice_label) {
            $resultOptions .= '<option value="'
                . htmlspecialchars($one_choice_value) . '"';

            if ($one_choice_value == $active_choice) {
                $resultOptions .= ' selected="selected"';
                $selected = true;
            }
            $resultOptions .= '>' . htmlspecialchars($one_choice_label)
                . '</option>';
        }

        if (!empty($placeholder)) {
            $resultOptions = '<option value="" disabled="disabled"'
                . ( !$selected ? ' selected="selected"' : '' )
                . '>' . $placeholder . '</option>'
                . $resultOptions;
        }

        $result .= $resultOptions
            . '</select>';

        return $result;
    }

    /**
     * Generates a slider effect (jQjuery)
     * Takes care of generating the initial <div> and the link
     * controlling the slider; you have to generate the </div> yourself
     * after the sliding section.
     *
     * @param string $id      the id of the <div> on which to apply the effect
     * @param string $message the message to show as a link
     *
     * @return string         html div element
     *
     */
    public static function getDivForSliderEffect($id = '', $message = '')
    {
        if ($GLOBALS['cfg']['InitialSlidersState'] == 'disabled') {
            return '<div' . ($id ? ' id="' . $id . '"' : '') . '>';
        }
        /**
         * Bad hack on the next line. document.write() conflicts with jQuery,
         * hence, opening the <div> with PHP itself instead of JavaScript.
         *
         * @todo find a better solution that uses $.append(), the recommended
         * method maybe by using an additional param, the id of the div to
         * append to
         */

        return '<div'
             . ($id ? ' id="' . $id . '"' : '')
            . (($GLOBALS['cfg']['InitialSlidersState'] == 'closed')
                ? ' style="display: none; overflow:auto;"'
                : '')
            . ' class="pma_auto_slider"'
            . ($message ? ' title="' . htmlspecialchars($message) . '"' : '')
            . '>';
    }

    /**
     * Creates an AJAX sliding toggle button
     * (or and equivalent form when AJAX is disabled)
     *
     * @param string $action      The URL for the request to be executed
     * @param string $select_name The name for the dropdown box
     * @param array  $options     An array of options (see rte_footer.lib.php)
     * @param string $callback    A JS snippet to execute when the request is
     *                            successfully processed
     *
     * @return string   HTML code for the toggle button
     */
    public static function toggleButton($action, $select_name, $options, $callback)
    {
        // Do the logic first
        $link = "$action&amp;" . urlencode($select_name) . "=";
        $link_on = $link . urlencode($options[1]['value']);
        $link_off = $link . urlencode($options[0]['value']);

        if ($options[1]['selected'] == true) {
            $state = 'on';
        } else if ($options[0]['selected'] == true) {
            $state = 'off';
        } else {
            $state = 'on';
        }

        // Generate output
        return "<!-- TOGGLE START -->\n"
            . "<div class='wrapper toggleAjax hide'>\n"
            . "    <div class='toggleButton'>\n"
            . "        <div title='" . __('Click to toggle')
            . "' class='container $state'>\n"
            . "           <img src='" . htmlspecialchars($GLOBALS['pmaThemeImage'])
            . "toggle-" . htmlspecialchars($GLOBALS['text_dir']) . ".png'\n"
            . "                 alt='' />\n"
            . "            <table class='nospacing nopadding'>\n"
            . "                <tbody>\n"
            . "                <tr>\n"
            . "                <td class='toggleOn'>\n"
            . "                    <span class='hide'>$link_on</span>\n"
            . "                    <div>"
            . str_replace(' ', '&nbsp;', htmlspecialchars($options[1]['label']))
            . "\n" . "                    </div>\n"
            . "                </td>\n"
            . "                <td><div>&nbsp;</div></td>\n"
            . "                <td class='toggleOff'>\n"
            . "                    <span class='hide'>$link_off</span>\n"
            . "                    <div>"
            . str_replace(' ', '&nbsp;', htmlspecialchars($options[0]['label']))
            . "\n" . "                    </div>\n"
            . "                </tr>\n"
            . "                </tbody>\n"
            . "            </table>\n"
            . "            <span class='hide callback'>"
            . htmlspecialchars($callback) . "</span>\n"
            . "            <span class='hide text_direction'>"
            . htmlspecialchars($GLOBALS['text_dir']) . "</span>\n"
            . "        </div>\n"
            . "    </div>\n"
            . "</div>\n"
            . "<!-- TOGGLE END -->";

    } // end toggleButton()

    /**
     * Clears cache content which needs to be refreshed on user change.
     *
     * @return void
     */
    public static function clearUserCache()
    {
        self::cacheUnset('is_superuser');
        self::cacheUnset('is_createuser');
        self::cacheUnset('is_grantuser');
    }

    /**
     * Verifies if something is cached in the session
     *
     * @param string $var variable name
     *
     * @return boolean
     */
    public static function cacheExists($var)
    {
        return isset($_SESSION['cache']['server_' . $GLOBALS['server']][$var]);
    }

    /**
     * Gets cached information from the session
     *
     * @param string   $var      variable name
     * @param \Closure $callback callback to fetch the value
     *
     * @return mixed
     */
    public static function cacheGet($var, $callback = null)
    {
        if (self::cacheExists($var)) {
            return $_SESSION['cache']['server_' . $GLOBALS['server']][$var];
        } else {
            if ($callback) {
                $val = $callback();
                self::cacheSet($var, $val);
                return $val;
            }
            return null;
        }
    }

    /**
     * Caches information in the session
     *
     * @param string $var variable name
     * @param mixed  $val value
     *
     * @return mixed
     */
    public static function cacheSet($var, $val = null)
    {
        $_SESSION['cache']['server_' . $GLOBALS['server']][$var] = $val;
    }

    /**
     * Removes cached information from the session
     *
     * @param string $var variable name
     *
     * @return void
     */
    public static function cacheUnset($var)
    {
        unset($_SESSION['cache']['server_' . $GLOBALS['server']][$var]);
    }

    /**
     * Converts a bit value to printable format;
     * in MySQL a BIT field can be from 1 to 64 bits so we need this
     * function because in PHP, decbin() supports only 32 bits
     * on 32-bit servers
     *
     * @param integer $value  coming from a BIT field
     * @param integer $length length
     *
     * @return string  the printable value
     */
    public static function printableBitValue($value, $length)
    {
        // if running on a 64-bit server or the length is safe for decbin()
        if (PHP_INT_SIZE == 8 || $length < 33) {
            $printable = decbin($value);
        } else {
            // FIXME: does not work for the leftmost bit of a 64-bit value
            $i = 0;
            $printable = '';
            while ($value >= pow(2, $i)) {
                ++$i;
            }
            if ($i != 0) {
                --$i;
            }

            while ($i >= 0) {
                if ($value - pow(2, $i) < 0) {
                    $printable = '0' . $printable;
                } else {
                    $printable = '1' . $printable;
                    $value = $value - pow(2, $i);
                }
                --$i;
            }
            $printable = strrev($printable);
        }
        $printable = str_pad($printable, $length, '0', STR_PAD_LEFT);
        return $printable;
    }

    /**
     * Verifies whether the value contains a non-printable character
     *
     * @param string $value value
     *
     * @return integer
     */
    public static function containsNonPrintableAscii($value)
    {
        return preg_match('@[^[:print:]]@', $value);
    }

    /**
     * Converts a BIT type default value
     * for example, b'010' becomes 010
     *
     * @param string $bit_default_value value
     *
     * @return string the converted value
     */
    public static function convertBitDefaultValue($bit_default_value)
    {
        return rtrim(ltrim($bit_default_value, "b'"), "'");
    }

    /**
     * Extracts the various parts from a column spec
     *
     * @param string $columnspec Column specification
     *
     * @return array associative array containing type, spec_in_brackets
     *          and possibly enum_set_values (another array)
     */
    public static function extractColumnSpec($columnspec)
    {
        $first_bracket_pos = mb_strpos($columnspec, '(');
        if ($first_bracket_pos) {
            $spec_in_brackets = chop(
                mb_substr(
                    $columnspec,
                    $first_bracket_pos + 1,
                    mb_strrpos($columnspec, ')') - $first_bracket_pos - 1
                )
            );
            // convert to lowercase just to be sure
            $type = mb_strtolower(
                chop(mb_substr($columnspec, 0, $first_bracket_pos))
            );
        } else {
            // Split trailing attributes such as unsigned,
            // binary, zerofill and get data type name
            $type_parts = explode(' ', $columnspec);
            $type = mb_strtolower($type_parts[0]);
            $spec_in_brackets = '';
        }

        if ('enum' == $type || 'set' == $type) {
            // Define our working vars
            $enum_set_values = self::parseEnumSetValues($columnspec, false);
            $printtype = $type
                . '(' .  str_replace("','", "', '", $spec_in_brackets) . ')';
            $binary = false;
            $unsigned = false;
            $zerofill = false;
        } else {
            $enum_set_values = array();

            /* Create printable type name */
            $printtype = mb_strtolower($columnspec);

            // Strip the "BINARY" attribute, except if we find "BINARY(" because
            // this would be a BINARY or VARBINARY column type;
            // by the way, a BLOB should not show the BINARY attribute
            // because this is not accepted in MySQL syntax.
            if (preg_match('@binary@', $printtype)
                && ! preg_match('@binary[\(]@', $printtype)
            ) {
                $printtype = preg_replace('@binary@', '', $printtype);
                $binary = true;
            } else {
                $binary = false;
            }

            $printtype = preg_replace(
                '@zerofill@', '', $printtype, -1, $zerofill_cnt
            );
            $zerofill = ($zerofill_cnt > 0);
            $printtype = preg_replace(
                '@unsigned@', '', $printtype, -1, $unsigned_cnt
            );
            $unsigned = ($unsigned_cnt > 0);
            $printtype = trim($printtype);
        }

        $attribute     = ' ';
        if ($binary) {
            $attribute = 'BINARY';
        }
        if ($unsigned) {
            $attribute = 'UNSIGNED';
        }
        if ($zerofill) {
            $attribute = 'UNSIGNED ZEROFILL';
        }

        $can_contain_collation = false;
        if (! $binary
            && preg_match(
                "@^(char|varchar|text|tinytext|mediumtext|longtext|set|enum)@", $type
            )
        ) {
            $can_contain_collation = true;
        }

        // for the case ENUM('&#8211;','&ldquo;')
        $displayed_type = htmlspecialchars($printtype);
        if (mb_strlen($printtype) > $GLOBALS['cfg']['LimitChars']) {
            $displayed_type  = '<abbr title="' . htmlspecialchars($printtype) . '">';
            $displayed_type .= htmlspecialchars(
                mb_substr(
                    $printtype, 0, $GLOBALS['cfg']['LimitChars']
                ) . '...'
            );
            $displayed_type .= '</abbr>';
        }

        return array(
            'type' => $type,
            'spec_in_brackets' => $spec_in_brackets,
            'enum_set_values'  => $enum_set_values,
            'print_type' => $printtype,
            'binary' => $binary,
            'unsigned' => $unsigned,
            'zerofill' => $zerofill,
            'attribute' => $attribute,
            'can_contain_collation' => $can_contain_collation,
            'displayed_type' => $displayed_type
        );
    }

    /**
     * Verifies if this table's engine supports foreign keys
     *
     * @param string $engine engine
     *
     * @return boolean
     */
    public static function isForeignKeySupported($engine)
    {
        $engine = strtoupper($engine);
        if (($engine == 'INNODB') || ($engine == 'PBXT')) {
            return true;
        } elseif ($engine == 'NDBCLUSTER' || $engine == 'NDB') {
            $ndbver = strtolower(
                $GLOBALS['dbi']->fetchValue("SELECT @@ndb_version_string")
            );
            if (substr($ndbver, 0, 4) == 'ndb-') {
                $ndbver = substr($ndbver, 4);
            }
            return version_compare($ndbver, 7.3, '>=');
        } else {
            return false;
        }
    }

    /**
     * Is Foreign key check enabled?
     *
     * @return bool
     */
    public static function isForeignKeyCheck()
    {
        if ($GLOBALS['cfg']['DefaultForeignKeyChecks'] === 'enable') {
            return true;
        } else if ($GLOBALS['cfg']['DefaultForeignKeyChecks'] === 'disable') {
            return false;
        }
        return ($GLOBALS['dbi']->getVariable('FOREIGN_KEY_CHECKS') == 'ON');
    }

    /**
    * Get HTML for Foreign key check checkbox
    *
    * @return string HTML for checkbox
    */
    public static function getFKCheckbox()
    {
        $checked = self::isForeignKeyCheck();
        $html = '<input type="hidden" name="fk_checks" value="0" />';
        $html .= '<input type="checkbox" name="fk_checks"'
            . ' id="fk_checks" value="1"'
            . ($checked ? ' checked="checked"' : '') . '/>';
        $html .= '<label for="fk_checks">' . __('Enable foreign key checks')
            . '</label>';
        return $html;
    }

    /**
     * Handle foreign key check request
     *
     * @return bool Default foreign key checks value
     */
    public static function handleDisableFKCheckInit()
    {
        $default_fk_check_value
            = $GLOBALS['dbi']->getVariable('FOREIGN_KEY_CHECKS') == 'ON';
        if (isset($_REQUEST['fk_checks'])) {
            if (empty($_REQUEST['fk_checks'])) {
                // Disable foreign key checks
                $GLOBALS['dbi']->setVariable('FOREIGN_KEY_CHECKS', 'OFF');
            } else {
                // Enable foreign key checks
                $GLOBALS['dbi']->setVariable('FOREIGN_KEY_CHECKS', 'ON');
            }
        } // else do nothing, go with default
        return $default_fk_check_value;
    }

    /**
     * Cleanup changes done for foreign key check
     *
     * @param bool $default_fk_check_value original value for 'FOREIGN_KEY_CHECKS'
     *
     * @return void
     */
    public static function handleDisableFKCheckCleanup($default_fk_check_value)
    {
        $GLOBALS['dbi']->setVariable(
            'FOREIGN_KEY_CHECKS', $default_fk_check_value ? 'ON' : 'OFF'
        );
    }

    /**
     * Replaces some characters by a displayable equivalent
     *
     * @param string $content content
     *
     * @return string the content with characters replaced
     */
    public static function replaceBinaryContents($content)
    {
        $result = str_replace("\x00", '\0', $content);
        $result = str_replace("\x08", '\b', $result);
        $result = str_replace("\x0a", '\n', $result);
        $result = str_replace("\x0d", '\r', $result);
        $result = str_replace("\x1a", '\Z', $result);
        return $result;
    }

    /**
     * Converts GIS data to Well Known Text format
     *
     * @param string $data        GIS data
     * @param bool   $includeSRID Add SRID to the WKT
     *
     * @return string GIS data in Well Know Text format
     */
    public static function asWKT($data, $includeSRID = false)
    {
        // Convert to WKT format
        $hex = bin2hex($data);
        $wktsql     = "SELECT ASTEXT(x'" . $hex . "')";
        if ($includeSRID) {
            $wktsql .= ", SRID(x'" . $hex . "')";
        }

        $wktresult  = $GLOBALS['dbi']->tryQuery(
            $wktsql, null, DatabaseInterface::QUERY_STORE
        );
        $wktarr     = $GLOBALS['dbi']->fetchRow($wktresult, 0);
        $wktval     = $wktarr[0];

        if ($includeSRID) {
            $srid = $wktarr[1];
            $wktval = "'" . $wktval . "'," . $srid;
        }
        @$GLOBALS['dbi']->freeResult($wktresult);

        return $wktval;
    }

    /**
     * If the string starts with a \r\n pair (0x0d0a) add an extra \n
     *
     * @param string $string string
     *
     * @return string with the chars replaced
     */
    public static function duplicateFirstNewline($string)
    {
        $first_occurence = mb_strpos($string, "\r\n");
        if ($first_occurence === 0) {
            $string = "\n" . $string;
        }
        return $string;
    }

    /**
     * Get the action word corresponding to a script name
     * in order to display it as a title in navigation panel
     *
     * @param string $target a valid value for $cfg['NavigationTreeDefaultTabTable'],
     *                       $cfg['NavigationTreeDefaultTabTable2'],
     *                       $cfg['DefaultTabTable'] or $cfg['DefaultTabDatabase']
     *
     * @return string Title for the $cfg value
     */
    public static function getTitleForTarget($target)
    {
        $mapping = array(
            'structure' =>  __('Structure'),
            'sql' => __('SQL'),
            'search' =>__('Search'),
            'insert' =>__('Insert'),
            'browse' => __('Browse'),
            'operations' => __('Operations'),

            // For backward compatiblity

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
        return isset($mapping[$target]) ? $mapping[$target] : false;
    }

    /**
     * Get the script name corresponding to a plain English config word
     * in order to append in links on navigation and main panel
     *
     * @param string $target   a valid value for
     *                         $cfg['NavigationTreeDefaultTabTable'],
     *                         $cfg['NavigationTreeDefaultTabTable2'],
     *                         $cfg['DefaultTabTable'], $cfg['DefaultTabDatabase'] or
     *                         $cfg['DefaultTabServer']
     * @param string $location one out of 'server', 'table', 'database'
     *
     * @return string script name corresponding to the config word
     */
    public static function getScriptNameForOption($target, $location)
    {
        if ($location == 'server') {
            // Values for $cfg['DefaultTabServer']
            switch ($target) {
            case 'welcome':
                return 'index.php';
            case 'databases':
                return 'server_databases.php';
            case 'status':
                return 'server_status.php';
            case 'variables':
                return 'server_variables.php';
            case 'privileges':
                return 'server_privileges.php';
            }
        } elseif ($location == 'database') {
            // Values for $cfg['DefaultTabDatabase']
            switch ($target) {
            case 'structure':
                return 'db_structure.php';
            case 'sql':
                return 'db_sql.php';
            case 'search':
                return 'db_search.php';
            case 'operations':
                return 'db_operations.php';
            }
        } elseif ($location == 'table') {
            // Values for $cfg['DefaultTabTable'],
            // $cfg['NavigationTreeDefaultTabTable'] and
            // $cfg['NavigationTreeDefaultTabTable2']
            switch ($target) {
            case 'structure':
                return 'tbl_structure.php';
            case 'sql':
                return 'tbl_sql.php';
            case 'search':
                return 'tbl_select.php';
            case 'insert':
                return 'tbl_change.php';
            case 'browse':
                return 'sql.php';
            }
        }

        return $target;
    }

    /**
     * Formats user string, expanding @VARIABLES@, accepting strftime format
     * string.
     *
     * @param string       $string  Text where to do expansion.
     * @param array|string $escape  Function to call for escaping variable values.
     *                          Can also be an array of:
     *                          - the escape method name
     *                          - the class that contains the method
     *                          - location of the class (for inclusion)
     * @param array        $updates Array with overrides for default parameters
     *                     (obtained from GLOBALS).
     *
     * @return string
     */
    public static function expandUserString(
        $string, $escape = null, $updates = array()
    ) {
        /* Content */
        $vars = array();
        $vars['http_host'] = PMA_getenv('HTTP_HOST');
        $vars['server_name'] = $GLOBALS['cfg']['Server']['host'];
        $vars['server_verbose'] = $GLOBALS['cfg']['Server']['verbose'];

        if (empty($GLOBALS['cfg']['Server']['verbose'])) {
            $vars['server_verbose_or_name'] = $GLOBALS['cfg']['Server']['host'];
        } else {
            $vars['server_verbose_or_name'] = $GLOBALS['cfg']['Server']['verbose'];
        }

        $vars['database'] = $GLOBALS['db'];
        $vars['table'] = $GLOBALS['table'];
        $vars['phpmyadmin_version'] = 'phpMyAdmin ' . PMA_VERSION;

        /* Update forced variables */
        foreach ($updates as $key => $val) {
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
        if (! is_null($escape)) {
            if (is_array($escape)) {
                $escape_class = new $escape[1];
                $escape_method = $escape[0];
            }
            foreach ($replace as $key => $val) {
                if (is_array($escape)) {
                    $replace[$key] = $escape_class->$escape_method($val);
                } else {
                    $replace[$key] = ($escape == 'backquote')
                        ? self::$escape($val)
                        : $escape($val);
                }
            }
        }

        /* Backward compatibility in 3.5.x */
        if (mb_strpos($string, '@FIELDS@') !== false) {
            $string = strtr($string, array('@FIELDS@' => '@COLUMNS@'));
        }

        /* Fetch columns list if required */
        if (mb_strpos($string, '@COLUMNS@') !== false) {
            $columns_list = $GLOBALS['dbi']->getColumns(
                $GLOBALS['db'], $GLOBALS['table']
            );

            // sometimes the table no longer exists at this point
            if (! is_null($columns_list)) {
                $column_names = array();
                foreach ($columns_list as $column) {
                    if (! is_null($escape)) {
                        $column_names[] = self::$escape($column['Field']);
                    } else {
                        $column_names[] = $column['Field'];
                    }
                }
                $replace['@COLUMNS@'] = implode(',', $column_names);
            } else {
                $replace['@COLUMNS@'] = '*';
            }
        }

        /* Do the replacement */
        return strtr(strftime($string), $replace);
    }

    /**
     * Prepare the form used to browse anywhere on the local server for a file to
     * import
     *
     * @param string $max_upload_size maximum upload size
     *
     * @return String
     */
    public static function getBrowseUploadFileBlock($max_upload_size)
    {
        $block_html = '';

        if ($GLOBALS['is_upload'] && ! empty($GLOBALS['cfg']['UploadDir'])) {
            $block_html .= '<label for="radio_import_file">';
        } else {
            $block_html .= '<label for="input_import_file">';
        }

        $block_html .= __("Browse your computer:") . '</label>'
            . '<div id="upload_form_status" style="display: none;"></div>'
            . '<div id="upload_form_status_info" style="display: none;"></div>'
            . '<input type="file" name="import_file" id="input_import_file" />'
            . self::getFormattedMaximumUploadSize($max_upload_size) . "\n"
            // some browsers should respect this :)
            . self::generateHiddenMaxFileSize($max_upload_size) . "\n";

        return $block_html;
    }

    /**
     * Prepare the form used to select a file to import from the server upload
     * directory
     *
     * @param ImportPlugin[] $import_list array of import plugins
     * @param string         $uploaddir   upload directory
     *
     * @return String
     */
    public static function getSelectUploadFileBlock($import_list, $uploaddir)
    {
        $block_html = '';
        $block_html .= '<label for="radio_local_import_file">'
            . sprintf(
                __("Select from the web server upload directory <b>%s</b>:"),
                htmlspecialchars(self::userDir($uploaddir))
            )
            . '</label>';

        $extensions = '';
        foreach ($import_list as $import_plugin) {
            if (! empty($extensions)) {
                $extensions .= '|';
            }
            $extensions .= $import_plugin->getProperties()->getExtension();
        }

        $matcher = '@\.(' . $extensions . ')(\.('
            . PMA_supportedDecompressions() . '))?$@';

        $active = (isset($GLOBALS['timeout_passed']) && $GLOBALS['timeout_passed']
            && isset($GLOBALS['local_import_file']))
            ? $GLOBALS['local_import_file']
            : '';

        $files = PMA_getFileSelectOptions(
            self::userDir($uploaddir),
            $matcher,
            $active
        );

        if ($files === false) {
            Message::error(
                __('The directory you set for upload work cannot be reached.')
            )->display();
        } elseif (! empty($files)) {
            $block_html .= "\n"
                . '    <select style="margin: 5px" size="1" '
                . 'name="local_import_file" '
                . 'id="select_local_import_file">' . "\n"
                . '        <option value="">&nbsp;</option>' . "\n"
                . $files
                . '    </select>' . "\n";
        } elseif (empty($files)) {
            $block_html .= '<i>' . __('There are no files to upload!') . '</i>';
        }

        return $block_html;

    }

    /**
     * Build titles and icons for action links
     *
     * @return array   the action titles
     */
    public static function buildActionTitles()
    {
        $titles = array();

        $titles['Browse']     = self::getIcon('b_browse.png', __('Browse'));
        $titles['NoBrowse']   = self::getIcon('bd_browse.png', __('Browse'));
        $titles['Search']     = self::getIcon('b_select.png', __('Search'));
        $titles['NoSearch']   = self::getIcon('bd_select.png', __('Search'));
        $titles['Insert']     = self::getIcon('b_insrow.png', __('Insert'));
        $titles['NoInsert']   = self::getIcon('bd_insrow.png', __('Insert'));
        $titles['Structure']  = self::getIcon('b_props.png', __('Structure'));
        $titles['Drop']       = self::getIcon('b_drop.png', __('Drop'));
        $titles['NoDrop']     = self::getIcon('bd_drop.png', __('Drop'));
        $titles['Empty']      = self::getIcon('b_empty.png', __('Empty'));
        $titles['NoEmpty']    = self::getIcon('bd_empty.png', __('Empty'));
        $titles['Edit']       = self::getIcon('b_edit.png', __('Edit'));
        $titles['NoEdit']     = self::getIcon('bd_edit.png', __('Edit'));
        $titles['Export']     = self::getIcon('b_export.png', __('Export'));
        $titles['NoExport']   = self::getIcon('bd_export.png', __('Export'));
        $titles['Execute']    = self::getIcon('b_nextpage.png', __('Execute'));
        $titles['NoExecute']  = self::getIcon('bd_nextpage.png', __('Execute'));
        // For Favorite/NoFavorite, we need icon only.
        $titles['Favorite']  = self::getIcon('b_favorite.png', '');
        $titles['NoFavorite']= self::getIcon('b_no_favorite.png', '');

        return $titles;
    }

    /**
     * This function processes the datatypes supported by the DB,
     * as specified in Types->getColumns() and either returns an array
     * (useful for quickly checking if a datatype is supported)
     * or an HTML snippet that creates a drop-down list.
     *
     * @param bool   $html     Whether to generate an html snippet or an array
     * @param string $selected The value to mark as selected in HTML mode
     *
     * @return mixed   An HTML snippet or an array of datatypes.
     *
     */
    public static function getSupportedDatatypes($html = false, $selected = '')
    {
        if ($html) {

            // NOTE: the SELECT tag in not included in this snippet.
            $retval = '';

            foreach ($GLOBALS['PMA_Types']->getColumns() as $key => $value) {
                if (is_array($value)) {
                    $retval .= "<optgroup label='" . htmlspecialchars($key) . "'>";
                    foreach ($value as $subvalue) {
                        if ($subvalue == $selected) {
                            $retval .= sprintf(
                                '<option selected="selected" title="%s">%s</option>',
                                $GLOBALS['PMA_Types']->getTypeDescription($subvalue),
                                $subvalue
                            );
                        } else if ($subvalue === '-') {
                            $retval .= '<option disabled="disabled">';
                            $retval .= $subvalue;
                            $retval .= '</option>';
                        } else {
                            $retval .= sprintf(
                                '<option title="%s">%s</option>',
                                $GLOBALS['PMA_Types']->getTypeDescription($subvalue),
                                $subvalue
                            );
                        }
                    }
                    $retval .= '</optgroup>';
                } else {
                    if ($selected == $value) {
                        $retval .= sprintf(
                            '<option selected="selected" title="%s">%s</option>',
                            $GLOBALS['PMA_Types']->getTypeDescription($value),
                            $value
                        );
                    } else {
                        $retval .= sprintf(
                            '<option title="%s">%s</option>',
                            $GLOBALS['PMA_Types']->getTypeDescription($value),
                            $value
                        );
                    }
                }
            }
        } else {
            $retval = array();
            foreach ($GLOBALS['PMA_Types']->getColumns() as $value) {
                if (is_array($value)) {
                    foreach ($value as $subvalue) {
                        if ($subvalue !== '-') {
                            $retval[] = $subvalue;
                        }
                    }
                } else {
                    if ($value !== '-') {
                        $retval[] = $value;
                    }
                }
            }
        }

        return $retval;
    } // end getSupportedDatatypes()

    /**
     * Returns a list of datatypes that are not (yet) handled by PMA.
     * Used by: tbl_change.php and libraries/db_routines.inc.php
     *
     * @return array   list of datatypes
     */
    public static function unsupportedDatatypes()
    {
        $no_support_types = array();
        return $no_support_types;
    }

    /**
     * Return GIS data types
     *
     * @param bool $upper_case whether to return values in upper case
     *
     * @return string[] GIS data types
     */
    public static function getGISDatatypes($upper_case = false)
    {
        $gis_data_types = array(
            'geometry',
            'point',
            'linestring',
            'polygon',
            'multipoint',
            'multilinestring',
            'multipolygon',
            'geometrycollection'
        );
        if ($upper_case) {
            for ($i = 0, $nb = count($gis_data_types); $i < $nb; $i++) {
                $gis_data_types[$i]
                    = mb_strtoupper($gis_data_types[$i]);
            }
        }
        return $gis_data_types;
    }

    /**
     * Generates GIS data based on the string passed.
     *
     * @param string $gis_string GIS string
     *
     * @return string GIS data enclosed in 'GeomFromText' function
     */
    public static function createGISData($gis_string)
    {
        $gis_string = trim($gis_string);
        $geom_types = '(POINT|MULTIPOINT|LINESTRING|MULTILINESTRING|'
            . 'POLYGON|MULTIPOLYGON|GEOMETRYCOLLECTION)';
        if (preg_match("/^'" . $geom_types . "\(.*\)',[0-9]*$/i", $gis_string)) {
            return 'GeomFromText(' . $gis_string . ')';
        } elseif (preg_match("/^" . $geom_types . "\(.*\)$/i", $gis_string)) {
            return "GeomFromText('" . $gis_string . "')";
        } else {
            return $gis_string;
        }
    }

    /**
     * Returns the names and details of the functions
     * that can be applied on geometry data types.
     *
     * @param string $geom_type if provided the output is limited to the functions
     *                          that are applicable to the provided geometry type.
     * @param bool   $binary    if set to false functions that take two geometries
     *                          as arguments will not be included.
     * @param bool   $display   if set to true separators will be added to the
     *                          output array.
     *
     * @return array names and details of the functions that can be applied on
     *               geometry data types.
     */
    public static function getGISFunctions(
        $geom_type = null, $binary = true, $display = false
    ) {
        $funcs = array();
        if ($display) {
            $funcs[] = array('display' => ' ');
        }

        // Unary functions common to all geometry types
        $funcs['Dimension']    = array('params' => 1, 'type' => 'int');
        $funcs['Envelope']     = array('params' => 1, 'type' => 'Polygon');
        $funcs['GeometryType'] = array('params' => 1, 'type' => 'text');
        $funcs['SRID']         = array('params' => 1, 'type' => 'int');
        $funcs['IsEmpty']      = array('params' => 1, 'type' => 'int');
        $funcs['IsSimple']     = array('params' => 1, 'type' => 'int');

        $geom_type = trim(mb_strtolower($geom_type));
        if ($display && $geom_type != 'geometry' && $geom_type != 'multipoint') {
            $funcs[] = array('display' => '--------');
        }

        // Unary functions that are specific to each geometry type
        if ($geom_type == 'point') {
            $funcs['X'] = array('params' => 1, 'type' => 'float');
            $funcs['Y'] = array('params' => 1, 'type' => 'float');

        } elseif ($geom_type == 'multipoint') {
            // no functions here
        } elseif ($geom_type == 'linestring') {
            $funcs['EndPoint']   = array('params' => 1, 'type' => 'point');
            $funcs['GLength']    = array('params' => 1, 'type' => 'float');
            $funcs['NumPoints']  = array('params' => 1, 'type' => 'int');
            $funcs['StartPoint'] = array('params' => 1, 'type' => 'point');
            $funcs['IsRing']     = array('params' => 1, 'type' => 'int');

        } elseif ($geom_type == 'multilinestring') {
            $funcs['GLength']  = array('params' => 1, 'type' => 'float');
            $funcs['IsClosed'] = array('params' => 1, 'type' => 'int');

        } elseif ($geom_type == 'polygon') {
            $funcs['Area']         = array('params' => 1, 'type' => 'float');
            $funcs['ExteriorRing'] = array('params' => 1, 'type' => 'linestring');
            $funcs['NumInteriorRings'] = array('params' => 1, 'type' => 'int');

        } elseif ($geom_type == 'multipolygon') {
            $funcs['Area']     = array('params' => 1, 'type' => 'float');
            $funcs['Centroid'] = array('params' => 1, 'type' => 'point');
            // Not yet implemented in MySQL
            //$funcs['PointOnSurface'] = array('params' => 1, 'type' => 'point');

        } elseif ($geom_type == 'geometrycollection') {
            $funcs['NumGeometries'] = array('params' => 1, 'type' => 'int');
        }

        // If we are asked for binary functions as well
        if ($binary) {
            // section separator
            if ($display) {
                $funcs[] = array('display' => '--------');
            }

            if (PMA_MYSQL_INT_VERSION < 50601) {
                $funcs['Crosses']    = array('params' => 2, 'type' => 'int');
                $funcs['Contains']   = array('params' => 2, 'type' => 'int');
                $funcs['Disjoint']   = array('params' => 2, 'type' => 'int');
                $funcs['Equals']     = array('params' => 2, 'type' => 'int');
                $funcs['Intersects'] = array('params' => 2, 'type' => 'int');
                $funcs['Overlaps']   = array('params' => 2, 'type' => 'int');
                $funcs['Touches']    = array('params' => 2, 'type' => 'int');
                $funcs['Within']     = array('params' => 2, 'type' => 'int');
            } else {
                // If MySQl version is greater than or equal 5.6.1,
                // use the ST_ prefix.
                $funcs['ST_Crosses']    = array('params' => 2, 'type' => 'int');
                $funcs['ST_Contains']   = array('params' => 2, 'type' => 'int');
                $funcs['ST_Disjoint']   = array('params' => 2, 'type' => 'int');
                $funcs['ST_Equals']     = array('params' => 2, 'type' => 'int');
                $funcs['ST_Intersects'] = array('params' => 2, 'type' => 'int');
                $funcs['ST_Overlaps']   = array('params' => 2, 'type' => 'int');
                $funcs['ST_Touches']    = array('params' => 2, 'type' => 'int');
                $funcs['ST_Within']     = array('params' => 2, 'type' => 'int');
            }

            if ($display) {
                $funcs[] = array('display' => '--------');
            }
            // Minimum bounding rectangle functions
            $funcs['MBRContains']   = array('params' => 2, 'type' => 'int');
            $funcs['MBRDisjoint']   = array('params' => 2, 'type' => 'int');
            $funcs['MBREquals']     = array('params' => 2, 'type' => 'int');
            $funcs['MBRIntersects'] = array('params' => 2, 'type' => 'int');
            $funcs['MBROverlaps']   = array('params' => 2, 'type' => 'int');
            $funcs['MBRTouches']    = array('params' => 2, 'type' => 'int');
            $funcs['MBRWithin']     = array('params' => 2, 'type' => 'int');
        }
        return $funcs;
    }

    /**
     * Returns default function for a particular column.
     *
     * @param array $field       Data about the column for which
     *                           to generate the dropdown
     * @param bool  $insert_mode Whether the operation is 'insert'
     *
     * @global   array    $cfg            PMA configuration
     * @global   mixed    $data           data of currently edited row
     *                                    (used to detect whether to choose defaults)
     *
     * @return string   An HTML snippet of a dropdown list with function
     *                    names appropriate for the requested column.
     */
    public static function getDefaultFunctionForField($field, $insert_mode)
    {
        /*
         * @todo Except for $cfg, no longer use globals but pass as parameters
         *       from higher levels
         */
        global $cfg, $data;

        $default_function   = '';

        // Can we get field class based values?
        $current_class = $GLOBALS['PMA_Types']->getTypeClass($field['True_Type']);
        if (! empty($current_class)) {
            if (isset($cfg['DefaultFunctions']['FUNC_' . $current_class])) {
                $default_function
                    = $cfg['DefaultFunctions']['FUNC_' . $current_class];
            }
        }

        // what function defined as default?
        // for the first timestamp we don't set the default function
        // if there is a default value for the timestamp
        // (not including CURRENT_TIMESTAMP)
        // and the column does not have the
        // ON UPDATE DEFAULT TIMESTAMP attribute.
        if (($field['True_Type'] == 'timestamp')
            && $field['first_timestamp']
            && empty($field['Default'])
            && empty($data)
            && $field['Extra'] != 'on update CURRENT_TIMESTAMP'
            && $field['Null'] == 'NO'
        ) {
            $default_function = $cfg['DefaultFunctions']['first_timestamp'];
        }

        // For primary keys of type char(36) or varchar(36) UUID if the default
        // function
        // Only applies to insert mode, as it would silently trash data on updates.
        if ($insert_mode
            && $field['Key'] == 'PRI'
            && ($field['Type'] == 'char(36)' || $field['Type'] == 'varchar(36)')
        ) {
             $default_function = $cfg['DefaultFunctions']['FUNC_UUID'];
        }

        return $default_function;
    }

    /**
     * Creates a dropdown box with MySQL functions for a particular column.
     *
     * @param array $field       Data about the column for which
     *                           to generate the dropdown
     * @param bool  $insert_mode Whether the operation is 'insert'
     *
     * @return string   An HTML snippet of a dropdown list with function
     *                    names appropriate for the requested column.
     */
    public static function getFunctionsForField($field, $insert_mode)
    {
        $default_function = self::getDefaultFunctionForField($field, $insert_mode);
        $dropdown_built = array();

        // Create the output
        $retval = '<option></option>' . "\n";
        // loop on the dropdown array and print all available options for that
        // field.
        $functions = $GLOBALS['PMA_Types']->getFunctions($field['True_Type']);
        foreach ($functions as $function) {
            $retval .= '<option';
            if ($default_function === $function) {
                $retval .= ' selected="selected"';
            }
            $retval .= '>' . $function . '</option>' . "\n";
            $dropdown_built[$function] = true;
        }

        // Create separator before all functions list
        if (count($functions) > 0) {
            $retval .= '<option value="" disabled="disabled">--------</option>'
                . "\n";
        }

        // For compatibility's sake, do not let out all other functions. Instead
        // print a separator (blank) and then show ALL functions which weren't
        // shown yet.
        $functions = $GLOBALS['PMA_Types']->getAllFunctions();
        foreach ($functions as $function) {
            // Skip already included functions
            if (isset($dropdown_built[$function])) {
                continue;
            }
            $retval .= '<option';
            if ($default_function === $function) {
                $retval .= ' selected="selected"';
            }
            $retval .= '>' . $function . '</option>' . "\n";
        } // end for

        return $retval;
    } // end getFunctionsForField()

    /**
     * Checks if the current user has a specific privilege and returns true if the
     * user indeed has that privilege or false if (s)he doesn't. This function must
     * only be used for features that are available since MySQL 5, because it
     * relies on the INFORMATION_SCHEMA database to be present.
     *
     * Example:   currentUserHasPrivilege('CREATE ROUTINE', 'mydb');
     *            // Checks if the currently logged in user has the global
     *            // 'CREATE ROUTINE' privilege or, if not, checks if the
     *            // user has this privilege on database 'mydb'.
     *
     * @param string $priv The privilege to check
     * @param mixed  $db   null, to only check global privileges
     *                     string, db name where to also check for privileges
     * @param mixed  $tbl  null, to only check global/db privileges
     *                     string, table name where to also check for privileges
     *
     * @return bool
     */
    public static function currentUserHasPrivilege($priv, $db = null, $tbl = null)
    {
        // Get the username for the current user in the format
        // required to use in the information schema database.
        $user = $GLOBALS['dbi']->fetchValue("SELECT CURRENT_USER();");
        if ($user === false) {
            return false;
        }

        if ($user == '@') { // MySQL is started with --skip-grant-tables
            return true;
        }

        $user = explode('@', $user);
        $username  = "''";
        $username .= str_replace("'", "''", $user[0]);
        $username .= "''@''";
        $username .= str_replace("'", "''", $user[1]);
        $username .= "''";

        // Prepare the query
        $query = "SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`%s` "
               . "WHERE GRANTEE='%s' AND PRIVILEGE_TYPE='%s'";

        // Check global privileges first.
        $user_privileges = $GLOBALS['dbi']->fetchValue(
            sprintf(
                $query,
                'USER_PRIVILEGES',
                $username,
                $priv
            )
        );
        if ($user_privileges) {
            return true;
        }
        // If a database name was provided and user does not have the
        // required global privilege, try database-wise permissions.
        if ($db !== null) {
            $query .= " AND '%s' LIKE `TABLE_SCHEMA`";
            $schema_privileges = $GLOBALS['dbi']->fetchValue(
                sprintf(
                    $query,
                    'SCHEMA_PRIVILEGES',
                    $username,
                    $priv,
                    self::sqlAddSlashes($db)
                )
            );
            if ($schema_privileges) {
                return true;
            }
        } else {
            // There was no database name provided and the user
            // does not have the correct global privilege.
            return false;
        }
        // If a table name was also provided and we still didn't
        // find any valid privileges, try table-wise privileges.
        if ($tbl !== null) {
            // need to escape wildcards in db and table names, see bug #3518484
            $tbl = str_replace(array('%', '_'), array('\%', '\_'), $tbl);
            $query .= " AND TABLE_NAME='%s'";
            $table_privileges = $GLOBALS['dbi']->fetchValue(
                sprintf(
                    $query,
                    'TABLE_PRIVILEGES',
                    $username,
                    $priv,
                    self::sqlAddSlashes($db),
                    self::sqlAddSlashes($tbl)
                )
            );
            if ($table_privileges) {
                return true;
            }
        }
        // If we reached this point, the user does not
        // have even valid table-wise privileges.
        return false;
    }

    /**
     * Returns server type for current connection
     *
     * Known types are: MariaDB and MySQL (default)
     *
     * @return string
     */
    public static function getServerType()
    {
        $server_type = 'MySQL';

        if (mb_stripos(PMA_MYSQL_STR_VERSION, 'mariadb') !== false) {
            $server_type = 'MariaDB';
            return $server_type;
        }

        if (mb_stripos(PMA_MYSQL_VERSION_COMMENT, 'percona') !== false) {
            $server_type = 'Percona Server';
            return $server_type;
        }

        return $server_type;
    }

    /**
     * Prepare HTML code for display button.
     *
     * @return String
     */
    public static function getButton()
    {
        return '<p class="print_ignore">'
            . '<input type="button" class="button" id="print" value="'
            . __('Print') . '" />'
            . '</p>';
    }

    /**
     * Parses ENUM/SET values
     *
     * @param string $definition The definition of the column
     *                           for which to parse the values
     * @param bool   $escapeHtml Whether to escape html entities
     *
     * @return array
     */
    public static function parseEnumSetValues($definition, $escapeHtml = true)
    {
        $values_string = htmlentities($definition, ENT_COMPAT, "UTF-8");
        // There is a JS port of the below parser in functions.js
        // If you are fixing something here,
        // you need to also update the JS port.
        $values = array();
        $in_string = false;
        $buffer = '';

        for ($i = 0, $length = mb_strlen($values_string);
             $i < $length;
             $i++
        ) {
            $curr = mb_substr($values_string, $i, 1);
            $next = ($i == mb_strlen($values_string) - 1)
                ? ''
                : mb_substr($values_string, $i + 1, 1);

            if (! $in_string && $curr == "'") {
                $in_string = true;
            } else if (($in_string && $curr == "\\") && $next == "\\") {
                $buffer .= "&#92;";
                $i++;
            } else if (($in_string && $next == "'")
                && ($curr == "'" || $curr == "\\")
            ) {
                $buffer .= "&#39;";
                $i++;
            } else if ($in_string && $curr == "'") {
                $in_string = false;
                $values[] = $buffer;
                $buffer = '';
            } else if ($in_string) {
                 $buffer .= $curr;
            }

        }

        if (mb_strlen($buffer) > 0) {
            // The leftovers in the buffer are the last value (if any)
            $values[] = $buffer;
        }

        if (! $escapeHtml) {
            foreach ($values as $key => $value) {
                $values[$key] = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
            }
        }

        return $values;
    }

    /**
     * Get regular expression which occur first inside the given sql query.
     *
     * @param array  $regex_array Comparing regular expressions.
     * @param String $query       SQL query to be checked.
     *
     * @return String Matching regular expression.
     */
    public static function getFirstOccurringRegularExpression($regex_array, $query)
    {
        $minimum_first_occurence_index = null;
        $regex = null;

        foreach ($regex_array as $test_regex) {
            if (preg_match($test_regex, $query, $matches, PREG_OFFSET_CAPTURE)) {
                if (is_null($minimum_first_occurence_index)
                    || ($matches[0][1] < $minimum_first_occurence_index)
                ) {
                    $regex = $test_regex;
                    $minimum_first_occurence_index = $matches[0][1];
                }
            }
        }
        return $regex;
    }

    /**
     * Return the list of tabs for the menu with corresponding names
     *
     * @param string $level 'server', 'db' or 'table' level
     *
     * @return array list of tabs for the menu
     */
    public static function getMenuTabList($level = null)
    {
        $tabList = array(
            'server' => array(
                'databases'   => __('Databases'),
                'sql'         => __('SQL'),
                'status'      => __('Status'),
                'rights'      => __('Users'),
                'export'      => __('Export'),
                'import'      => __('Import'),
                'settings'    => __('Settings'),
                'binlog'      => __('Binary log'),
                'replication' => __('Replication'),
                'vars'        => __('Variables'),
                'charset'     => __('Charsets'),
                'plugins'     => __('Plugins'),
                'engine'      => __('Engines')
            ),
            'db'     => array(
                'structure'   => __('Structure'),
                'sql'         => __('SQL'),
                'search'      => __('Search'),
                'qbe'         => __('Query'),
                'export'      => __('Export'),
                'import'      => __('Import'),
                'operation'   => __('Operations'),
                'privileges'  => __('Privileges'),
                'routines'    => __('Routines'),
                'events'      => __('Events'),
                'triggers'    => __('Triggers'),
                'tracking'    => __('Tracking'),
                'designer'    => __('Designer'),
                'central_columns' => __('Central columns')
            ),
            'table'  => array(
                'browse'      => __('Browse'),
                'structure'   => __('Structure'),
                'sql'         => __('SQL'),
                'search'      => __('Search'),
                'insert'      => __('Insert'),
                'export'      => __('Export'),
                'import'      => __('Import'),
                'privileges'  => __('Privileges'),
                'operation'   => __('Operations'),
                'tracking'    => __('Tracking'),
                'triggers'    => __('Triggers'),
            )
        );

        if ($level == null) {
            return $tabList;
        } else if (array_key_exists($level, $tabList)) {
            return $tabList[$level];
        } else {
            return null;
        }
    }

    /**
     * Returns information with regards to handling the http request
     *
     * @param array $context Data about the context for which
     *                       to http request is sent
     *
     * @return array of updated context information
     */
    public static function handleContext(array $context)
    {
        if (mb_strlen($GLOBALS['cfg']['ProxyUrl'])) {
            $context['http'] = array(
                'proxy' => $GLOBALS['cfg']['ProxyUrl'],
                'request_fulluri' => true
            );
            if (mb_strlen($GLOBALS['cfg']['ProxyUser'])) {
                $auth = base64_encode(
                    $GLOBALS['cfg']['ProxyUser'] . ':' . $GLOBALS['cfg']['ProxyPass']
                );
                $context['http']['header'] .= 'Proxy-Authorization: Basic '
                    . $auth . "\r\n";
            }
        }
        return $context;
    }
    /**
     * Updates an existing curl as necessary
     *
     * @param resource $curl_handle A curl_handle resource
     *                              created by curl_init which should
     *                              have several options set
     *
     * @return resource curl_handle with updated options
     */
    public static function configureCurl($curl_handle)
    {
        if (mb_strlen($GLOBALS['cfg']['ProxyUrl'])) {
            curl_setopt($curl_handle, CURLOPT_PROXY, $GLOBALS['cfg']['ProxyUrl']);
            if (mb_strlen($GLOBALS['cfg']['ProxyUser'])) {
                curl_setopt(
                    $curl_handle,
                    CURLOPT_PROXYUSERPWD,
                    $GLOBALS['cfg']['ProxyUser'] . ':' . $GLOBALS['cfg']['ProxyPass']
                );
            }
        }
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'phpMyAdmin/' . PMA_VERSION);
        return $curl_handle;
    }

    /**
     * Add fractional seconds to time, datetime and timestamp strings.
     * If the string contains fractional seconds,
     * pads it with 0s up to 6 decimal places.
     *
     * @param string $value time, datetime or timestamp strings
     *
     * @return string time, datetime or timestamp strings with fractional seconds
     */
    public static function addMicroseconds($value)
    {
        if (empty($value) || $value == 'CURRENT_TIMESTAMP') {
            return $value;
        }

        if (mb_strpos($value, '.') === false) {
            return $value . '.000000';
        }

        $value .= '000000';
        return mb_substr(
            $value,
            0,
            mb_strpos($value, '.') + 7
        );
    }

    /**
     * Reads the file, detects the compression MIME type, closes the file
     * and returns the MIME type
     *
     * @param resource $file the file handle
     *
     * @return string the MIME type for compression, or 'none'
     */
    public static function getCompressionMimeType($file)
    {
        $test = fread($file, 4);
        $len = strlen($test);
        fclose($file);
        if ($len >= 2 && $test[0] == chr(31) && $test[1] == chr(139)) {
            return 'application/gzip';
        }
        if ($len >= 3 && substr($test, 0, 3) == 'BZh') {
            return 'application/bzip2';
        }
        if ($len >= 4 && $test == "PK\003\004") {
            return 'application/zip';
        }
        return 'none';
    }

    /**
     * Renders a single link for the top of the navigation panel
     *
     * @param string  $link        The url for the link
     * @param bool    $showText    Whether to show the text or to
     *                             only use it for title attributes
     * @param string  $text        The text to display and use for title attributes
     * @param bool    $showIcon    Whether to show the icon
     * @param string  $icon        The filename of the icon to show
     * @param string  $linkId      Value to use for the ID attribute
     * @param boolean $disableAjax Whether to disable ajax page loading for this link
     * @param string  $linkTarget  The name of the target frame for the link
     * @param array   $classes     HTML classes to apply
     *
     * @return string HTML code for one link
     */
    public static function getNavigationLink(
        $link,
        $showText,
        $text,
        $showIcon,
        $icon,
        $linkId = '',
        $disableAjax = false,
        $linkTarget = '',
        $classes = array()
    ) {
        $retval = '<a href="' . $link . '"';
        if (! empty($linkId)) {
            $retval .= ' id="' . $linkId . '"';
        }
        if (! empty($linkTarget)) {
            $retval .= ' target="' . $linkTarget . '"';
        }
        if ($disableAjax) {
            $classes[] = 'disableAjax';
        }
        if (!empty($classes)) {
            $retval .= ' class="' . join(" ", $classes) . '"';
        }
        $retval .= ' title="' . $text . '">';
        if ($showIcon) {
            $retval .= Util::getImage(
                $icon,
                $text
            );
        }
        if ($showText) {
            $retval .= $text;
        }
        $retval .= '</a>';
        if ($showText) {
            $retval .= '<br />';
        }
        return $retval;
    }

    /**
     * Provide COLLATE clause, if required, to perform case sensitive comparisons
     * for queries on information_schema.
     *
     * @return string COLLATE clause if needed or empty string.
     */
    public static function getCollateForIS()
    {
        $lowerCaseTableNames = self::cacheGet(
            'lower_case_table_names',
            function () {
                return $GLOBALS['dbi']->fetchValue(
                    "SELECT @@lower_case_table_names"
                );
            }
        );

        if ($lowerCaseTableNames === '0' // issue #10961
            || $lowerCaseTableNames === '2' // issue #11461
        ) {
            return "COLLATE utf8_bin";
        }
        return "";
    }

    /**
     * Process the index data.
     *
     * @param array $indexes index data
     *
     * @return array processes index data
     */
    public static function processIndexData($indexes)
    {
        $lastIndex    = '';

        $primary      = '';
        $pk_array     = array(); // will be use to emphasis prim. keys in the table
        $indexes_info = array();
        $indexes_data = array();

        // view
        foreach ($indexes as $row) {
            // Backups the list of primary keys
            if ($row['Key_name'] == 'PRIMARY') {
                $primary   .= $row['Column_name'] . ', ';
                $pk_array[$row['Column_name']] = 1;
            }
            // Retains keys informations
            if ($row['Key_name'] != $lastIndex) {
                $indexes[] = $row['Key_name'];
                $lastIndex = $row['Key_name'];
            }
            $indexes_info[$row['Key_name']]['Sequences'][] = $row['Seq_in_index'];
            $indexes_info[$row['Key_name']]['Non_unique'] = $row['Non_unique'];
            if (isset($row['Cardinality'])) {
                $indexes_info[$row['Key_name']]['Cardinality'] = $row['Cardinality'];
            }
            // I don't know what does following column mean....
            // $indexes_info[$row['Key_name']]['Packed']          = $row['Packed'];

            $indexes_info[$row['Key_name']]['Comment'] = $row['Comment'];

            $indexes_data[$row['Key_name']][$row['Seq_in_index']]['Column_name']
                = $row['Column_name'];
            if (isset($row['Sub_part'])) {
                $indexes_data[$row['Key_name']][$row['Seq_in_index']]['Sub_part']
                    = $row['Sub_part'];
            }

        } // end while

        return array($primary, $pk_array, $indexes_info, $indexes_data);
    }

    /**
     * Returns the HTML for check all check box and with selected text
     * for multi submits
     *
     * @param string $pmaThemeImage path to theme's image folder
     * @param string $text_dir      text direction
     * @param string $formName      name of the enclosing form
     *
     * @return string HTML
     */
    public static function getWithSelected($pmaThemeImage, $text_dir, $formName)
    {
        $html = '<img class="selectallarrow" '
            . 'src="' . $pmaThemeImage . 'arrow_' . $text_dir . '.png" '
            . 'width="38" height="22" alt="' . __('With selected:') . '" />';
        $html .= '<input type="checkbox" id="' . $formName . '_checkall" '
            . 'class="checkall_box" title="' . __('Check all') . '" />'
            . '<label for="' . $formName . '_checkall">' . __('Check all')
            . '</label>';
        $html .= '<i style="margin-left: 2em">'
            . __('With selected:') . '</i>';

        return $html;
    }

    /**
     * Function to get html for the start row and number of rows panel
     *
     * @param string $sql_query sql query
     *
     * @return string html
     */
    public static function getStartAndNumberOfRowsPanel($sql_query)
    {
        $pos = isset($_REQUEST['pos'])
            ? $_REQUEST['pos']
            : $_SESSION['tmpval']['pos'];
        if (isset($_REQUEST['session_max_rows'])) {
            $rows = $_REQUEST['session_max_rows'];
        } else {
            if ($_SESSION['tmpval']['max_rows'] != 'all') {
                $rows = $_SESSION['tmpval']['max_rows'];
            } else {
                $rows = $GLOBALS['cfg']['MaxRows'];
            }
        }

        return Template::get('startAndNumberOfRowsPanel')
            ->render(
                array(
                    'pos' => $pos,
                    'unlim_num_rows' => $_REQUEST['unlim_num_rows'],
                    'rows' => $rows,
                    'sql_query' => $sql_query,
                )
            );
    }

    /**
     * Returns whether the database server supports virtual columns
     *
     * @return bool
     */
    public static function isVirtualColumnsSupported()
    {
        $serverType = self::getServerType();
        return $serverType == 'MySQL' && PMA_MYSQL_INT_VERSION >= 50705
             || ($serverType == 'MariaDB' && PMA_MYSQL_INT_VERSION >= 50200);
    }

    /**
     * Returns the proper class clause according to the column type
     *
     * @param string $type the column type
     *
     * @return string $class_clause the HTML class clause
     */
    public static function getClassForType($type)
    {
        if ('set' == $type
            || 'enum' == $type
        ) {
            $class_clause = '';
        } else {
            $class_clause = ' class="nowrap"';
        }
        return $class_clause;
    }

    /**
     * Gets the list of tables in the current db and information about these
     * tables if possible
     *
     * @param string $db       database name
     * @param string $sub_part part of script name
     *
     * @return array
     *
     */
    public static function getDbInfo($db, $sub_part)
    {
        global $cfg;

        /**
         * limits for table list
         */
        if (! isset($_SESSION['tmpval']['table_limit_offset'])
            || $_SESSION['tmpval']['table_limit_offset_db'] != $db
        ) {
            $_SESSION['tmpval']['table_limit_offset'] = 0;
            $_SESSION['tmpval']['table_limit_offset_db'] = $db;
        }
        if (isset($_REQUEST['pos'])) {
            $_SESSION['tmpval']['table_limit_offset'] = (int) $_REQUEST['pos'];
        }
        $pos = $_SESSION['tmpval']['table_limit_offset'];

        /**
         * whether to display extended stats
         */
        $is_show_stats = $cfg['ShowStats'];

        /**
         * whether selected db is information_schema
         */
        $db_is_system_schema = false;

        if ($GLOBALS['dbi']->isSystemSchema($db)) {
            $is_show_stats = false;
            $db_is_system_schema = true;
        }

        /**
         * information about tables in db
         */
        $tables = array();

        $tooltip_truename = array();
        $tooltip_aliasname = array();

        // Special speedup for newer MySQL Versions (in 4.0 format changed)
        if (true === $cfg['SkipLockedTables']) {
            $db_info_result = $GLOBALS['dbi']->query(
                'SHOW OPEN TABLES FROM ' . Util::backquote($db) . ';'
            );

            // Blending out tables in use
            if ($db_info_result && $GLOBALS['dbi']->numRows($db_info_result) > 0) {
                $tables = self::getTablesWhenOpen($db, $db_info_result);

            } elseif ($db_info_result) {
                $GLOBALS['dbi']->freeResult($db_info_result);
            }
        }

        if (empty($tables)) {
            // Set some sorting defaults
            $sort = 'Name';
            $sort_order = 'ASC';

            if (isset($_REQUEST['sort'])) {
                $sortable_name_mappings = array(
                    'table'       => 'Name',
                    'records'     => 'Rows',
                    'type'        => 'Engine',
                    'collation'   => 'Collation',
                    'size'        => 'Data_length',
                    'overhead'    => 'Data_free',
                    'creation'    => 'Create_time',
                    'last_update' => 'Update_time',
                    'last_check'  => 'Check_time',
                    'comment'     => 'Comment',
                );

                // Make sure the sort type is implemented
                if (isset($sortable_name_mappings[$_REQUEST['sort']])) {
                    $sort = $sortable_name_mappings[$_REQUEST['sort']];
                    if ($_REQUEST['sort_order'] == 'DESC') {
                        $sort_order = 'DESC';
                    }
                }
            }

            $groupWithSeparator = false;
            $tbl_type = null;
            $limit_offset = 0;
            $limit_count = false;
            $groupTable = array();

            if (! empty($_REQUEST['tbl_group']) || ! empty($_REQUEST['tbl_type'])) {
                if (! empty($_REQUEST['tbl_type'])) {
                    // only tables for selected type
                    $tbl_type = $_REQUEST['tbl_type'];
                }
                if (! empty($_REQUEST['tbl_group'])) {
                    // only tables for selected group
                    $tbl_group = $_REQUEST['tbl_group'];
                    // include the table with the exact name of the group if such
                    // exists
                    $groupTable = $GLOBALS['dbi']->getTablesFull(
                        $db, $tbl_group, false, null, $limit_offset,
                        $limit_count, $sort, $sort_order, $tbl_type
                    );
                    $groupWithSeparator = $tbl_group
                        . $GLOBALS['cfg']['NavigationTreeTableSeparator'];
                }
            } else {
                // all tables in db
                // - get the total number of tables
                //  (needed for proper working of the MaxTableList feature)
                $tables = $GLOBALS['dbi']->getTables($db);
                $total_num_tables = count($tables);
                if (isset($sub_part) && $sub_part == '_export') {
                    // (don't fetch only a subset if we are coming from
                    // db_export.php, because I think it's too risky to display only
                    // a subset of the table names when exporting a db)
                    /**
                     *
                     * @todo Page selector for table names?
                     */
                } else {
                    // fetch the details for a possible limited subset
                    $limit_offset = $pos;
                    $limit_count = true;
                }
            }
            $tables = array_merge(
                $groupTable,
                $GLOBALS['dbi']->getTablesFull(
                    $db, $groupWithSeparator, ($groupWithSeparator !== false), null,
                    $limit_offset, $limit_count, $sort, $sort_order, $tbl_type
                )
            );
        }

        $num_tables = count($tables);
        //  (needed for proper working of the MaxTableList feature)
        if (! isset($total_num_tables)) {
            $total_num_tables = $num_tables;
        }

        /**
         * If coming from a Show MySQL link on the home page,
         * put something in $sub_part
         */
        if (empty($sub_part)) {
            $sub_part = '_structure';
        }

        return array(
            $tables,
            $num_tables,
            $total_num_tables,
            $sub_part,
            $is_show_stats,
            $db_is_system_schema,
            $tooltip_truename,
            $tooltip_aliasname,
            $pos
        );
    }

    /**
     * Gets the list of tables in the current db, taking into account
     * that they might be "in use"
     *
     * @param string $db             database name
     * @param object $db_info_result result set
     *
     * @return array $tables list of tables
     *
     */
    public static function getTablesWhenOpen($db, $db_info_result)
    {
        $sot_cache = $tables = array();

        while ($tmp = $GLOBALS['dbi']->fetchAssoc($db_info_result)) {
            // if in use, memorize table name
            if ($tmp['In_use'] > 0) {
                $sot_cache[$tmp['Table']] = true;
            }
        }
        $GLOBALS['dbi']->freeResult($db_info_result);

        // is there at least one "in use" table?
        if (isset($sot_cache)) {
            $tblGroupSql = "";
            $whereAdded = false;
            if (PMA_isValid($_REQUEST['tbl_group'])) {
                $group = Util::escapeMysqlWildcards($_REQUEST['tbl_group']);
                $groupWithSeparator = Util::escapeMysqlWildcards(
                    $_REQUEST['tbl_group']
                    . $GLOBALS['cfg']['NavigationTreeTableSeparator']
                );
                $tblGroupSql .= " WHERE ("
                    . Util::backquote('Tables_in_' . $db)
                    . " LIKE '" . $groupWithSeparator . "%'"
                    . " OR "
                    . Util::backquote('Tables_in_' . $db)
                    . " LIKE '" . $group . "')";
                $whereAdded = true;
            }
            if (PMA_isValid($_REQUEST['tbl_type'], array('table', 'view'))) {
                $tblGroupSql .= $whereAdded ? " AND" : " WHERE";
                if ($_REQUEST['tbl_type'] == 'view') {
                    $tblGroupSql .= " `Table_type` != 'BASE TABLE'";
                } else {
                    $tblGroupSql .= " `Table_type` = 'BASE TABLE'";
                }
            }
            $db_info_result = $GLOBALS['dbi']->query(
                'SHOW FULL TABLES FROM ' . Util::backquote($db) . $tblGroupSql,
                null, DatabaseInterface::QUERY_STORE
            );
            unset($tblGroupSql, $whereAdded);

            if ($db_info_result && $GLOBALS['dbi']->numRows($db_info_result) > 0) {
                while ($tmp = $GLOBALS['dbi']->fetchRow($db_info_result)) {
                    if (! isset($sot_cache[$tmp[0]])) {
                        $sts_result = $GLOBALS['dbi']->query(
                            "SHOW TABLE STATUS FROM " . Util::backquote($db)
                            . " LIKE '" . Util::sqlAddSlashes($tmp[0], true)
                            . "';"
                        );
                        $sts_tmp = $GLOBALS['dbi']->fetchAssoc($sts_result);
                        $GLOBALS['dbi']->freeResult($sts_result);
                        unset($sts_result);

                        $tableArray = $GLOBALS['dbi']->copyTableProperties(
                            array($sts_tmp), $db
                        );
                                $tables[$sts_tmp['Name']] = $tableArray[0];
                    } else { // table in use
                        $tables[$tmp[0]] = array(
                            'TABLE_NAME' => $tmp[0],
                            'ENGINE' => '',
                            'TABLE_TYPE' => '',
                            'TABLE_ROWS' => 0,
                            'TABLE_COMMENT' => '',
                        );
                    }
                } // end while
                if ($GLOBALS['cfg']['NaturalOrder']) {
                    uksort($tables, 'strnatcasecmp');
                }

            } elseif ($db_info_result) {
                $GLOBALS['dbi']->freeResult($db_info_result);
            }
            unset($sot_cache);
        }
        return $tables;
    }

    /**
     * Returs list of used PHP extensions.
     *
     * @return array of strings
     */
    public static function listPHPExtensions()
    {
        $result = array();
        if (DatabaseInterface::checkDbExtension('mysqli')) {
            $result[] = 'mysqli';
        } else {
            $result[] = 'mysql';
        }

        if (extension_loaded('curl')) {
            $result[] = 'curl';
        }

        if (extension_loaded('mbstring')) {
            $result[] = 'mbstring';
        }

        return $result;
    }
}

