<?php
/**
 * Used to render the header of PMA's pages
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Container\ContainerBuilder;
use PhpMyAdmin\Favorites\RecentFavoriteTable;
use PhpMyAdmin\Favorites\RecentFavoriteTables;
use PhpMyAdmin\Favorites\TableType;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Navigation\Navigation;
use PhpMyAdmin\Theme\ThemeManager;

use function array_merge;
use function defined;
use function gmdate;
use function header;
use function htmlspecialchars;
use function ini_get;
use function json_encode;
use function sprintf;
use function strtolower;
use function urlencode;

use const JSON_HEX_TAG;

/**
 * Class used to output the HTTP and HTML headers
 */
class Header
{
    /**
     * Scripts instance
     */
    private Scripts $scripts;
    /**
     * Menu instance
     */
    private Menu $menu;
    /**
     * The page title
     */
    private string $title = '';
    /**
     * The value for the id attribute for the body tag
     */
    private string $bodyId = '';
    /**
     * Whether to show the top menu
     */
    private bool $menuEnabled;
    /**
     * Whether to show the warnings
     */
    private bool $warningsEnabled = true;
    /**
     * Whether we are servicing an ajax request.
     */
    private bool $isAjax = false;
    /**
     * Whether to display anything
     */
    private bool $isEnabled = true;
    /**
     * Whether the HTTP headers (and possibly some HTML)
     * have already been sent to the browser
     */
    private bool $headerIsSent = false;

    private UserPreferences $userPreferences;

    private bool $isTransformationWrapper = false;

    public function __construct(
        private readonly Template $template,
        private readonly Console $console,
        private readonly Config $config,
    ) {
        $dbi = DatabaseInterface::getInstance();
        $this->menuEnabled = $dbi->isConnected();
        $relation = new Relation($dbi);
        $this->menu = new Menu($dbi, $this->template, $this->config, $relation, Current::$database, Current::$table);
        $this->scripts = new Scripts($this->template);
        $this->addDefaultScripts();

        $this->userPreferences = new UserPreferences($dbi, $relation, $this->template);
    }

    /**
     * Loads common scripts
     */
    private function addDefaultScripts(): void
    {
        $this->scripts->addFile('runtime.js');
        $this->scripts->addFile('vendor/jquery/jquery.min.js');
        $this->scripts->addFile('vendor/jquery/jquery-migrate.min.js');
        $this->scripts->addFile('vendor/sprintf.js');
        $this->scripts->addFile('vendor/jquery/jquery-ui.min.js');
        $this->scripts->addFile('name-conflict-fixes.js');
        $this->scripts->addFile('vendor/bootstrap/bootstrap.bundle.min.js');
        $this->scripts->addFile('vendor/js.cookie.min.js');
        $this->scripts->addFile('vendor/jquery/jquery.validate.min.js');
        $this->scripts->addFile('vendor/jquery/jquery-ui-timepicker-addon.js');
        $this->scripts->addFile('index.php', ['route' => '/messages', 'l' => $GLOBALS['lang']]);
        $this->scripts->addFile('shared.js');
        $this->scripts->addFile('menu_resizer.js');
        $this->scripts->addFile('main.js');

        $this->scripts->addCode($this->getJsParamsCode());
    }

    /**
     * Returns, as an array, a list of parameters
     * used on the client side
     *
     * @return mixed[]
     */
    public function getJsParams(): array
    {
        $pftext = $_SESSION['tmpval']['pftext'] ?? '';

        $params = [
            // Do not add any separator, JS code will decide
            'common_query' => Url::getCommonRaw([], ''),
            'opendb_url' => Util::getScriptNameForOption($this->config->settings['DefaultTabDatabase'], 'database'),
            'lang' => $GLOBALS['lang'],
            'server' => Current::$server,
            'table' => Current::$table,
            'db' => Current::$database,
            'token' => $_SESSION[' PMA_token '],
            'text_dir' => LanguageManager::$textDir,
            'LimitChars' => $this->config->settings['LimitChars'],
            'pftext' => $pftext,
            'confirm' => $this->config->settings['Confirm'],
            'LoginCookieValidity' => $this->config->settings['LoginCookieValidity'],
            'session_gc_maxlifetime' => (int) ini_get('session.gc_maxlifetime'),
            'logged_in' => DatabaseInterface::getInstance()->isConnected(),
            'is_https' => $this->config->isHttps(),
            'rootPath' => $this->config->getRootPath(),
            'arg_separator' => Url::getArgSeparator(),
            'version' => Version::VERSION,
        ];
        if ($this->config->hasSelectedServer()) {
            $params['auth_type'] = $this->config->selectedServer['auth_type'];
            if (isset($this->config->selectedServer['user'])) {
                $params['user'] = $this->config->selectedServer['user'];
            }
        }

        return $params;
    }

