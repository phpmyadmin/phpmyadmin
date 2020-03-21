<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Used to render the header of PMA's pages
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Navigation\Navigation;

/**
 * Class used to output the HTTP and HTML headers
 *
 * @package PhpMyAdmin
 */
class Header
{
    /**
     * Scripts instance
     *
     * @access private
     * @var Scripts
     */
    private $_scripts;
    /**
     * PhpMyAdmin\Console instance
     *
     * @access private
     * @var Console
     */
    private $_console;
    /**
     * Menu instance
     *
     * @access private
     * @var Menu
     */
    private $_menu;
    /**
     * Whether to offer the option of importing user settings
     *
     * @access private
     * @var bool
     */
    private $_userprefsOfferImport;
    /**
     * The page title
     *
     * @access private
     * @var string
     */
    private $_title;
    /**
     * The value for the id attribute for the body tag
     *
     * @access private
     * @var string
     */
    private $_bodyId;
    /**
     * Whether to show the top menu
     *
     * @access private
     * @var bool
     */
    private $_menuEnabled;
    /**
     * Whether to show the warnings
     *
     * @access private
     * @var bool
     */
    private $_warningsEnabled;
    /**
     * Whether the page is in 'print view' mode
     *
     * @access private
     * @var bool
     */
    private $_isPrintView;
    /**
     * Whether we are servicing an ajax request.
     *
     * @access private
     * @var bool
     */
    private $_isAjax;
    /**
     * Whether to display anything
     *
     * @access private
     * @var bool
     */
    private $_isEnabled;
    /**
     * Whether the HTTP headers (and possibly some HTML)
     * have already been sent to the browser
     *
     * @access private
     * @var bool
     */
    private $_headerIsSent;

    /**
     * @var UserPreferences
     */
    private $userPreferences;

    /**
     * @var Template
     */
    private $template;

    /**
     * Creates a new class instance
     */
    public function __construct()
    {
        $this->template = new Template();

        $this->_isEnabled = true;
        $this->_isAjax = false;
        $this->_bodyId = '';
        $this->_title = '';
        $this->_console = new Console();
        $db = strlen($GLOBALS['db']) ? $GLOBALS['db'] : '';
        $table = strlen($GLOBALS['table']) ? $GLOBALS['table'] : '';
        $this->_menu = new Menu(
            $db,
            $table
        );
        $this->_menuEnabled = true;
        $this->_warningsEnabled = true;
        $this->_isPrintView = false;
        $this->_scripts = new Scripts();
        $this->_addDefaultScripts();
        $this->_headerIsSent = false;
        // if database storage for user preferences is transient,
        // offer to load exported settings from localStorage
        // (detection will be done in JavaScript)
        $this->_userprefsOfferImport = false;
        if ($GLOBALS['PMA_Config']->get('user_preferences') == 'session'
            && ! isset($_SESSION['userprefs_autoload'])
        ) {
            $this->_userprefsOfferImport = true;
        }

        $this->userPreferences = new UserPreferences();
    }

