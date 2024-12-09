<?php
/**
 * HTML Generator
 */

declare(strict_types=1);

namespace PhpMyAdmin\Html;

use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\MessageType;
use PhpMyAdmin\Profiling;
use PhpMyAdmin\Providers\ServerVariables\ServerVariablesProvider;
use PhpMyAdmin\Query\Compatibility;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\SqlParser\Lexer;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Utils\Error as ParserError;
use PhpMyAdmin\Template;
use PhpMyAdmin\TypeClass;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

use function __;
use function _pgettext;
use function addslashes;
use function array_key_exists;
use function ceil;
use function count;
use function explode;
use function htmlentities;
use function htmlspecialchars;
use function implode;
use function in_array;
use function ini_get;
use function is_array;
use function is_string;
use function json_encode;
use function mb_strlen;
use function mb_strtolower;
use function mb_substr;
use function nl2br;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function stripos;
use function strlen;
use function trim;

use const ENT_COMPAT;
use const JSON_HEX_TAG;

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
     */
    public static function showCopyToClipboard(string $text): string
    {
        return '  <a href="#" class="copyQueryBtn" data-text="'
            . htmlspecialchars($text) . '">' . __('Copy') . '</a>';
    }

    /**
     * Get a link to variable documentation
     *
     * @param string      $name       The variable name
     * @param bool        $useMariaDB Use only MariaDB documentation
     * @param string|null $text       (optional) The text for the link
     *
     * @return string link or empty string
     */
    public static function linkToVarDocumentation(
        string $name,
        bool $useMariaDB = false,
        string|null $text = null,
    ): string {
        $kbs = ServerVariablesProvider::getImplementation();
        $link = $useMariaDB ? $kbs->getDocLinkByNameMariaDb($name) : $kbs->getDocLinkByNameMysql($name);
        $link = $link !== null ? Core::linkURL($link) : $link;

        return MySQLDocumentation::show($name, false, $link, $text);
    }

    /**
     * Returns HTML code for a tooltip
     *
     * @param string $message the message for the tooltip
     */
    public static function showHint(string $message): string
    {
        if (Config::getInstance()->settings['ShowHint']) {
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
     * returns html code for db link to default db page
     *
     * @param string $database database
     *
     * @return string  html link to default db page
     */
    public static function getDbLink(string $database): string
    {
        if ($database === '') {
            if (Current::$database === '') {
                return '';
            }

            $database = Current::$database;
        }

        $scriptName = Util::getScriptNameForOption(Config::getInstance()->settings['DefaultTabDatabase'], 'database');

        return '<a href="'
            . $scriptName
            . Url::getCommon(['db' => $database], ! str_contains($scriptName, '?') ? '?' : '&')
            . '" title="'
            . htmlspecialchars(
                sprintf(
                    __('Jump to database “%s”.'),
                    $database,
                ),
            )
            . '">' . htmlspecialchars($database) . '</a>';
    }

    /**
     * Returns an HTML IMG tag for a particular icon from a theme,
     * which may be an actual file or an icon from a sprite.
     * This function takes into account the ActionLinksMode
     * configuration setting and wraps the image tag in a span tag.
     *
     * @param string $icon         name of icon file
     * @param string $alternate    alternate text
     * @param bool   $forceText    whether to force alternate text to be displayed
     * @param bool   $menuIcon     whether this icon is for the menu bar or not
     * @param string $controlParam which directive controls the display
     *
     * @return string an html snippet
     */
    public static function getIcon(
        string $icon,
        string $alternate = '',
        bool $forceText = false,
        bool $menuIcon = false,
        string $controlParam = 'ActionLinksMode',
    ): string {
        $includeIcon = $includeText = false;
        if (Util::showIcons($controlParam)) {
            $includeIcon = true;
        }

        if ($forceText || Util::showText($controlParam)) {
            $includeText = true;
        }

        // Sometimes use a span (we rely on this in js/sql.js). But for menu bar
        // we don't need a span
        $button = $menuIcon ? '' : '<span class="text-nowrap">';
        if ($includeIcon) {
            $button .= self::getImage($icon, $alternate);
        }

        if ($includeIcon && $includeText) {
            $button .= '&nbsp;';
        }

        if ($includeText) {
            $button .= $alternate;
        }

        $button .= $menuIcon ? '' : '</span>';

        return $button;
    }

    /**
     * Returns information about SSL status for current connection
     */
    public static function getServerSSL(): string
    {
        $config = Config::getInstance();
        $server = $config->selectedServer;
        $class = 'text-danger';
        if (! $server['ssl']) {
            $message = __('SSL is not being used');
            if (
                ! empty($server['socket']) || in_array($server['host'], $config->settings['MysqlSslWarningSafeHosts'])
            ) {
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
            'ssl',
        );
    }

    /**
     * Returns default function for a particular column.
     *
     * @return string An HTML snippet of a dropdown list with function
     *                names appropriate for the requested column.
     */
    public static function getDefaultFunctionForField(
        string $trueType,
        bool $firstTimestamp,
        string|null $defaultValue,
        string $extra,
        bool $isNull,
        string $key,
        string $type,
        bool $insertMode,
    ): string {
        // For uuid field, no default function
        if ($trueType === 'uuid') {
            return '';
        }

        $defaultFunction = '';

        $dbi = DatabaseInterface::getInstance();
        // Can we get field class based values?
        $currentClass = $dbi->types->getTypeClass($trueType);
        $config = Config::getInstance();
        if (
            $currentClass !== TypeClass::Unknown
            && isset($config->settings['DefaultFunctions']['FUNC_' . $currentClass->value])
        ) {
            $defaultFunction = $config->settings['DefaultFunctions']['FUNC_' . $currentClass->value];
            // Change the configured default function to include the ST_ prefix with MySQL 5.6 and later.
            // It needs to match the function listed in the select html element.
            if (
                $currentClass === TypeClass::Spatial &&
                $dbi->getVersion() >= 50600 &&
                stripos($defaultFunction, 'ST_') !== 0
            ) {
                $defaultFunction = 'ST_' . $defaultFunction;
            }
        }

        // what function defined as default?
        // for the first timestamp we don't set the default function
        // if there is a default value for the timestamp
        // (not including CURRENT_TIMESTAMP)
        // and the column does not have the
        // ON UPDATE DEFAULT TIMESTAMP attribute.
        if (
            $trueType === 'timestamp'
            && $firstTimestamp
            && ($defaultValue === null || $defaultValue === '')
            && $extra !== 'on update CURRENT_TIMESTAMP'
            && ! $isNull
        ) {
            $defaultFunction = $config->settings['DefaultFunctions']['first_timestamp'];
        }

        // For primary keys of type char(36) or varchar(36) UUID if the default
        // function
        // Only applies to insert mode, as it would silently trash data on updates.
        if (
            $insertMode
            && $key === 'PRI'
            && ($type === 'char(36)' || $type === 'varchar(36)')
        ) {
            return $config->settings['DefaultFunctions']['FUNC_UUID'];
        }

        return $defaultFunction;
    }

    /**
     * Creates a dropdown box with MySQL functions for a particular column.
     *
     * @return string An HTML snippet of a dropdown list with function names appropriate for the requested column.
     */
    public static function getFunctionsForField(string $defaultFunction, mixed $foreignField = null): string
    {
        // Create the output
        $retval = '<option></option>' . "\n";
        // loop on the dropdown array and print all available options for that
        // field.
        $functions = DatabaseInterface::getInstance()->types->getAllFunctions();
        foreach ($functions as $function) {
            $retval .= '<option';
            if ($function === $defaultFunction && $foreignField === null) {
                $retval .= ' selected="selected"';
            }

            $retval .= '>' . $function . '</option>' . "\n";
        }

        $retval .= '<option value="PHP_PASSWORD_HASH" title="';
        $retval .= htmlentities(__('The PHP function password_hash() with default options.'), ENT_COMPAT);
        $retval .= '">' . __('password_hash() PHP function') . '</option>' . "\n";

        return $retval;
    }

    /**
     * Renders a single link for the top of the navigation panel
     *
     * @param string $link   The url for the link
     * @param string $text   The text to display and use for title attributes
     * @param string $icon   The filename of the icon to show
     * @param string $linkId Value to use for the ID attribute
     *
     * @return string HTML code for one link
     */
    public static function getNavigationLink(
        string $link,
        string $text,
        string $icon,
        string $linkId = '',
    ): string {
        $retval = '<a href="' . $link . '"';
        if ($linkId !== '') {
            $retval .= ' id="' . $linkId . '"';
        }

        $retval .= ' title="' . $text . '">';
        $retval .= self::getImage($icon, $text);
        $retval .= '</a>';

        return $retval;
    }

    /**
     * @return array<string, int|string>
     * @psalm-return array{pos: int, unlim_num_rows: int, rows: int, sql_query: string}
     */
    public static function getStartAndNumberOfRowsFieldsetData(string $sqlQuery): array
    {
        if (isset($_REQUEST['session_max_rows'])) {
            $rows = (int) $_REQUEST['session_max_rows'];
        } elseif (isset($_SESSION['tmpval']['max_rows']) && $_SESSION['tmpval']['max_rows'] !== 'all') {
            $rows = (int) $_SESSION['tmpval']['max_rows'];
        } else {
            $rows = Config::getInstance()->settings['MaxRows'];
            $_SESSION['tmpval']['max_rows'] = $rows;
        }

        $numberOfLine = (int) $_REQUEST['unlim_num_rows'];
        if (isset($_REQUEST['pos'])) {
            $pos = (int) $_REQUEST['pos'];
        } elseif (isset($_SESSION['tmpval']['pos'])) {
            $pos = (int) $_SESSION['tmpval']['pos'];
        } else {
            $pos = ((int) ceil($numberOfLine / $rows) - 1) * $rows;
            $_SESSION['tmpval']['pos'] = $pos;
        }

        return ['pos' => $pos, 'unlim_num_rows' => $numberOfLine, 'rows' => $rows, 'sql_query' => $sqlQuery];
    }

    /**
     * Prepare the message and the query
     * usually the message is the result of the query executed
     *
     * @param Message|string $message  the message to display
     * @param string|null    $sqlQuery the query to display
     *
     * @throws Throwable
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public static function getMessage(
        Message|string $message,
        string|null $sqlQuery = null,
        MessageType $type = MessageType::Notice,
    ): string {
        $retval = '';

        if ($sqlQuery === null) {
            if (! empty($GLOBALS['display_query'])) {
                $sqlQuery = (string) $GLOBALS['display_query'];
            } elseif (! empty($GLOBALS['sql_query'])) {
                $sqlQuery = (string) $GLOBALS['sql_query'];
            } else {
                $sqlQuery = '';
            }
        }

        $config = Config::getInstance();
        $renderSql = $config->settings['ShowSQL'] == true && $sqlQuery !== '' && $sqlQuery !== ';';

        if (isset($GLOBALS['using_bookmark_message'])) {
            $retval .= $GLOBALS['using_bookmark_message']->getDisplay();
            unset($GLOBALS['using_bookmark_message']);
        }

        if (is_string($message)) {
            $message = new Message($message, $type);
        }

        if (isset($GLOBALS['special_message'])) {
            $message->addText($GLOBALS['special_message']);
            unset($GLOBALS['special_message']);
        }

        if (! $renderSql) {
            return $retval . $message->getDisplay();
        }

        $retval .= '<div class="card mb-3 result_query">' . "\n";

        $message->isDisplayed(true);
        $retval .= '<div class="alert alert-' . $message->getContext();
        $retval .= ' border-top-0 border-start-0 border-end-0 rounded-bottom-0 mb-0" role="alert">' . "\n";
        $retval .= '  ' . $message->getMessage() . "\n";
        $retval .= '</div>' . "\n";

        // Html format the query to be displayed
        // If we want to show some sql code it is easiest to create it here
        /* SQL-Parser-Analyzer */

        if (! empty($GLOBALS['show_as_php'])) {
            $newLine = '\\n"<br>' . "\n" . '&nbsp;&nbsp;&nbsp;&nbsp;. "';
            $queryBase = htmlspecialchars(addslashes($sqlQuery));
            $queryBase = preg_replace('/((\015\012)|(\015)|(\012))/', $newLine, $queryBase);
            $queryBase = '<code class="php" dir="ltr"><pre>' . "\n"
                . '$sql = "' . $queryBase . '";' . "\n"
                . '</pre></code>';
        } else {
            $queryBase = self::formatSql($sqlQuery, true);
        }

        // Prepares links that may be displayed to edit/explain the query
        // (don't go to default pages, we must go to the page
        // where the query box is available)

        // Basic url query part
        $urlParams = [];
        if (Current::$database !== '') {
            $urlParams['db'] = Current::$database;
            if (Current::$table !== '') {
                $urlParams['table'] = Current::$table;
                $editLinkRoute = '/table/sql';
            } else {
                $editLinkRoute = '/database/sql';
            }
        } else {
            $editLinkRoute = '/server/sql';
        }

        // Want to have the query explained
        // but only explain a SELECT (that has not been explained)
        /* SQL-Parser-Analyzer */
        $explainLink = '';
        $queryTooBig = mb_strlen($sqlQuery) > $config->settings['MaxCharactersInDisplayedSQL'];
        $isSelect = preg_match('@^SELECT[[:space:]]+@i', $sqlQuery) === 1;
        if (! empty($config->settings['SQLQuery']['Explain']) && ! $queryTooBig) {
            $explainParams = $urlParams;
            if ($isSelect) {
                $explainParams['sql_query'] = 'EXPLAIN ' . $sqlQuery;
                $explainLink = '<div class="col-auto">'
                    . self::linkOrButton(
                        Url::getFromRoute('/import', $explainParams),
                        null,
                        __('Explain SQL'),
                        ['class' => 'btn btn-link'],
                    ) . '</div>' . "\n";
            } elseif (preg_match('@^EXPLAIN[[:space:]]+SELECT[[:space:]]+@i', $sqlQuery) === 1) {
                $explainParams['sql_query'] = mb_substr($sqlQuery, 8);
                $explainLink = '<div class="col-auto">'
                    . self::linkOrButton(
                        Url::getFromRoute('/import', $explainParams),
                        null,
                        __('Skip Explain SQL'),
                        ['class' => 'btn btn-link'],
                    ) . '</div>' . "\n";
            }
        }

        $urlParams['sql_query'] = $sqlQuery;
        $urlParams['show_query'] = 1;

        // even if the query is big and was truncated, offer the chance
        // to edit it (unless it's enormous, see linkOrButton() )
        if (! empty($config->settings['SQLQuery']['Edit']) && empty($GLOBALS['show_as_php'])) {
            $editLink = '<div class="col-auto">'
                . self::linkOrButton(
                    Url::getFromRoute($editLinkRoute, $urlParams),
                    null,
                    __('Edit'),
                    ['class' => 'btn btn-link'],
                )
                . '</div>' . "\n";
        } else {
            $editLink = '';
        }

        // Also we would like to get the SQL formed in some nice
        // php-code
        if (! empty($config->settings['SQLQuery']['ShowAsPHP']) && ! $queryTooBig) {
            if (! empty($GLOBALS['show_as_php'])) {
                $phpLink = '<div class="col-auto">'
                    . self::linkOrButton(
                        Url::getFromRoute('/import', $urlParams),
                        null,
                        __('Without PHP code'),
                        ['class' => 'btn btn-link'],
                    )
                    . '</div>' . "\n";

                $phpLink .= '<div class="col-auto">'
                    . self::linkOrButton(
                        Url::getFromRoute('/import', $urlParams),
                        null,
                        __('Submit query'),
                        ['class' => 'btn btn-link'],
                    )
                    . '</div>' . "\n";
            } else {
                $phpParams = $urlParams;
                $phpParams['show_as_php'] = 1;
                $phpLink = '<div class="col-auto">'
                    . self::linkOrButton(
                        Url::getFromRoute('/import', $phpParams),
                        null,
                        __('Create PHP code'),
                        ['class' => 'btn btn-link'],
                    )
                    . '</div>' . "\n";
            }
        } else {
            $phpLink = '';
        }

        // Refresh query
        if (
            ! empty($config->settings['SQLQuery']['Refresh'])
            && ! isset($GLOBALS['show_as_php']) // 'Submit query' does the same
            && preg_match('@^(SELECT|SHOW)[[:space:]]+@i', $sqlQuery) === 1
        ) {
            $refreshLink = Url::getFromRoute('/sql', $urlParams);
            $refreshLink = '<div class="col-auto">'
                . self::linkOrButton(
                    $refreshLink,
                    null,
                    __('Refresh'),
                    ['class' => 'btn btn-link'],
                ) . '</div>' . "\n";
        } else {
            $refreshLink = '';
        }

        $retval .= '<div class="card-body sqlOuter">';
        $retval .= $queryBase;
        $retval .= '</div>' . "\n";

        $retval .= '<div class="card-footer tools d-print-none">' . "\n";
        $retval .= '<div class="row align-items-center">' . "\n";
        $retval .= '<div class="col-auto">' . "\n";
        $retval .= '<form action="' . Url::getFromRoute(
            '/sql',
            ['db' => Current::$database, 'table' => Current::$table],
        )
            . '" method="post" class="disableAjax">' . "\n";
        $retval .= Url::getHiddenInputs(Current::$database, Current::$table) . "\n";
        $retval .= '<input type="hidden" name="sql_query" value="'
            . htmlspecialchars($sqlQuery) . '">' . "\n";

        // avoid displaying a Profiling checkbox that could
        // be checked, which would re-execute an INSERT, for example
        if ($refreshLink !== '' && Profiling::isSupported(DatabaseInterface::getInstance())) {
            $retval .= '<input type="hidden" name="profiling_form" value="1">' . "\n";
            $retval .= '<div class="form-check form-switch">' . "\n";
            $retval .= '<input type="checkbox" name="profiling" id="profilingCheckbox" role="switch"';
            $retval .= ' class="form-check-input autosubmit"';
            $retval .= isset($_SESSION['profiling']) ? ' checked>' . "\n" : '>' . "\n";
            $retval .= '<label class="form-check-label" for="profilingCheckbox">' . __('Profiling') . '</label>' . "\n";
            $retval .= '</div>' . "\n";
        }

        $retval .= '</form></div>' . "\n";

        /**
         * TODO: Should we have $cfg['SQLQuery']['InlineEdit']?
         */
        if (! empty($config->settings['SQLQuery']['Edit']) && ! $queryTooBig && empty($GLOBALS['show_as_php'])) {
            $inlineEditLink = '<div class="col-auto">'
                . self::linkOrButton(
                    '#',
                    null,
                    _pgettext('Inline edit query', 'Edit inline'),
                    ['class' => 'btn btn-link inline_edit_sql'],
                )
                . '</div>' . "\n";
        } else {
            $inlineEditLink = '';
        }

        $retval .= $inlineEditLink . $editLink . $explainLink . $phpLink
            . $refreshLink;
        $retval .= '</div></div>';

        $retval .= '</div>';

        return $retval;
    }

    /**
     * Displays a link to the PHP documentation
     *
     * @param string $target anchor in documentation
     *
     * @return string  the html link
     */
    public static function showPHPDocumentation(string $target): string
    {
        return self::showDocumentationLink(Core::getPHPDocLink($target));
    }

    /**
     * Displays a link to the documentation as an icon
     *
     * @param string $link            documentation link
     * @param string $target          optional link target
     * @param bool   $bbcode          optional flag indicating whether to output bbcode
     * @param bool   $disableTabIndex optional flag indicating that a negative tabindex should be set on the link
     *
     * @return string the html link
     */
    public static function showDocumentationLink(
        string $link,
        string $target = 'documentation',
        bool $bbcode = false,
        bool $disableTabIndex = false,
    ): string {
        if ($bbcode) {
            return '[a@' . $link . '@' . $target . '][dochelpicon][/a]';
        }

        return '<a href="' . $link . '" target="' . $target . '"' . ($disableTabIndex ? ' tabindex="-1"' : '') . '>'
            . self::getImage('b_help', __('Documentation'))
            . '</a>';
    }

    /**
     * Displays a MySQL error message in the main panel when $exit is true.
     * Returns the error message otherwise.
     *
     * @param string $serverMessage Server's error message.
     * @param string $sqlQuery      The SQL query that failed.
     * @param bool   $isModifyLink  Whether to show a "modify" link or not.
     * @param string $backUrl       URL for the "back" link (full path is not required).
     * @param bool   $exit          Whether execution should be stopped or the error message should be returned.
     *
     * @psalm-return ($exit is true ? never : string|null)
     *
     * @global string $table The current table.
     * @global string $db    The current database.
     */
    public static function mysqlDie(
        string $serverMessage = '',
        string $sqlQuery = '',
        bool $isModifyLink = true,
        string $backUrl = '',
        bool $exit = true,
    ): string|null {
        /**
         * Error message to be built.
         */
        $errorMessage = '';

        // Checking for any server errors.
        if ($serverMessage === '') {
            $serverMessage = DatabaseInterface::getInstance()->getError();
        }

        // Finding the query that failed, if not specified.
        if ($sqlQuery === '' && ! empty($GLOBALS['sql_query'])) {
            $sqlQuery = $GLOBALS['sql_query'];
        }

        $sqlQuery = trim($sqlQuery);

        /**
         * The lexer used for analysis.
         */
        $lexer = new Lexer($sqlQuery);

        /**
         * The parser used for analysis.
         */
        $parser = new Parser($lexer->list);

        /**
         * The errors found by the lexer and the parser.
         */
        $errors = ParserError::get(
            [$lexer, $parser],
        );

        if ($sqlQuery === '') {
            $formattedSql = '';
        } elseif ($errors !== []) {
            $formattedSql = htmlspecialchars($sqlQuery);
        } else {
            $formattedSql = self::formatSql($sqlQuery, true);
        }

        $errorMessage .= '<div class="alert alert-danger" role="alert"><h1>' . __('Error') . '</h1>';

        // For security reasons, if the MySQL refuses the connection, the query
        // is hidden so no details are revealed.
        if ($sqlQuery !== '' && ! str_contains($sqlQuery, 'connect')) {
            // Static analysis errors.
            if ($errors !== []) {
                $errorMessage .= '<p><strong>' . __('Static analysis:')
                    . '</strong></p>';
                $errorMessage .= '<p>' . sprintf(
                    __('%d errors were found during analysis.'),
                    count($errors),
                ) . '</p>';
                $errorMessage .= '<p><ol>';
                $errorMessage .= implode(
                    ParserError::format(
                        $errors,
                        '<li>%2$s (near "%4$s" at position %5$d)</li>',
                    ),
                );
                $errorMessage .= '</ol></p>';
            }

            // Display the SQL query and link to MySQL documentation.
            $errorMessage .= '<p><strong>' . __('SQL query:') . '</strong>' . self::showCopyToClipboard(
                $sqlQuery,
            ) . "\n";
            $formattedSqlToLower = mb_strtolower($formattedSql);

            // TODO: Show documentation for all statement types.
            if (str_contains($formattedSqlToLower, 'select')) {
                // please show me help to the error on select
                $errorMessage .= MySQLDocumentation::show('SELECT');
            }

            if ($isModifyLink) {
                $urlParams = ['sql_query' => $sqlQuery, 'show_query' => 1];
                if (Current::$table !== '') {
                    $urlParams['db'] = Current::$database;
                    $urlParams['table'] = Current::$table;
                    $doEditGoto = '<a href="' . Url::getFromRoute('/table/sql', $urlParams) . '">';
                } elseif (Current::$database !== '') {
                    $urlParams['db'] = Current::$database;
                    $doEditGoto = '<a href="' . Url::getFromRoute('/database/sql', $urlParams) . '">';
                } else {
                    $doEditGoto = '<a href="' . Url::getFromRoute('/server/sql', $urlParams) . '">';
                }

                $errorMessage .= $doEditGoto
                    . self::getIcon('b_edit', __('Edit'))
                    . '</a>';
            }

            $errorMessage .= '    </p>' . "\n"
                . '<p>' . "\n"
                . $formattedSql . "\n"
                . '</p>' . "\n";
        }

        // Display server's error.
        if ($serverMessage !== '') {
            $serverMessage = (string) preg_replace("@((\015\012)|(\015)|(\012)){3,}@", "\n\n", $serverMessage);

            // Adds a link to MySQL documentation.
            $errorMessage .= '<p>' . "\n"
                . '    <strong>' . __('MySQL said: ') . '</strong>'
                . MySQLDocumentation::show('server-error-reference')
                . "\n"
                . '</p>' . "\n";

            // The error message will be displayed within a CODE segment.
            // To preserve original formatting, but allow word-wrapping,
            // a couple of replacements are done.
            // All non-single blanks and  TAB-characters are replaced with their
            // HTML-counterpart
            $serverMessage = str_replace(
                ['  ', "\t"],
                ['&nbsp;&nbsp;', '&nbsp;&nbsp;&nbsp;&nbsp;'],
                $serverMessage,
            );

            // Replace line breaks
            $serverMessage = nl2br($serverMessage);

            $errorMessage .= '<code>' . $serverMessage . '</code><br>';
        }

        $errorMessage .= '</div>';
        $_SESSION['Import_message']['message'] = $errorMessage;

        if (! $exit) {
            return $errorMessage;
        }

        /**
         * If this is an AJAX request, there is no "Back" link and
         * `Response()` is used to send the response.
         */
        $response = ResponseRenderer::getInstance();
        if ($response->isAjax()) {
            $response->setRequestStatus(false);
            $response->addJSON('message', $errorMessage);
            $response->callExit();
        }

        if ($backUrl !== '') {
            if (str_contains($backUrl, '?')) {
                $backUrl .= '&amp;no_history=true';
            } else {
                $backUrl .= '?no_history=true';
            }

            $_SESSION['Import_message']['go_back_url'] = $backUrl;

            $errorMessage .= '<div class="card"><div class="card-body">'
                . '[ <a href="' . $backUrl . '">' . __('Back') . '</a> ]'
                . '</div></div>' . "\n\n";
        }

        $response->addHTML($errorMessage);
        $response->callExit();
    }

    /**
     * Returns an HTML IMG tag for a particular image from a theme
     *
     * The image name should match CSS class defined in icons.css.php
     *
     * @param string  $image      The name of the file to get
     * @param string  $alternate  Used to set 'alt' and 'title' attributes
     *                            of the image
     * @param mixed[] $attributes An associative array of other attributes
     *
     * @return string an html IMG tag
     */
    public static function getImage(string $image, string $alternate = '', array $attributes = []): string
    {
        $alternate = htmlspecialchars($alternate);

        if (isset($attributes['class'])) {
            $attributes['class'] = 'icon ic_' . $image . ' ' . $attributes['class'];
        } else {
            $attributes['class'] = 'icon ic_' . $image;
        }

        // set all other attributes
        $attributeString = '';
        foreach ($attributes as $key => $value) {
            if (in_array($key, ['alt', 'title'], true)) {
                continue;
            }

            $attributeString .= ' ' . $key . '="' . $value . '"';
        }

        // override the alt attribute
        $alt = $attributes['alt'] ?? $alternate;

        // override the title attribute
        $title = $attributes['title'] ?? $alternate;

        // generate the IMG tag
        $template = '<img src="themes/dot.gif" title="%s" alt="%s"%s>';

        return sprintf($template, $title, $alt, $attributeString);
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
     * @param string                        $urlPath   the URL
     * @param array<int|string, mixed>|null $urlParams URL parameters
     * @param string                        $message   the link message
     * @param string|array<string, string>  $tagParams string: js confirmation;
     *                                                 array: additional tag params (f.e. style="")
     * @param string                        $target    target
     *
     * @return string  the results to be echoed or saved in an array
     */
    public static function linkOrButton(
        string $urlPath,
        array|null $urlParams,
        string $message,
        string|array $tagParams = [],
        string $target = '',
        bool $respectUrlLengthLimit = true,
    ): string {
        $url = $urlPath;
        if (is_array($urlParams)) {
            $url = $urlPath . Url::getCommon($urlParams, str_contains($urlPath, '?') ? '&' : '?', false);
        }

        if (is_string($tagParams)) {
            $tagParams = $tagParams !== '' ?
                ['onclick' => 'return window.pmaConfirmLink(this, ' . json_encode($tagParams, JSON_HEX_TAG) . ')'] :
                [];
        }

        if ($target !== '') {
            $tagParams['target'] = $target;
            if ($target === '_blank' && str_starts_with($url, 'index.php?route=/url&url=')) {
                $tagParams['rel'] = 'noopener noreferrer';
            }
        }

        // Suhosin: Check that each query parameter is not above maximum
        $inSuhosinLimits = true;
        $config = Config::getInstance();
        if (strlen($url) <= $config->settings['LinkLengthLimit']) {
            $suhosinGetMaxValueLength = ini_get('suhosin.get.max_value_length');
            if ($suhosinGetMaxValueLength) {
                $queryParts = Util::splitURLQuery($url);
                foreach ($queryParts as $queryPair) {
                    if (! str_contains($queryPair, '=')) {
                        continue;
                    }

                    [, $eachValue] = explode('=', $queryPair);
                    if (strlen($eachValue) > $suhosinGetMaxValueLength) {
                        $inSuhosinLimits = false;
                        break;
                    }
                }
            }
        }

        $tagParamsStrings = [];
        $isDataPostFormatSupported = (strlen($url) > $config->settings['LinkLengthLimit'])
            || ! $inSuhosinLimits
            || (
                // Has as sql_query without a signature, to be accepted it needs to be sent using POST
                str_contains($url, 'sql_query=') && ! str_contains($url, 'sql_signature=')
            )
            || str_contains($url, 'view[as]=');
        if ($respectUrlLengthLimit && $isDataPostFormatSupported) {
            $parts = explode('?', $url, 2);
            // The data-post indicates that client should do POST this is handled in js/ajax.js
            $tagParamsStrings[] = 'data-post="' . ($parts[1] ?? '') . '"';
            $url = $parts[0];
            if (array_key_exists('class', $tagParams) && str_contains($tagParams['class'], 'create_view')) {
                $url .= '?' . explode('&', $parts[1], 2)[0];
            }
        } else {
            $url = $urlPath;
            if (is_array($urlParams)) {
                $url = $urlPath . Url::getCommon($urlParams, str_contains($urlPath, '?') ? '&' : '?');
            }
        }

        foreach ($tagParams as $paramName => $paramValue) {
            $tagParamsStrings[] = $paramName . '="' . htmlspecialchars($paramValue) . '"';
        }

        // no whitespace within an <a> else Safari will make it part of the link
        return '<a href="' . $url . '" '
            . implode(' ', $tagParamsStrings) . '>'
            . $message . '</a>';
    }

    /**
     * Prepare navigation for a list
     *
     * @param int      $count     number of elements in the list
     * @param int      $pos       current position in the list
     * @param mixed[]  $urlParams url parameters
     * @param string   $script    script name for form target
     * @param string   $frame     target frame
     * @param int      $maxCount  maximum number of elements to display from
     *                             the list
     * @param string   $name      the name for the request parameter
     * @param string[] $classes   additional classes for the container
     *
     * @return string the  html content
     *
     * @todo    use $pos from $_url_params
     */
    public static function getListNavigator(
        int $count,
        int $pos,
        array $urlParams,
        string $script,
        string $frame,
        int $maxCount,
        string $name = 'pos',
        array $classes = [],
    ): string {
        if ($maxCount <= 0) {
            $maxCount = 250;
        }

        $pageSelector = '';
        if ($maxCount < $count) {
            $classes[] = 'pageselector';

            $pageSelector = Util::pageselector(
                $name,
                $maxCount,
                Util::getPageFromPosition($pos, $maxCount),
                (int) ceil($count / $maxCount),
            );
        }

        return (new Template())->render('list_navigator', [
            'count' => $count,
            'max_count' => $maxCount,
            'classes' => $classes,
            'frame' => $frame,
            'position' => $pos,
            'script' => $script,
            'url_params' => $urlParams,
            'param_name' => $name,
            'page_selector' => $pageSelector,
        ]);
    }

    /**
     * format sql strings
     *
     * @param string $sqlQuery raw SQL string
     * @param bool   $truncate truncate the query if it is too long
     *
     * @return string the formatted sql
     */
    public static function formatSql(string $sqlQuery, bool $truncate = false): string
    {
        $config = Config::getInstance();
        if ($truncate && mb_strlen($sqlQuery) > $config->settings['MaxCharactersInDisplayedSQL']) {
            $sqlQuery = mb_substr($sqlQuery, 0, $config->settings['MaxCharactersInDisplayedSQL']) . '[...]';
        }

        return '<code class="sql" dir="ltr"><pre>' . "\n"
            . htmlspecialchars($sqlQuery, ENT_COMPAT) . "\n"
            . '</pre></code>';
    }

    /**
     * This function processes the datatypes supported by the DB,
     * as specified in Types->getColumns() and returns an HTML snippet that
     * creates a drop-down list.
     *
     * @param string $selected The value to mark as selected in HTML mode
     */
    public static function getSupportedDatatypes(string $selected): string
    {
        // NOTE: the SELECT tag is not included in this snippet.
        $retval = '';

        $dbi = DatabaseInterface::getInstance();
        foreach ($dbi->types->getColumns() as $key => $value) {
            if (is_array($value)) {
                $retval .= '<optgroup label="' . htmlspecialchars($key) . '">';
                foreach ($value as $subvalue) {
                    if ($subvalue === '-') {
                        $retval .= '<option disabled="disabled">';
                        $retval .= $subvalue;
                        $retval .= '</option>';
                        continue;
                    }

                    $isLengthRestricted = Compatibility::isIntegersSupportLength($subvalue, '2', $dbi);
                    $retval .= sprintf(
                        '<option data-length-restricted="%b" %s title="%s">%s</option>',
                        $isLengthRestricted ? 0 : 1,
                        $selected === $subvalue ? 'selected="selected"' : '',
                        $dbi->types->getTypeDescription($subvalue),
                        $subvalue,
                    );
                }

                $retval .= '</optgroup>';
                continue;
            }

            $isLengthRestricted = Compatibility::isIntegersSupportLength($value, '2', $dbi);
            $retval .= sprintf(
                '<option data-length-restricted="%b" %s title="%s">%s</option>',
                $isLengthRestricted ? 0 : 1,
                $selected === $value ? 'selected="selected"' : '',
                $dbi->types->getTypeDescription($value),
                $value,
            );
        }

        return $retval;
    }
}