    /**
     * Returns, as a string, a list of parameters
     * used on the client side
     */
    public function getJsParamsCode(): string
    {
        $params = $this->getJsParams();

        return 'window.Navigation.update(window.CommonParams.setAll(' . json_encode($params, JSON_HEX_TAG) . '));';
    }

    /**
     * Disables the rendering of the header
     */
    public function disable(): void
    {
        $this->isEnabled = false;
    }

    /**
     * Set the ajax flag to indicate whether
     * we are servicing an ajax request
     *
     * @param bool $isAjax Whether we are servicing an ajax request
     */
    public function setAjax(bool $isAjax): void
    {
        $this->isAjax = $isAjax;
        $this->console->setAjax($isAjax);
    }

    /**
     * Returns the Scripts object
     *
     * @return Scripts object
     */
    public function getScripts(): Scripts
    {
        return $this->scripts;
    }

    /**
     * Returns the Menu object
     *
     * @return Menu object
     */
    public function getMenu(): Menu
    {
        return $this->menu;
    }

    /**
     * Setter for the ID attribute in the BODY tag
     *
     * @param string $id Value for the ID attribute
     */
    public function setBodyId(string $id): void
    {
        $this->bodyId = htmlspecialchars($id);
    }

    /**
     * Setter for the title of the page
     *
     * @param string $title New title
     */
    public function setTitle(string $title): void
    {
        $this->title = htmlspecialchars($title);
    }

    /**
     * Disables the display of the top menu
     */
    public function disableMenuAndConsole(): void
    {
        $this->menuEnabled = false;
        $this->console->disable();
    }

    /**
     * Disables the display of the top menu
     */
    public function disableWarnings(): void
    {
        $this->warningsEnabled = false;
    }

    /**
     * Generates the header
     *
     * @return string The header
     */
    public function getDisplay(): string
    {
        if ($this->headerIsSent || ! $this->isEnabled) {
            return '';
        }

        $recentTable = '';
        if (empty($_REQUEST['recent_table']) && Current::$table !== '') {
            $recentTable = $this->addRecentTable(
                DatabaseName::from(Current::$database),
                TableName::from(Current::$table),
            );
        }

        if ($this->isAjax) {
            return $recentTable;
        }

        $this->sendHttpHeaders();

        $baseDir = defined('PMA_PATH_TO_BASEDIR') ? PMA_PATH_TO_BASEDIR : '';

        /** @var ThemeManager $themeManager */
        $themeManager = ContainerBuilder::getContainer()->get(ThemeManager::class);
        $theme = $themeManager->theme;

        $version = self::getVersionParameter();

        // The user preferences have been merged at this point
        // so we can conditionally add CodeMirror, other scripts and settings
        if ($this->config->settings['CodemirrorEnable']) {
            $this->scripts->addFile('vendor/codemirror/lib/codemirror.js');
            $this->scripts->addFile('vendor/codemirror/mode/sql/sql.js');
            $this->scripts->addFile('vendor/codemirror/addon/runmode/runmode.js');
            $this->scripts->addFile('vendor/codemirror/addon/hint/show-hint.js');
            $this->scripts->addFile('vendor/codemirror/addon/hint/sql-hint.js');
            if ($this->config->settings['LintEnable']) {
                $this->scripts->addFile('vendor/codemirror/addon/lint/lint.js');
                $this->scripts->addFile('codemirror/addon/lint/sql-lint.js');
            }
        }

        if ($this->config->settings['SendErrorReports'] !== 'never') {
            $this->scripts->addFile('vendor/tracekit.js');
            $this->scripts->addFile('error_report.js');
        }

        if ($this->config->settings['enable_drag_drop_import'] === true) {
            $this->scripts->addFile('drag_drop_import.js');
        }

        if (! $this->config->get('DisableShortcutKeys')) {
            $this->scripts->addFile('shortcuts_handler.js');
        }

        $this->scripts->addCode($this->getVariablesForJavaScript());

        $this->scripts->addCode(
            'ConsoleEnterExecutes=' . ($this->config->settings['ConsoleEnterExecutes'] ? 'true' : 'false'),
        );
        $this->scripts->addFiles($this->console->getScripts());

        $dbi = DatabaseInterface::getInstance();
        if ($this->menuEnabled && Current::$server > 0) {
            $navigation = (new Navigation($this->template, new Relation($dbi), $dbi, $this->config))->getDisplay();
        }

        $customHeader = Config::renderHeader();

        // offer to load user preferences from localStorage
        if (
            $this->config->get('user_preferences') === 'session'
            && ! isset($_SESSION['userprefs_autoload'])
        ) {
            $loadUserPreferences = $this->userPreferences->autoloadGetHeader();
        }

        if ($this->menuEnabled && Current::$server > 0) {
            $menu = $this->menu->getDisplay();
        }

        $console = $this->console->getDisplay();
        $messages = $this->getMessage();
        $isLoggedIn = $dbi->isConnected();

        $this->scripts->addFile('datetimepicker.js');
        $this->scripts->addFile('validator-messages.js');

        return $this->template->render('header', [
            'lang' => $GLOBALS['lang'],
            'allow_third_party_framing' => $this->config->settings['AllowThirdPartyFraming'],
            'base_dir' => $baseDir,
            'theme_path' => $theme->getPath(),
            'version' => $version,
            'text_dir' => LanguageManager::$textDir,
            'server' => Current::$server,
            'title' => $this->getPageTitle(),
            'scripts' => $this->scripts->getDisplay(),
            'body_id' => $this->bodyId,
            'navigation' => $navigation ?? '',
            'custom_header' => $customHeader,
            'load_user_preferences' => $loadUserPreferences ?? '',
            'show_hint' => $this->config->settings['ShowHint'],
            'is_warnings_enabled' => $this->warningsEnabled,
            'is_menu_enabled' => $this->menuEnabled,
            'is_logged_in' => $isLoggedIn,
            'menu' => $menu ?? '',
            'console' => $console,
            'messages' => $messages,
            'recent_table' => $recentTable,
            'theme_color_mode' => $theme->getColorMode(),
            'theme_color_modes' => $theme->getColorModes(),
            'theme_id' => $theme->getId(),
            'current_user' => $dbi->getCurrentUserAndHost(),
            'is_mariadb' => $dbi->isMariaDB(),
        ]);
    }