    /**
     * Loads common scripts
     *
     * @return void
     */
    private function _addDefaultScripts(): void
    {
        // Localised strings
        $this->_scripts->addFile('vendor/jquery/jquery.min.js');
        $this->_scripts->addFile('vendor/jquery/jquery-migrate.js');
        $this->_scripts->addFile('whitelist.php');
        $this->_scripts->addFile('vendor/sprintf.js');
        $this->_scripts->addFile('ajax.js');
        $this->_scripts->addFile('keyhandler.js');
        $this->_scripts->addFile('vendor/bootstrap/bootstrap.bundle.min.js');
        $this->_scripts->addFile('vendor/jquery/jquery-ui.min.js');
        $this->_scripts->addFile('vendor/js.cookie.js');
        $this->_scripts->addFile('vendor/jquery/jquery.mousewheel.js');
        $this->_scripts->addFile('vendor/jquery/jquery.event.drag-2.2.js');
        $this->_scripts->addFile('vendor/jquery/jquery.validate.js');
        $this->_scripts->addFile('vendor/jquery/jquery-ui-timepicker-addon.js');
        $this->_scripts->addFile('vendor/jquery/jquery.ba-hashchange-1.3.js');
        $this->_scripts->addFile('vendor/jquery/jquery.debounce-1.0.5.js');
        $this->_scripts->addFile('menu_resizer.js');

        // Cross-framing protection
        if ($GLOBALS['cfg']['AllowThirdPartyFraming'] === false) {
            $this->_scripts->addFile('cross_framing_protection.js');
        }

        $this->_scripts->addFile('rte.js');
        if ($GLOBALS['cfg']['SendErrorReports'] !== 'never') {
            $this->_scripts->addFile('vendor/tracekit.js');
            $this->_scripts->addFile('error_report.js');
        }

        // Here would not be a good place to add CodeMirror because
        // the user preferences have not been merged at this point

        $this->_scripts->addFile('messages.php', ['l' => $GLOBALS['lang']]);
        $this->_scripts->addFile('config.js');
        $this->_scripts->addFile('doclinks.js');
        $this->_scripts->addFile('functions.js');
        $this->_scripts->addFile('navigation.js');
        $this->_scripts->addFile('indexes.js');
        $this->_scripts->addFile('common.js');
        $this->_scripts->addFile('page_settings.js');
        if ($GLOBALS['cfg']['enable_drag_drop_import'] === true) {
            $this->_scripts->addFile('drag_drop_import.js');
        }
        if (! $GLOBALS['PMA_Config']->get('DisableShortcutKeys')) {
            $this->_scripts->addFile('shortcuts_handler.js');
        }
        $this->_scripts->addCode($this->getJsParamsCode());
    }

    /**
     * Returns, as an array, a list of parameters
     * used on the client side
     *
     * @return array
     */
    public function getJsParams(): array
    {
        $db = strlen($GLOBALS['db']) ? $GLOBALS['db'] : '';
        $table = strlen($GLOBALS['table']) ? $GLOBALS['table'] : '';
        $pftext = isset($_SESSION['tmpval']['pftext'])
            ? $_SESSION['tmpval']['pftext'] : '';

        $params = [
            'common_query' => Url::getCommonRaw(),
            'opendb_url' => Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabDatabase'],
                'database'
            ),
            'lang' => $GLOBALS['lang'],
            'server' => $GLOBALS['server'],
            'table' => $table,
            'db' => $db,
            'token' => $_SESSION[' PMA_token '],
            'text_dir' => $GLOBALS['text_dir'],
            'show_databases_navigation_as_tree' => $GLOBALS['cfg']['ShowDatabasesNavigationAsTree'],
            'pma_text_default_tab' => Util::getTitleForTarget(
                $GLOBALS['cfg']['DefaultTabTable']
            ),
            'pma_text_left_default_tab' => Util::getTitleForTarget(
                $GLOBALS['cfg']['NavigationTreeDefaultTabTable']
            ),
            'pma_text_left_default_tab2' => Util::getTitleForTarget(
                $GLOBALS['cfg']['NavigationTreeDefaultTabTable2']
            ),
            'LimitChars' => $GLOBALS['cfg']['LimitChars'],
            'pftext' => $pftext,
            'confirm' => $GLOBALS['cfg']['Confirm'],
            'LoginCookieValidity' => $GLOBALS['cfg']['LoginCookieValidity'],
            'session_gc_maxlifetime' => (int) ini_get('session.gc_maxlifetime'),
            'logged_in' => isset($GLOBALS['dbi']) ? $GLOBALS['dbi']->isUserType('logged') : false,
            'is_https' => $GLOBALS['PMA_Config']->isHttps(),
            'rootPath' => $GLOBALS['PMA_Config']->getRootPath(),
            'arg_separator' => Url::getArgSeparator(),
            'PMA_VERSION' => PMA_VERSION,
        ];
        if (isset($GLOBALS['cfg']['Server'])
            && isset($GLOBALS['cfg']['Server']['auth_type'])
        ) {
            $params['auth_type'] = $GLOBALS['cfg']['Server']['auth_type'];
            if (isset($GLOBALS['cfg']['Server']['user'])) {
                $params['user'] = $GLOBALS['cfg']['Server']['user'];
            }
        }

