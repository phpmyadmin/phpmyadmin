<?php
/**
 * HTML Generator
 */
declare(strict_types=1);

namespace PhpMyAdmin\Html;

use PhpMyAdmin\Core;
use PhpMyAdmin\Html\ActionLinksModes\Factory;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\SqlParser\Lexer;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Utils\Error as ParserError;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use Throwable;
use Twig_Error_Loader;
use Twig_Error_Runtime;
use Twig_Error_Syntax;
use Williamdes\MariaDBMySQLKBS\KBException;
use Williamdes\MariaDBMySQLKBS\Search as KBSearch;
use function addslashes;
use function array_key_exists;
use function array_merge;
use function basename;
use function ceil;
use function count;
use function explode;
use function floor;
use function htmlentities;
use function htmlspecialchars;
use function implode;
use function in_array;
use function ini_get;
use function intval;
use function is_array;
use function mb_strlen;
use function mb_strstr;
use function mb_strtolower;
use function mb_substr;
use function nl2br;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_replace;
use function strlen;
use function strncmp;
use function strpos;
use function trigger_error;
use function trim;
use function urlencode;
use const E_USER_NOTICE;

/**
 * HTML Generator
 */
class Generator
{
    /**
     * Displays a button to copy content to clipboard
     *
     * @param string $text Text to copy to clipboard
     *
     * @return string  the html link
     *
     * @access public
     */
    public static function showCopyToClipboard(string $text): string
    {
        return '  <a href="#" class="copyQueryBtn" data-text="'
            . htmlspecialchars($text) . '">' . __('Copy') . '</a>';
    }

    /**
     * Get a link to variable documentation
     *
     * @param string $name       The variable name
     * @param bool   $useMariaDB Use only MariaDB documentation
     * @param string $text       (optional) The text for the link
     *
     * @return string link or empty string
     */
    public static function linkToVarDocumentation(
        string $name,
        bool $useMariaDB = false,
        string $text = null
    ): string {
        $html = '';
        try {
            $type = KBSearch::MYSQL;
            if ($useMariaDB) {
                $type = KBSearch::MARIADB;
            }
            $docLink = KBSearch::getByName($name, $type);
            $html = MySQLDocumentation::show(
                $name,
                false,
                $docLink,
                $text
            );
        } catch (KBException $e) {
            unset($e);// phpstan workaround
        }
        return $html;
    }

    /**
     * Returns HTML code for a tooltip
     *
     * @param string $message the message for the tooltip
     *
     * @access public
     */
    public static function showHint($message): string
    {
        if ($GLOBALS['cfg']['ShowHint']) {
            $classClause = ' class="pma_hint"';
        } else {
            $classClause = '';
        }
        return '<span' . $classClause . '>'
            . self::getImage('b_help')
            . '<span class="hide">' . $message . '</span>'
            . '</span>';
    }

    /**
     * Generate a button or image tag
     *
     * @param string $button_name  name of button element
     * @param string $button_class class of button or image element
     * @param string $text         text to display
     * @param string $image        image to display
     * @param string $value        value
     *
     * @return string              html content
     *
     * @access public
     */
    public static function getButtonOrImage(
        $button_name,
        $button_class,
        $text,
        $image,
        $value = ''
    ): string {
        return Factory::build($GLOBALS['cfg']['ActionLinksMode'])
            ->generate($button_name, $button_class, $text, $image, $value);
    }