    /**
     * Returns the message to be displayed at the top of
     * the page, including the executed SQL query, if any.
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

        if ($message !== '') {
            if (isset($GLOBALS['buffer_message'])) {
                $bufferMessage = $GLOBALS['buffer_message'];
            }

            $retval .= Generator::getMessage($message);
            if (isset($bufferMessage)) {
                $GLOBALS['buffer_message'] = $bufferMessage;
            }
        }

        return $retval;
    }

    /**
     * Sends out the HTTP headers
     */
    public function sendHttpHeaders(): void
    {
        if (defined('TESTSUITE')) {
            return;
        }

        /**
         * Sends http headers
         */
        $GLOBALS['now'] = gmdate('D, d M Y H:i:s') . ' GMT';

        $headers = $this->getHttpHeaders();

        foreach ($headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }

        $this->headerIsSent = true;
    }

    /** @return array<string, string> */
    public function getHttpHeaders(): array
    {
        $headers = [];

        /* Prevent against ClickJacking by disabling framing */
        if (strtolower((string) $this->config->settings['AllowThirdPartyFraming']) === 'sameorigin') {
            $headers['X-Frame-Options'] = 'SAMEORIGIN';
        } elseif ($this->config->settings['AllowThirdPartyFraming'] !== true) {
            $headers['X-Frame-Options'] = 'DENY';
        }

        $headers['Referrer-Policy'] = 'no-referrer';

        $headers = array_merge($headers, $this->getCspHeaders());

        /**
         * Re-enable possible disabled XSS filters.
         *
         * @see https://developer.mozilla.org/docs/Web/HTTP/Headers/X-XSS-Protection
         */
        $headers['X-XSS-Protection'] = '1; mode=block';

        /**
         * "nosniff", prevents Internet Explorer and Google Chrome from MIME-sniffing
         * a response away from the declared content-type.
         *
         * @see https://developer.mozilla.org/docs/Web/HTTP/Headers/X-Content-Type-Options
         */
        $headers['X-Content-Type-Options'] = 'nosniff';

        /**
         * Adobe cross-domain-policies.
         *
         * @see https://www.sentrium.co.uk/labs/application-security-101-http-headers
         */
        $headers['X-Permitted-Cross-Domain-Policies'] = 'none';

        /**
         * Robots meta tag.
         *
         * @see https://developers.google.com/search/docs/crawling-indexing/robots-meta-tag
         */
        $headers['X-Robots-Tag'] = 'noindex, nofollow';

        /**
         * The HTTP Permissions-Policy header provides a mechanism to allow and deny
         * the use of browser features in a document
         * or within any <iframe> elements in the document.
         *
         * @see https://developer.mozilla.org/docs/Web/HTTP/Headers/Permissions-Policy
         */
        $headers['Permissions-Policy'] = 'fullscreen=(self), oversized-images=(self), interest-cohort=()';

        $headers = array_merge($headers, Core::getNoCacheHeaders());

        /**
         * A different Content-Type is set in {@see \PhpMyAdmin\Controllers\Transformation\WrapperController}.
         */
        if (! $this->isTransformationWrapper) {
            // Define the charset to be used
            $headers['Content-Type'] = 'text/html; charset=utf-8';
        }

        return $headers;
    }