        return $params;
    }

    /**
     * Returns, as a string, a list of parameters
     * used on the client side
     *
     * @return string
     */
    public function getJsParamsCode(): string
    {
        $params = $this->getJsParams();
        foreach ($params as $key => $value) {
            if (is_bool($value)) {
                $params[$key] = $key . ':' . ($value ? 'true' : 'false') . '';
            } else {
                $params[$key] = $key . ':"' . Sanitize::escapeJsString($value) . '"';
            }
        }
        return 'CommonParams.setAll({' . implode(',', $params) . '});';
    }

    /**
     * Disables the rendering of the header
     *
     * @return void
     */
    public function disable(): void
    {
        $this->_isEnabled = false;
    }

    /**
     * Set the ajax flag to indicate whether
     * we are servicing an ajax request
     *
     * @param bool $isAjax Whether we are servicing an ajax request
     *
     * @return void
     */
    public function setAjax(bool $isAjax): void
    {
        $this->_isAjax = $isAjax;
        $this->_console->setAjax($isAjax);
    }

    /**
     * Returns the Scripts object
     *
     * @return Scripts object
     */
    public function getScripts(): Scripts
    {
        return $this->_scripts;
    }

    /**
     * Returns the Menu object
     *
     * @return Menu object
     */
    public function getMenu(): Menu
    {
        return $this->_menu;
    }

    /**
     * Setter for the ID attribute in the BODY tag
     *
     * @param string $id Value for the ID attribute
     *
     * @return void
     */
    public function setBodyId(string $id): void
    {
        $this->_bodyId = htmlspecialchars($id);
    }

    /**
     * Setter for the title of the page
     *
     * @param string $title New title
     *
     * @return void
     */
    public function setTitle(string $title): void
    {
        $this->_title = htmlspecialchars($title);
    }

    /**
     * Disables the display of the top menu
     *
     * @return void
     */
    public function disableMenuAndConsole(): void
    {
        $this->_menuEnabled = false;
        $this->_console->disable();
    }

    /**
     * Disables the display of the top menu
     *
     * @return void
     */
    public function disableWarnings(): void
    {
        $this->_warningsEnabled = false;
    }

    /**
     * Turns on 'print view' mode
     *
     * @return void
     */
    public function enablePrintView(): void
    {
        $this->disableMenuAndConsole();
        $this->setTitle(__('Print view') . ' - phpMyAdmin ' . PMA_VERSION);
        $this->_isPrintView = true;
    }

    /**
     * Generates the header
     *
     * @return string The header
     */
    public function getDisplay(): string
    {
        if (! $this->_headerIsSent && $this->_isEnabled) {
            if (! $this->_isAjax) {
                $this->sendHttpHeaders();

                $baseDir = defined('PMA_PATH_TO_BASEDIR') ? PMA_PATH_TO_BASEDIR : '';
                $uniqueValue = $GLOBALS['PMA_Config']->getThemeUniqueValue();
                $themePath = $GLOBALS['pmaThemePath'];
                $version = self::getVersionParameter();

                // The user preferences have been merged at this point
                // so we can conditionally add CodeMirror
                if ($GLOBALS['cfg']['CodemirrorEnable']) {
                    $this->_scripts->addFile('vendor/codemirror/lib/codemirror.js');
                    $this->_scripts->addFile('vendor/codemirror/mode/sql/sql.js');
                    $this->_scripts->addFile('vendor/codemirror/addon/runmode/runmode.js');
                    $this->_scripts->addFile('vendor/codemirror/addon/hint/show-hint.js');
                    $this->_scripts->addFile('vendor/codemirror/addon/hint/sql-hint.js');
                    if ($GLOBALS['cfg']['LintEnable']) {
                        $this->_scripts->addFile('vendor/codemirror/addon/lint/lint.js');
                        $this->_scripts->addFile(
                            'codemirror/addon/lint/sql-lint.js'
                        );
                    }
                }
                $this->_scripts->addCode(
                    'ConsoleEnterExecutes='
                    . ($GLOBALS['cfg']['ConsoleEnterExecutes'] ? 'true' : 'false')
                );
                $this->_scripts->addFiles($this->_console->getScripts());
                if ($this->_userprefsOfferImport) {
                    $this->_scripts->addFile('config.js');
                }

                if ($this->_menuEnabled && $GLOBALS['server'] > 0) {
                    $nav = new Navigation(
                        $this->template,
                        new Relation($GLOBALS['dbi']),
                        $GLOBALS['dbi']
                    );
                    $navigation = $nav->getDisplay();
                }

                $customHeader = Config::renderHeader();

                // offer to load user preferences from localStorage
                if ($this->_userprefsOfferImport) {
                    $loadUserPreferences = $this->userPreferences->autoloadGetHeader();
                }

                if ($this->_menuEnabled && $GLOBALS['server'] > 0) {
                    $menu = $this->_menu->getDisplay();
                }
                $console = $this->_console->getDisplay();
                $messages = $this->getMessage();
            }
            if (empty($_REQUEST['recent_table'])) {
                $recentTable = $this->_addRecentTable(
                    $GLOBALS['db'],
                    $GLOBALS['table']
                );
            }
            return $this->template->render('header', [
                'is_ajax' => $this->_isAjax,
                'is_enabled' => $this->_isEnabled,
                'lang' => $GLOBALS['lang'],
                'allow_third_party_framing' => $GLOBALS['cfg']['AllowThirdPartyFraming'],
                'is_print_view' => $this->_isPrintView,
                'base_dir' => $baseDir ?? '',
                'unique_value' => $uniqueValue ?? '',
                'theme_path' => $themePath ?? '',
                'version' => $version ?? '',
                'text_dir' => $GLOBALS['text_dir'],
                'server' => $GLOBALS['server'] ?? null,
                'title' => $this->getPageTitle(),
                'scripts' => $this->_scripts->getDisplay(),
                'body_id' => $this->_bodyId,
                'navigation' => $navigation ?? '',
                'custom_header' => $customHeader ?? '',
                'load_user_preferences' => $loadUserPreferences ?? '',
                'show_hint' => $GLOBALS['cfg']['ShowHint'],
                'is_warnings_enabled' => $this->_warningsEnabled,
                'is_menu_enabled' => $this->_menuEnabled,
                'menu' => $menu ?? '',
                'console' => $console ?? '',
                'messages' => $messages ?? '',
                'has_recent_table' => empty($_REQUEST['recent_table']),
                'recent_table' => $recentTable ?? '',
            ]);
        }
        return '';
    }

    /**
     * Returns the message to be displayed at the top of
     * the page, including the executed SQL query, if any.
     *
     * @return string
     */
    public function getMessage(): string
    {
        $retval = '';
        $message = '';
        if (! empty($GLOBALS['message'])) {
            $message = $GLOBALS['message'];
            unset($GLOBALS['message']);
        } elseif (! empty($_REQUEST['message'])) {
            $message = $_REQUEST['message'];
        }
        if (! empty($message)) {
            if (isset($GLOBALS['buffer_message'])) {
                $buffer_message = $GLOBALS['buffer_message'];
            }
            $retval .= Util::getMessage($message);
            if (isset($buffer_message)) {
                $GLOBALS['buffer_message'] = $buffer_message;
            }
        }
        return $retval;
    }

    /**
     * Sends out the HTTP headers
     *
     * @return void
     */
    public function sendHttpHeaders(): void
    {
        if (defined('TESTSUITE')) {
            return;
        }
        $map_tile_urls = ' *.tile.openstreetmap.org';

        /**
         * Sends http headers
         */
        $GLOBALS['now'] = gmdate('D, d M Y H:i:s') . ' GMT';
        if (! empty($GLOBALS['cfg']['CaptchaLoginPrivateKey'])
            && ! empty($GLOBALS['cfg']['CaptchaLoginPublicKey'])
        ) {
            $captcha_url
                = ' https://apis.google.com https://www.google.com/recaptcha/'
                . ' https://www.gstatic.com/recaptcha/ https://ssl.gstatic.com/ ';
        } else {
            $captcha_url = '';
        }
        /* Prevent against ClickJacking by disabling framing */
        if (strtolower((string) $GLOBALS['cfg']['AllowThirdPartyFraming']) === 'sameorigin') {
            header(
                'X-Frame-Options: SAMEORIGIN'
            );
        } elseif ($GLOBALS['cfg']['AllowThirdPartyFraming'] !== true) {
            header(
                'X-Frame-Options: DENY'
            );
        }
        header(
            'Referrer-Policy: no-referrer'
        );
        header(
            "Content-Security-Policy: default-src 'self' "
            . $captcha_url
            . $GLOBALS['cfg']['CSPAllow'] . ';'
            . "script-src 'self' 'unsafe-inline' 'unsafe-eval' "
            . $captcha_url
            . $GLOBALS['cfg']['CSPAllow'] . ';'
            . "style-src 'self' 'unsafe-inline' "
            . $captcha_url
            . $GLOBALS['cfg']['CSPAllow']
            . ";"
            . "img-src 'self' data: "
            . $GLOBALS['cfg']['CSPAllow']
            . $map_tile_urls
            . $captcha_url
            . ";"
            . "object-src 'none';"
        );
        header(
            "X-Content-Security-Policy: default-src 'self' "
            . $captcha_url
            . $GLOBALS['cfg']['CSPAllow'] . ';'
            . "options inline-script eval-script;"
            . "referrer no-referrer;"
            . "img-src 'self' data: "
            . $GLOBALS['cfg']['CSPAllow']
            . $map_tile_urls
            . $captcha_url
            . ";"
            . "object-src 'none';"
        );
        header(
            "X-WebKit-CSP: default-src 'self' "
            . $captcha_url
            . $GLOBALS['cfg']['CSPAllow'] . ';'
            . "script-src 'self' "
            . $captcha_url
            . $GLOBALS['cfg']['CSPAllow']
            . " 'unsafe-inline' 'unsafe-eval';"
            . "referrer no-referrer;"
            . "style-src 'self' 'unsafe-inline' "
            . $captcha_url
            . ';'
            . "img-src 'self' data: "
            . $GLOBALS['cfg']['CSPAllow']
            . $map_tile_urls
            . $captcha_url
            . ";"
            . "object-src 'none';"
        );
        // Re-enable possible disabled XSS filters
        // see https://www.owasp.org/index.php/List_of_useful_HTTP_headers
        header(
            'X-XSS-Protection: 1; mode=block'
        );
        // "nosniff", prevents Internet Explorer and Google Chrome from MIME-sniffing
        // a response away from the declared content-type
        // see https://www.owasp.org/index.php/List_of_useful_HTTP_headers
        header(
            'X-Content-Type-Options: nosniff'
        );
        // Adobe cross-domain-policies
        // see https://www.adobe.com/devnet/articles/crossdomain_policy_file_spec.html
        header(
            'X-Permitted-Cross-Domain-Policies: none'
        );
        // Robots meta tag
        // see https://developers.google.com/webmasters/control-crawl-index/docs/robots_meta_tag
        header(
            'X-Robots-Tag: noindex, nofollow'
        );
        Core::noCacheHeader();
        if (! defined('IS_TRANSFORMATION_WRAPPER')) {
            // Define the charset to be used
            header('Content-Type: text/html; charset=utf-8');
        }
        $this->_headerIsSent = true;
    }

    /**
     * If the page is missing the title, this function
     * will set it to something reasonable
     *
     * @return string
     */
    public function getPageTitle(): string
    {
        if (strlen($this->_title) == 0) {
            if ($GLOBALS['server'] > 0) {
                if (strlen($GLOBALS['table'])) {
                    $temp_title = $GLOBALS['cfg']['TitleTable'];
                } elseif (strlen($GLOBALS['db'])) {
                    $temp_title = $GLOBALS['cfg']['TitleDatabase'];
                } elseif (strlen($GLOBALS['cfg']['Server']['host'])) {
                    $temp_title = $GLOBALS['cfg']['TitleServer'];
                } else {
                    $temp_title = $GLOBALS['cfg']['TitleDefault'];
                }
                $this->_title = htmlspecialchars(
                    Util::expandUserString($temp_title)
                );
            } else {
                $this->_title = 'phpMyAdmin';
            }
        }
        return $this->_title;
    }

    /**
     * Add recently used table and reload the navigation.
     *
     * @param string $db    Database name where the table is located.
     * @param string $table The table name
     *
     * @return string
     */
    private function _addRecentTable(string $db, string $table): string
    {
        $retval = '';
        if ($this->_menuEnabled
            && strlen($table) > 0
            && $GLOBALS['cfg']['NumRecentTables'] > 0
        ) {
            $tmp_result = RecentFavoriteTable::getInstance('recent')->add(
                $db,
                $table
            );
            if ($tmp_result === true) {
                $retval = RecentFavoriteTable::getHtmlUpdateRecentTables();
            } else {
                $error  = $tmp_result;
                $retval = $error->getDisplay();
            }
        }
        return $retval;
    }

    /**
     * Returns the phpMyAdmin version to be appended to the url to avoid caching
     * between versions
     *
     * @return string urlenocded pma version as a parameter
     */
    public static function getVersionParameter(): string
    {
        return "v=" . urlencode(PMA_VERSION);
    }
}