    /**
     * returns html code for db link to default db page
     *
     * @param string $database database
     *
     * @return string  html link to default db page
     */
    public static function getDbLink($database = ''): string
    {
        if ((string) $database === '') {
            if ((string) $GLOBALS['db'] === '') {
                return '';
            }
            $database = $GLOBALS['db'];
        } else {
            $database = Util::unescapeMysqlWildcards($database);
        }

        $scriptName = Util::getScriptNameForOption(
            $GLOBALS['cfg']['DefaultTabDatabase'],
            'database'
        );
        return '<a href="'
            . $scriptName
            . Url::getCommon(['db' => $database], strpos($scriptName, '?') === false ? '?' : '&')
            . '" title="'
            . htmlspecialchars(
                sprintf(
                    __('Jump to database “%s”.'),
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
     */
    public static function getExternalBug(
        $functionality,
        $component,
        $minimum_version,
        $bugref
    ): string {
        $ext_but_html = '';
        if (($component === 'mysql') && ($GLOBALS['dbi']->getVersion() < $minimum_version)) {
            $ext_but_html .= self::showHint(
                sprintf(
                    __('The %s functionality is affected by a known bug, see %s'),
                    $functionality,
                    Core::linkURL('https://bugs.mysql.com/') . $bugref
                )
            );
        }
        return $ext_but_html;
    }

    /**
     * Creates an AJAX sliding toggle button
     * (or and equivalent form when AJAX is disabled)
     *
     * @param string $action      The URL for the request to be executed
     * @param string $select_name The name for the dropdown box
     * @param array  $options     An array of options
     * @param string $callback    A JS snippet to execute when the request is
     *                            successfully processed
     *
     * @return string   HTML code for the toggle button
     *
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public static function toggleButton($action, $select_name, array $options, $callback): string
    {
        $template = new Template();
        // Do the logic first
        $link = $action . '&amp;' . urlencode($select_name) . '=';
        $link_on = $link . urlencode($options[1]['value']);
        $link_off = $link . urlencode($options[0]['value']);

        if ($options[1]['selected'] == true) {
            $state = 'on';
        } elseif ($options[0]['selected'] == true) {
            $state = 'off';
        } else {
            $state = 'on';
        }

        return $template->render(
            'toggle_button',
            [
                'pma_theme_image' => $GLOBALS['pmaThemeImage'],
                'text_dir' => $GLOBALS['text_dir'],
                'link_on' => $link_on,
                'link_off' => $link_off,
                'toggle_on' => $options[1]['label'],
                'toggle_off' => $options[0]['label'],
                'callback' => $callback,
                'state' => $state,
            ]
        );
    }

    /**
     * Returns an HTML IMG tag for a particular icon from a theme,
     * which may be an actual file or an icon from a sprite.
     * This function takes into account the ActionLinksMode
     * configuration setting and wraps the image tag in a span tag.
     *
     * @param string $icon          name of icon file
     * @param string $alternate     alternate text
     * @param bool   $force_text    whether to force alternate text to be displayed
     * @param bool   $menu_icon     whether this icon is for the menu bar or not
     * @param string $control_param which directive controls the display
     *
     * @return string an html snippet
     */
    public static function getIcon(
        $icon,
        $alternate = '',
        $force_text = false,
        $menu_icon = false,
        $control_param = 'ActionLinksMode'
    ): string {
        $include_icon = $include_text = false;
        if (Util::showIcons($control_param)) {
            $include_icon = true;
        }
        if ($force_text
            || Util::showText($control_param)
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
     * Returns information about SSL status for current connection
     */
    public static function getServerSSL(): string
    {
        $server = $GLOBALS['cfg']['Server'];
        $class = 'caution';
        if (! $server['ssl']) {
            $message = __('SSL is not being used');
            if (! empty($server['socket']) || $server['host'] === '127.0.0.1' || $server['host'] === 'localhost') {
                $class = '';
            }
        } elseif (! $server['ssl_verify']) {
            $message = __('SSL is used with disabled verification');
        } elseif (empty($server['ssl_ca'])) {
            $message = __('SSL is used without certification authority');
        } else {
            $class = '';
            $message = __('SSL is used');
        }
        return '<span class="' . $class . '">' . $message . '</span> ' . MySQLDocumentation::showDocumentation(
                'setup',
                'ssl'
            );
    }

    /**
     * Returns default function for a particular column.
     *
     * @param array $field       Data about the column for which
     *                           to generate the dropdown
     * @param bool  $insert_mode Whether the operation is 'insert'
     *
     * @return string An HTML snippet of a dropdown list with function
     *                names appropriate for the requested column.
     *
     * @global mixed $data data of currently edited row
     *                     (used to detect whether to choose defaults)
     * @global array $cfg  PMA configuration
     */
    public static function getDefaultFunctionForField(array $field, $insert_mode): string
    {
        /*
         * @todo Except for $cfg, no longer use globals but pass as parameters
         *       from higher levels
         */
        global $cfg, $data;

        $default_function = '';

        // Can we get field class based values?
        $current_class = $GLOBALS['dbi']->types->getTypeClass($field['True_Type']);
        if (! empty($current_class) && isset($cfg['DefaultFunctions']['FUNC_' . $current_class])) {
            $default_function = $cfg['DefaultFunctions']['FUNC_' . $current_class];
        }

        // what function defined as default?
        // for the first timestamp we don't set the default function
        // if there is a default value for the timestamp
        // (not including CURRENT_TIMESTAMP)
        // and the column does not have the
        // ON UPDATE DEFAULT TIMESTAMP attribute.
        if (($field['True_Type'] === 'timestamp')
            && $field['first_timestamp']
            && empty($field['Default'])
            && empty($data)
            && $field['Extra'] !== 'on update CURRENT_TIMESTAMP'
            && $field['Null'] === 'NO'
        ) {
            $default_function = $cfg['DefaultFunctions']['first_timestamp'];
        }

        // For primary keys of type char(36) or varchar(36) UUID if the default
        // function
        // Only applies to insert mode, as it would silently trash data on updates.
        if ($insert_mode
            && $field['Key'] === 'PRI'
            && ($field['Type'] === 'char(36)' || $field['Type'] === 'varchar(36)')
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
     * @param array $foreignData Foreign data
     *
     * @return string   An HTML snippet of a dropdown list with function
     *                    names appropriate for the requested column.
     */
    public static function getFunctionsForField(array $field, $insert_mode, array $foreignData): string
    {
        $default_function = self::getDefaultFunctionForField($field, $insert_mode);
        $dropdown_built = [];

        // Create the output
        $retval = '<option></option>' . "\n";
        // loop on the dropdown array and print all available options for that
        // field.
        $functions = $GLOBALS['dbi']->types->getFunctions($field['True_Type']);
        foreach ($functions as $function) {
            $retval .= '<option';
            if (isset($foreignData['foreign_link']) && $foreignData['foreign_link'] !== false
                && $default_function
                === $function) {
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
        $functions = $GLOBALS['dbi']->types->getAllFunctions();
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
    }

    /**
     * Renders a single link for the top of the navigation panel
     *
     * @param string $link        The url for the link
     * @param bool   $showText    Whether to show the text or to
     *                            only use it for title attributes
     * @param string $text        The text to display and use for title attributes
     * @param bool   $showIcon    Whether to show the icon
     * @param string $icon        The filename of the icon to show
     * @param string $linkId      Value to use for the ID attribute
     * @param bool   $disableAjax Whether to disable ajax page loading for this link
     * @param string $linkTarget  The name of the target frame for the link
     * @param array  $classes     HTML classes to apply
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
        array $classes = []
    ): string {
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
        if (! empty($classes)) {
            $retval .= ' class="' . implode(' ', $classes) . '"';
        }
        $retval .= ' title="' . $text . '">';
        if ($showIcon) {
            $retval .= self::getImage(
                $icon,
                $text
            );
        }
        if ($showText) {
            $retval .= $text;
        }
        $retval .= '</a>';
        if ($showText) {
            $retval .= '<br>';
        }
        return $retval;
    }

    /**
     * Function to get html for the start row and number of rows panel
     *
     * @param string $sql_query sql query
     *
     * @return string html
     *
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public static function getStartAndNumberOfRowsPanel($sql_query): string
    {
        $template = new Template();

        if (isset($_REQUEST['session_max_rows'])) {
            $rows = $_REQUEST['session_max_rows'];
        } elseif (isset($_SESSION['tmpval']['max_rows'])
            && $_SESSION['tmpval']['max_rows'] !== 'all'
        ) {
            $rows = $_SESSION['tmpval']['max_rows'];
        } else {
            $rows = (int) $GLOBALS['cfg']['MaxRows'];
            $_SESSION['tmpval']['max_rows'] = $rows;
        }

        if (isset($_REQUEST['pos'])) {
            $pos = $_REQUEST['pos'];
        } elseif (isset($_SESSION['tmpval']['pos'])) {
            $pos = $_SESSION['tmpval']['pos'];
        } else {
            $number_of_line = (int) $_REQUEST['unlim_num_rows'];
            $pos = (ceil($number_of_line / $rows) - 1) * $rows;
            $_SESSION['tmpval']['pos'] = $pos;
        }

        return $template->render(
            'start_and_number_of_rows_panel',
            [
                'pos' => $pos,
                'unlim_num_rows' => (int) $_REQUEST['unlim_num_rows'],
                'rows' => $rows,
                'sql_query' => $sql_query,
            ]
        );
    }

    /**
     * Execute an EXPLAIN query and formats results similar to MySQL command line
     * utility.
     *
     * @param string $sqlQuery EXPLAIN query
     *
     * @return string query resuls
     */
    private static function _generateRowQueryOutput($sqlQuery): string
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
                    if ($value === null) {
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
     * Prepare the message and the query
     * usually the message is the result of the query executed
     *
     * @param Message|string $message   the message to display
     * @param string         $sql_query the query to display
     * @param string         $type      the type (level) of the message
     *
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     *
     * @access public
     */
    public static function getMessage(
        $message,
        $sql_query = null,
        $type = 'notice'
    ): string {
        global $cfg;
        $template = new Template();
        $retval = '';

        if ($sql_query === null) {
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
            $retval .= '<div class="result_query">' . "\n";
        }

        if ($message instanceof Message) {
            if (isset($GLOBALS['special_message'])) {
                $message->addText($GLOBALS['special_message']);
                unset($GLOBALS['special_message']);
            }
            $retval .= $message->getDisplay();
        } else {
            $context = 'primary';
            if ($type === 'error') {
                $context = 'danger';
            } elseif ($type === 'success') {
                $context = 'success';
            }
            $retval .= '<div class="alert alert-' . $context . '" role="alert">';
            $retval .= Sanitize::sanitizeMessage($message);
            if (isset($GLOBALS['special_message'])) {
                $retval .= Sanitize::sanitizeMessage($GLOBALS['special_message']);
                unset($GLOBALS['special_message']);
            }
            $retval .= '</div>';
        }

        if ($render_sql) {
            $query_too_big = false;

            $queryLength = mb_strlen($sql_query);
            if ($queryLength > $cfg['MaxCharactersInDisplayedSQL']) {
                // when the query is large (for example an INSERT of binary
                // data), the parser chokes; so avoid parsing the query
                $query_too_big = true;
                $query_base = mb_substr(
                        $sql_query,
                        0,
                        $cfg['MaxCharactersInDisplayedSQL']
                    ) . '[...]';
            } else {
                $query_base = $sql_query;
            }

            // Html format the query to be displayed
            // If we want to show some sql code it is easiest to create it here
            /* SQL-Parser-Analyzer */

            if (! empty($GLOBALS['show_as_php'])) {
                $new_line = '\\n"<br>' . "\n" . '&nbsp;&nbsp;&nbsp;&nbsp;. "';
                $query_base = htmlspecialchars(addslashes($query_base));
                $query_base = preg_replace(
                    '/((\015\012)|(\015)|(\012))/',
                    $new_line,
                    $query_base
                );
                $query_base = '<code class="php"><pre>' . "\n"
                    . '$sql = "' . $query_base . '";' . "\n"
                    . '</pre></code>';
            } elseif ($query_too_big) {
                $query_base = '<code class="sql"><pre>' . "\n" .
                    htmlspecialchars($query_base) .
                    '</pre></code>';
            } else {
                $query_base = self::formatSql($query_base);
            }

            // Prepares links that may be displayed to edit/explain the query
            // (don't go to default pages, we must go to the page
            // where the query box is available)

            // Basic url query part
            $url_params = [];
            if (! isset($GLOBALS['db'])) {
                $GLOBALS['db'] = '';
            }
            if (strlen($GLOBALS['db']) > 0) {
                $url_params['db'] = $GLOBALS['db'];
                if (strlen($GLOBALS['table']) > 0) {
                    $url_params['table'] = $GLOBALS['table'];
                    $edit_link = Url::getFromRoute('/table/sql');
                } else {
                    $edit_link = Url::getFromRoute('/database/sql');
                }
            } else {
                $edit_link = Url::getFromRoute('/server/sql');
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
                    $explain_link = ' [&nbsp;'
                        . self::linkOrButton(
                            Url::getFromRoute('/import', $explain_params),
                            __('Explain SQL')
                        ) . '&nbsp;]';
                } elseif (preg_match(
                    '@^EXPLAIN[[:space:]]+SELECT[[:space:]]+@i',
                    $sql_query
                )) {
                    $explain_params['sql_query']
                        = mb_substr($sql_query, 8);
                    $explain_link = ' [&nbsp;'
                        . self::linkOrButton(
                            Url::getFromRoute('/import', $explain_params),
                            __('Skip Explain SQL')
                        ) . ']';
                    $url = 'https://mariadb.org/explain_analyzer/analyze/'
                        . '?client=phpMyAdmin&raw_explain='
                        . urlencode(self::_generateRowQueryOutput($sql_query));
                    $explain_link .= ' ['
                        . self::linkOrButton(
                            htmlspecialchars('url.php?url=' . urlencode($url)),
                            sprintf(__('Analyze Explain at %s'), 'mariadb.org'),
                            [],
                            '_blank'
                        ) . '&nbsp;]';
                }
            } //show explain

            $url_params['sql_query'] = $sql_query;
            $url_params['show_query'] = 1;

            // even if the query is big and was truncated, offer the chance
            // to edit it (unless it's enormous, see linkOrButton() )
            if (! empty($cfg['SQLQuery']['Edit'])
                && empty($GLOBALS['show_as_php'])
            ) {
                $edit_link .= Url::getCommon($url_params, '&');
                $edit_link = ' [&nbsp;'
                    . self::linkOrButton($edit_link, __('Edit'))
                    . '&nbsp;]';
            } else {
                $edit_link = '';
            }

            // Also we would like to get the SQL formed in some nice
            // php-code
            if (! empty($cfg['SQLQuery']['ShowAsPHP']) && ! $query_too_big) {
                if (! empty($GLOBALS['show_as_php'])) {
                    $php_link = ' [&nbsp;'
                        . self::linkOrButton(
                            Url::getFromRoute('/import', $url_params),
                            __('Without PHP code')
                        )
                        . '&nbsp;]';

                    $php_link .= ' [&nbsp;'
                        . self::linkOrButton(
                            Url::getFromRoute('/import', $url_params),
                            __('Submit query')
                        )
                        . '&nbsp;]';
                } else {
                    $php_params = $url_params;
                    $php_params['show_as_php'] = 1;
                    $php_link = ' [&nbsp;'
                        . self::linkOrButton(
                            Url::getFromRoute('/import', $php_params),
                            __('Create PHP code')
                        )
                        . '&nbsp;]';
                }
            } else {
                $php_link = '';
            } //show as php

            // Refresh query
            if (! empty($cfg['SQLQuery']['Refresh'])
                && ! isset($GLOBALS['show_as_php']) // 'Submit query' does the same
                && preg_match('@^(SELECT|SHOW)[[:space:]]+@i', $sql_query)
            ) {
                $refresh_link = Url::getFromRoute('/sql', $url_params);
                $refresh_link = ' [&nbsp;'
                    . self::linkOrButton($refresh_link, __('Refresh')) . '&nbsp;]';
            } else {
                $refresh_link = '';
            } //refresh

            $retval .= '<div class="sqlOuter">';
            $retval .= $query_base;
            $retval .= '</div>';

            $retval .= '<div class="tools print_ignore">';
            $retval .= '<form action="' . Url::getFromRoute('/sql') . '" method="post">';
            $retval .= Url::getHiddenInputs($GLOBALS['db'], $GLOBALS['table']);
            $retval .= '<input type="hidden" name="sql_query" value="'
                . htmlspecialchars($sql_query) . '">';

            // avoid displaying a Profiling checkbox that could
            // be checked, which would reexecute an INSERT, for example
            if (! empty($refresh_link) && Util::profilingSupported()) {
                $retval .= '<input type="hidden" name="profiling_form" value="1">';
                $retval .= '<input type="checkbox" name="profiling" id="profilingCheckbox" class="autosubmit"';
                $retval .= isset($_SESSION['profiling']) ? ' checked' : '';
                $retval .= '> <label for="profilingCheckbox">' . __('Profiling') . '</label>';
            }
            $retval .= '</form>';

            /**
             * TODO: Should we have $cfg['SQLQuery']['InlineEdit']?
             */
            if (! empty($cfg['SQLQuery']['Edit'])
                && ! $query_too_big
                && empty($GLOBALS['show_as_php'])
            ) {
                $inline_edit_link = ' [&nbsp;'
                    . self::linkOrButton(
                        '#',
                        _pgettext('Inline edit query', 'Edit inline'),
                        ['class' => 'inline_edit_sql']
                    )
                    . '&nbsp;]';
            } else {
                $inline_edit_link = '';
            }
            $retval .= $inline_edit_link . $edit_link . $explain_link . $php_link
                . $refresh_link;
            $retval .= '</div>';

            $retval .= '</div>';
        }

        return $retval;
    }

    /**
     * Displays a link to the PHP documentation
     *
     * @param string $target anchor in documentation
     *
     * @return string  the html link
     *
     * @access public
     */
    public static function showPHPDocumentation($target): string
    {
        return self::showDocumentationLink(Core::getPHPDocLink($target));
    }

    /**
     * Displays a link to the documentation as an icon
     *
     * @param string $link   documentation link
     * @param string $target optional link target
     * @param bool   $bbcode optional flag indicating whether to output bbcode
     *
     * @return string the html link
     *
     * @access public
     */
    public static function showDocumentationLink($link, $target = 'documentation', $bbcode = false): string
    {
        if ($bbcode) {
            return '[a@' . $link . '@' . $target . '][dochelpicon][/a]';
        }

        return '<a href="' . $link . '" target="' . $target . '">'
            . self::getImage('b_help', __('Documentation'))
            . '</a>';
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
    ): ?string {
        global $table, $db;

        /**
         * Error message to be built.
         *
         * @var string $error_msg
         */
        $error_msg = '';

        // Checking for any server errors.
        if (empty($server_msg)) {
            $server_msg = $GLOBALS['dbi']->getError();
        }

        // Finding the query that failed, if not specified.
        if (empty($sql_query) && ! empty($GLOBALS['sql_query'])) {
            $sql_query = $GLOBALS['sql_query'];
        }
        $sql_query = trim($sql_query);

        /**
         * The lexer used for analysis.
         *
         * @var Lexer $lexer
         */
        $lexer = new Lexer($sql_query);

        /**
         * The parser used for analysis.
         *
         * @var Parser $parser
         */
        $parser = new Parser($lexer->list);

        /**
         * The errors found by the lexer and the parser.
         *
         * @var array $errors
         */
        $errors = ParserError::get(
            [
                $lexer,
                $parser,
            ]
        );

        if (empty($sql_query)) {
            $formatted_sql = '';
        } elseif (count($errors)) {
            $formatted_sql = htmlspecialchars($sql_query);
        } else {
            $formatted_sql = self::formatSql($sql_query, true);
        }

        $error_msg .= '<div class="alert alert-danger" role="alert"><h1>' . __('Error') . '</h1>';

        // For security reasons, if the MySQL refuses the connection, the query
        // is hidden so no details are revealed.
        if (! empty($sql_query) && ! mb_strstr($sql_query, 'connect')) {
            // Static analysis errors.
            if (! empty($errors)) {
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
            $error_msg .= '<p><strong>' . __('SQL query:') . '</strong>' . self::showCopyToClipboard(
                    $sql_query
                ) . "\n";
            $formattedSqlToLower = mb_strtolower($formatted_sql);

            // TODO: Show documentation for all statement types.
            if (mb_strstr($formattedSqlToLower, 'select')) {
                // please show me help to the error on select
                $error_msg .= MySQLDocumentation::show('SELECT');
            }

            if ($is_modify_link) {
                $_url_params = [
                    'sql_query' => $sql_query,
                    'show_query' => 1,
                ];
                if (strlen($table) > 0) {
                    $_url_params['db'] = $db;
                    $_url_params['table'] = $table;
                    $doedit_goto = '<a href="' . Url::getFromRoute('/table/sql', $_url_params) . '">';
                } elseif (strlen($db) > 0) {
                    $_url_params['db'] = $db;
                    $doedit_goto = '<a href="' . Url::getFromRoute('/database/sql', $_url_params) . '">';
                } else {
                    $doedit_goto = '<a href="' . Url::getFromRoute('/server/sql', $_url_params) . '">';
                }

                $error_msg .= $doedit_goto
                    . self::getIcon('b_edit', __('Edit'))
                    . '</a>';
            }

            $error_msg .= '    </p>' . "\n"
                . '<p>' . "\n"
                . $formatted_sql . "\n"
                . '</p>' . "\n";
        }

        // Display server's error.
        if (! empty($server_msg)) {
            $server_msg = preg_replace(
                "@((\015\012)|(\015)|(\012)){3,}@",
                "\n\n",
                $server_msg
            );

            // Adds a link to MySQL documentation.
            $error_msg .= '<p>' . "\n"
                . '    <strong>' . __('MySQL said: ') . '</strong>'
                . MySQLDocumentation::show('server-error-reference')
                . "\n"
                . '</p>' . "\n";

            // The error message will be displayed within a CODE segment.
            // To preserve original formatting, but allow word-wrapping,
            // a couple of replacements are done.
            // All non-single blanks and  TAB-characters are replaced with their
            // HTML-counterpart
            $server_msg = str_replace(
                [
                    '  ',
                    "\t",
                ],
                [
                    '&nbsp;&nbsp;',
                    '&nbsp;&nbsp;&nbsp;&nbsp;',
                ],
                $server_msg
            );

            // Replace line breaks
            $server_msg = nl2br($server_msg);

            $error_msg .= '<code>' . $server_msg . '</code><br>';
        }

        $error_msg .= '</div>';
        $_SESSION['Import_message']['message'] = $error_msg;

        if (! $exit) {
            return $error_msg;
        }

        /**
         * If this is an AJAX request, there is no "Back" link and
         * `Response()` is used to send the response.
         */
        $response = Response::getInstance();
        if ($response->isAjax()) {
            $response->setRequestStatus(false);
            $response->addJSON('message', $error_msg);
            exit;
        }

        if (! empty($back_url)) {
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
     * Returns an HTML IMG tag for a particular image from a theme
     *
     * The image name should match CSS class defined in icons.css.php
     *
     * @param string $image      The name of the file to get
     * @param string $alternate  Used to set 'alt' and 'title' attributes
     *                           of the image
     * @param array  $attributes An associative array of other attributes
     *
     * @return string an html IMG tag
     */
    public static function getImage($image, $alternate = '', array $attributes = []): string
    {
        $alternate = htmlspecialchars($alternate);

        if (isset($attributes['class'])) {
            $attributes['class'] = 'icon ic_' . $image . ' ' . $attributes['class'];
        } else {
            $attributes['class'] = 'icon ic_' . $image;
        }

        // set all other attributes
        $attr_str = '';
        foreach ($attributes as $key => $value) {
            if (! in_array($key, ['alt', 'title'])) {
                $attr_str .= ' ' . $key . '="' . $value . '"';
            }
        }

        // override the alt attribute
        $alt = $attributes['alt'] ?? $alternate;

        // override the title attribute
        $title = $attributes['title'] ?? $alternate;

        // generate the IMG tag
        $template = '<img src="themes/dot.gif" title="%s" alt="%s"%s>';
        return sprintf($template, $title, $alt, $attr_str);
    }

    /**
     * Displays a link, or a link with code to trigger POST request.
     *
     * POST is used in following cases:
     *
     * - URL is too long
     * - URL components are over Suhosin limits
     * - There is SQL query in the parameters
     *
     * @param string $url        the URL
     * @param string $message    the link message
     * @param mixed  $tag_params string: js confirmation; array: additional tag
     *                           params (f.e. style="")
     * @param string $target     target
     *
     * @return string  the results to be echoed or saved in an array
     */
    public static function linkOrButton(
        $url,
        $message,
        $tag_params = [],
        $target = ''
    ): string {
        $url_length = strlen($url);

        if (! is_array($tag_params)) {
            $tmp = $tag_params;
            $tag_params = [];
            if (! empty($tmp)) {
                $tag_params['onclick'] = 'return Functions.confirmLink(this, \''
                    . Sanitize::escapeJsString($tmp) . '\')';
            }
            unset($tmp);
        }
        if (! empty($target)) {
            $tag_params['target'] = $target;
            if ($target === '_blank' && strncmp($url, 'url.php?', 8) == 0) {
                $tag_params['rel'] = 'noopener noreferrer';
            }
        }

        // Suhosin: Check that each query parameter is not above maximum
        $in_suhosin_limits = true;
        if ($url_length <= $GLOBALS['cfg']['LinkLengthLimit']) {
            $suhosin_get_MaxValueLength = ini_get('suhosin.get.max_value_length');
            if ($suhosin_get_MaxValueLength) {
                $query_parts = Util::splitURLQuery($url);
                foreach ($query_parts as $query_pair) {
                    if (strpos($query_pair, '=') === false) {
                        continue;
                    }

                    [, $eachval] = explode('=', $query_pair);
                    if (strlen($eachval) > $suhosin_get_MaxValueLength) {
                        $in_suhosin_limits = false;
                        break;
                    }
                }
            }
        }

        $tag_params_strings = [];
        if (($url_length > $GLOBALS['cfg']['LinkLengthLimit'])
            || ! $in_suhosin_limits
            // Has as sql_query without a signature
            || (strpos($url, 'sql_query=') !== false && strpos($url, 'sql_signature=') === false)
            || strpos($url, 'view[as]=') !== false
        ) {
            $parts = explode('?', $url, 2);
            /*
             * The data-post indicates that client should do POST
             * this is handled in js/ajax.js
             */
            $tag_params_strings[] = 'data-post="' . ($parts[1] ?? '') . '"';
            $url = $parts[0];
            if (array_key_exists('class', $tag_params)
                && strpos($tag_params['class'], 'create_view') !== false
            ) {
                $url .= '?' . explode('&', $parts[1], 2)[0];
            }
        }

        foreach ($tag_params as $par_name => $par_value) {
            $tag_params_strings[] = $par_name . '="' . htmlspecialchars($par_value) . '"';
        }

        // no whitespace within an <a> else Safari will make it part of the link
        return '<a href="' . $url . '" '
            . implode(' ', $tag_params_strings) . '>'
            . $message . '</a>';
    }

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
     * @return string the  html content
     *
     * @access public
     *
     * @todo    use $pos from $_url_params
     */
    public static function getListNavigator(
        $count,
        $pos,
        array $_url_params,
        $script,
        $frame,
        $max_count,
        $name = 'pos',
        $classes = []
    ): string {
        // This is often coming from $cfg['MaxTableList'] and
        // people sometimes set it to empty string
        $max_count = intval($max_count);
        if ($max_count <= 0) {
            $max_count = 250;
        }

        $class = $frame === 'frame_navigation' ? ' class="ajax"' : '';

        $list_navigator_html = '';

        if ($max_count < $count) {
            $classes[] = 'pageselector';
            $list_navigator_html .= '<div class="' . implode(' ', $classes) . '">';

            if ($frame !== 'frame_navigation') {
                $list_navigator_html .= __('Page number:');
            }

            // Move to the beginning or to the previous page
            if ($pos > 0) {
                $caption1 = '';
                $caption2 = '';
                if (Util::showIcons('TableNavigationLinksMode')) {
                    $caption1 .= '&lt;&lt; ';
                    $caption2 .= '&lt; ';
                }
                if (Util::showText('TableNavigationLinksMode')) {
                    $caption1 .= _pgettext('First page', 'Begin');
                    $caption2 .= _pgettext('Previous page', 'Previous');
                }
                $title1 = ' title="' . _pgettext('First page', 'Begin') . '"';
                $title2 = ' title="' . _pgettext('Previous page', 'Previous') . '"';

                $_url_params[$name] = 0;
                $list_navigator_html .= '<a' . $class . $title1 . ' href="' . $script
                    . Url::getCommon($_url_params, '&') . '">' . $caption1
                    . '</a>';

                $_url_params[$name] = $pos - $max_count;
                $list_navigator_html .= ' <a' . $class . $title2
                    . ' href="' . $script . Url::getCommon($_url_params, '&') . '">'
                    . $caption2 . '</a>';
            }

            $list_navigator_html .= '<form action="' . $script
                . '" method="post">';

            $list_navigator_html .= Url::getHiddenInputs($_url_params);
            $list_navigator_html .= Util::pageselector(
                $name,
                $max_count,
                Util::getPageFromPosition($pos, $max_count),
                ceil($count / $max_count)
            );
            $list_navigator_html .= '</form>';

            if ($pos + $max_count < $count) {
                $caption3 = '';
                $caption4 = '';
                if (Util::showText('TableNavigationLinksMode')) {
                    $caption3 .= _pgettext('Next page', 'Next');
                    $caption4 .= _pgettext('Last page', 'End');
                }
                if (Util::showIcons('TableNavigationLinksMode')) {
                    $caption3 .= ' &gt;';
                    $caption4 .= ' &gt;&gt;';
                }
                $title3 = ' title="' . _pgettext('Next page', 'Next') . '"';
                $title4 = ' title="' . _pgettext('Last page', 'End') . '"';

                $_url_params[$name] = $pos + $max_count;
                $list_navigator_html .= '<a' . $class . $title3 . ' href="' . $script
                    . Url::getCommon($_url_params, '&') . '" >' . $caption3
                    . '</a>';

                $_url_params[$name] = floor($count / $max_count) * $max_count;
                if ($_url_params[$name] == $count) {
                    $_url_params[$name] = $count - $max_count;
                }

                $list_navigator_html .= ' <a' . $class . $title4
                    . ' href="' . $script . Url::getCommon($_url_params, '&') . '" >'
                    . $caption4 . '</a>';
            }
            $list_navigator_html .= '</div>' . "\n";
        }

        return $list_navigator_html;
    }

    /**
     * format sql strings
     *
     * @param string $sqlQuery raw SQL string
     * @param bool   $truncate truncate the query if it is too long
     *
     * @return string the formatted sql
     *
     * @global array  $cfg the configuration array
     *
     * @access public
     */
    public static function formatSql($sqlQuery, $truncate = false): string
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
    }

    /**
     * This function processes the datatypes supported by the DB,
     * as specified in Types->getColumns() and returns an HTML snippet that
     * creates a drop-down list.
     *
     * @param string $selected The value to mark as selected in HTML mode
     */
    public static function getSupportedDatatypes($selected): string
    {
        // NOTE: the SELECT tag is not included in this snippet.
        $retval = '';

        foreach ($GLOBALS['dbi']->types->getColumns() as $key => $value) {
            if (is_array($value)) {
                $retval .= "<optgroup label='" . htmlspecialchars($key) . "'>";
                foreach ($value as $subvalue) {
                    if ($subvalue == $selected) {
                        $retval .= sprintf(
                            '<option selected="selected" title="%s">%s</option>',
                            $GLOBALS['dbi']->types->getTypeDescription($subvalue),
                            $subvalue
                        );
                    } elseif ($subvalue === '-') {
                        $retval .= '<option disabled="disabled">';
                        $retval .= $subvalue;
                        $retval .= '</option>';
                    } else {
                        $retval .= sprintf(
                            '<option title="%s">%s</option>',
                            $GLOBALS['dbi']->types->getTypeDescription($subvalue),
                            $subvalue
                        );
                    }
                }
                $retval .= '</optgroup>';
            } elseif ($selected == $value) {
                $retval .= sprintf(
                    '<option selected="selected" title="%s">%s</option>',
                    $GLOBALS['dbi']->types->getTypeDescription($value),
                    $value
                );
            } else {
                $retval .= sprintf(
                    '<option title="%s">%s</option>',
                    $GLOBALS['dbi']->types->getTypeDescription($value),
                    $value
                );
            }
        }
        return $retval;
    }
}