    /**
     * If the page is missing the title, this function
     * will set it to something reasonable
     */
    public function getPageTitle(): string
    {
        if ($this->title === '') {
            if (Current::$server > 0) {
                if (Current::$table !== '') {
                    $tempTitle = $this->config->settings['TitleTable'];
                } elseif (Current::$database !== '') {
                    $tempTitle = $this->config->settings['TitleDatabase'];
                } elseif ($this->config->selectedServer['host'] !== '') {
                    $tempTitle = $this->config->settings['TitleServer'];
                } else {
                    $tempTitle = $this->config->settings['TitleDefault'];
                }

                $this->title = htmlspecialchars(
                    Util::expandUserString($tempTitle),
                );
            } else {
                $this->title = 'phpMyAdmin';
            }
        }

        return $this->title;
    }

    /**
     * Get all the CSP allow policy headers
     *
     * @return array<string, string>
     */
    private function getCspHeaders(): array
    {
        $mapTileUrl = ' tile.openstreetmap.org';
        $captchaUrl = '';
        $cspAllow = $this->config->settings['CSPAllow'];

        if (
            ! empty($this->config->settings['CaptchaLoginPrivateKey'])
            && ! empty($this->config->settings['CaptchaLoginPublicKey'])
            && ! empty($this->config->settings['CaptchaApi'])
            && ! empty($this->config->settings['CaptchaRequestParam'])
            && ! empty($this->config->settings['CaptchaResponseParam'])
        ) {
            $captchaUrl = ' ' . $this->config->settings['CaptchaCsp'] . ' ';
        }

        $headers = [];

        $headers['Content-Security-Policy'] = sprintf(
            'default-src \'self\' %s%s;script-src \'self\' \'unsafe-inline\' \'unsafe-eval\' %s%s;'
                . 'style-src \'self\' \'unsafe-inline\' %s%s;img-src \'self\' data: %s%s%s;object-src \'none\';',
            $captchaUrl,
            $cspAllow,
            $captchaUrl,
            $cspAllow,
            $captchaUrl,
            $cspAllow,
            $cspAllow,
            $mapTileUrl,
            $captchaUrl,
        );

        $headers['X-Content-Security-Policy'] = sprintf(
            'default-src \'self\' %s%s;options inline-script eval-script;'
                . 'referrer no-referrer;img-src \'self\' data: %s%s%s;object-src \'none\';',
            $captchaUrl,
            $cspAllow,
            $cspAllow,
            $mapTileUrl,
            $captchaUrl,
        );

        $headers['X-WebKit-CSP'] = sprintf(
            'default-src \'self\' %s%s;script-src \'self\' %s%s \'unsafe-inline\' \'unsafe-eval\';'
                . 'referrer no-referrer;style-src \'self\' \'unsafe-inline\' %s;'
                . 'img-src \'self\' data: %s%s%s;object-src \'none\';',
            $captchaUrl,
            $cspAllow,
            $captchaUrl,
            $cspAllow,
            $captchaUrl,
            $cspAllow,
            $mapTileUrl,
            $captchaUrl,
        );

        return $headers;
    }

    /**
     * Add recently used table and reload the navigation.
     */
    private function addRecentTable(DatabaseName $db, TableName $table): string
    {
        if ($this->menuEnabled && $this->config->settings['NumRecentTables'] > 0) {
            $favoriteTable = new RecentFavoriteTable($db, $table);
            $error = RecentFavoriteTables::getInstance(TableType::Recent)->add($favoriteTable);
            if ($error === true) {
                return RecentFavoriteTables::getHtmlUpdateRecentTables();
            }

            return $error->getDisplay();
        }

        return '';
    }

    /**
     * Returns the phpMyAdmin version to be appended to the url to avoid caching
     * between versions
     *
     * @return string urlencoded pma version as a parameter
     */
    public static function getVersionParameter(): string
    {
        return 'v=' . urlencode(Version::VERSION);
    }

    private function getVariablesForJavaScript(): string
    {
        $maxInputVars = ini_get('max_input_vars');
        $maxInputVarsValue = $maxInputVars === false || $maxInputVars === '' ? 'false' : (int) $maxInputVars;

        return $this->template->render('javascript/variables', [
            'first_day_of_calendar' => $this->config->settings['FirstDayOfCalendar'] ?? 0,
            'max_input_vars' => $maxInputVarsValue,
        ]);
    }

    public function setIsTransformationWrapper(bool $isTransformationWrapper): void
    {
        $this->isTransformationWrapper = $isTransformationWrapper;
    }
}
