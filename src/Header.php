<?php
/**
 * Used to render the header of PMA's pages
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Config\UserPreferences;
use PhpMyAdmin\Config\UserPreferencesHandler;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Console\Console;
use PhpMyAdmin\Container\ContainerBuilder;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\I18n\LanguageManager;
use PhpMyAdmin\Navigation\Navigation;
use PhpMyAdmin\Theme\ThemeManager;

use function array_merge;
use function htmlspecialchars;
use function ini_get;
use function json_encode;
use function sprintf;

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

    /** Whether to show the top menu */
    private bool|null $isMenuEnabled = null;

    /**
     * Whether to show the warnings
     */
    private bool $warningsEnabled = true;

    private bool $isTransformationWrapper = false;

    public function __construct(
        private readonly Template $template,
        private readonly Console $console,
        private readonly Config $config,
        private readonly DatabaseInterface $dbi,
        private readonly Relation $relation,
        private readonly UserPreferences $userPreferences,
        private readonly UserPreferencesHandler $userPreferencesHandler,
    ) {
        $this->menu = new Menu(
            $this->dbi,
            $this->template,
            $this->config,
            $this->relation,
            Current::$database,
            Current::$table,
        );
        $this->scripts = new Scripts($this->template);
        $this->addDefaultScripts();
    }

    private function isMenuEnabled(): bool
    {
        if ($this->isMenuEnabled !== null) {
            return $this->isMenuEnabled;
        }

        $this->isMenuEnabled = $this->dbi->isConnected();

        return $this->isMenuEnabled;
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
        $this->scripts->addFile('vendor/bootstrap/bootstrap.js');
        $this->scripts->addFile('vendor/js.cookie.min.js');
        $this->scripts->addFile('vendor/jquery/jquery.validate.min.js');
        $this->scripts->addFile('vendor/jquery/jquery-ui-timepicker-addon.min.js');
        $this->scripts->addFile('index.php', ['route' => '/messages', 'l' => Current::$lang]);
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
            'opendb_url' => Url::getFromRoute($this->config->config->DefaultTabDatabase),
            'lang' => Current::$lang,
            'server' => Current::$server,
            'table' => Current::$table,
            'db' => Current::$database,
            'token' => $_SESSION[' PMA_token '],
            'text_dir' => LanguageManager::$textDirection->value,
            'LimitChars' => $this->config->config->limitChars,
            'pftext' => $pftext,
            'confirm' => $this->config->config->Confirm,
            'LoginCookieValidity' => $this->config->config->LoginCookieValidity,
            'session_gc_maxlifetime' => (int) ini_get('session.gc_maxlifetime'),
            'logged_in' => $this->dbi->isConnected(),
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
        $this->isMenuEnabled = false;
        $this->console->disable();
    }

    /**
     * Disables the display of the top menu
     */
    public function disableWarnings(): void
    {
        $this->warningsEnabled = false;
    }

    /** @return array<string, mixed> */
    public function getDisplay(): array
    {
        $themeManager = ContainerBuilder::getContainer()->get(ThemeManager::class);
        $theme = $themeManager->theme;

        // The user preferences have been merged at this point
        // so we can conditionally add CodeMirror, other scripts and settings
        if ($this->config->config->CodemirrorEnable) {
            $this->scripts->addFile('vendor/codemirror/lib/codemirror.js');
            $this->scripts->addFile('vendor/codemirror/mode/sql/sql.js');
            $this->scripts->addFile('vendor/codemirror/addon/runmode/runmode.js');
            $this->scripts->addFile('vendor/codemirror/addon/hint/show-hint.js');
            $this->scripts->addFile('vendor/codemirror/addon/hint/sql-hint.js');
            if ($this->config->config->LintEnable) {
                $this->scripts->addFile('vendor/codemirror/addon/lint/lint.js');
                $this->scripts->addFile('codemirror/addon/lint/sql-lint.js');
            }
        }

        if ($this->config->config->SendErrorReports !== 'never') {
            $this->scripts->addFile('vendor/tracekit.js');
            $this->scripts->addFile('error_report.js');
        }

        if ($this->config->config->enable_drag_drop_import) {
            $this->scripts->addFile('drag_drop_import.js');
        }

        if (! $this->config->config->DisableShortcutKeys) {
            $this->scripts->addFile('shortcuts_handler.js');
        }

        $this->scripts->addCode($this->getVariablesForJavaScript());

        $this->scripts->addCode(
            'ConsoleEnterExecutes=' . ($this->config->config->ConsoleEnterExecutes ? 'true' : 'false'),
        );
        $this->scripts->addFiles($this->console->getScripts());

        if ($this->isMenuEnabled() && Current::$server > 0) {
            $navigation = (new Navigation($this->template, $this->relation, $this->dbi, $this->config))->getDisplay();
        }

        $customHeader = self::renderHeader();

        // offer to load user preferences from localStorage
        if (
            $this->userPreferencesHandler->storageType === 'session'
            && ! isset($_SESSION['userprefs_autoload'])
        ) {
            $loadUserPreferences = $this->userPreferences->autoloadGetHeader();
        }

        if ($this->isMenuEnabled() && Current::$server > 0) {
            $menu = $this->menu->getDisplay();
        }

        $console = $this->console->getDisplay();
        $messages = $this->getMessage();
        $isLoggedIn = $this->dbi->isConnected();

        $this->scripts->addFile('datetimepicker.js');
        $this->scripts->addFile('validator-messages.js');

        return [
            'lang' => Current::$lang,
            'allow_third_party_framing' => $this->config->config->AllowThirdPartyFraming,
            'theme_path' => $theme->getPath(),
            'server' => Current::$server,
            'title' => $this->getPageTitle(),
            'scripts' => $this->scripts->getDisplay(),
            'body_id' => $this->bodyId,
            'navigation' => $navigation ?? '',
            'custom_header' => $customHeader,
            'load_user_preferences' => $loadUserPreferences ?? '',
            'show_hint' => $this->config->config->ShowHint,
            'is_warnings_enabled' => $this->warningsEnabled,
            'is_menu_enabled' => $this->isMenuEnabled(),
            'is_logged_in' => $isLoggedIn,
            'menu' => $menu ?? '',
            'console' => $console,
            'messages' => $messages,
            'theme_color_mode' => $theme->getColorMode(),
            'theme_color_modes' => $theme->getColorModes(),
            'theme_id' => $theme->getId(),
            'current_user' => $this->dbi->getCurrentUserAndHost(),
            'is_mariadb' => $this->dbi->isMariaDB(),
        ];
    }

    /**
     * Returns the message to be displayed at the top of
     * the page, including the executed SQL query, if any.
     */
    public function getMessage(): string
    {
        $retval = '';
        $message = '';
        if (Current::$message !== null) {
            $message = Current::$message;
            Current::$message = null;
        } elseif (! empty($_REQUEST['message'])) {
            $message = $_REQUEST['message'];
        }

        if ($message !== '') {
            $retval .= Generator::getMessage($message);
        }

        return $retval;
    }

    /** @return array<string, string> */
    public function getHttpHeaders(string $currentDateTime = 'now'): array
    {
        $headers = [];

        /* Prevent against ClickJacking by disabling framing */
        if ($this->config->config->AllowThirdPartyFraming === 'sameorigin') {
            $headers['X-Frame-Options'] = 'SAMEORIGIN';
        } elseif ($this->config->config->AllowThirdPartyFraming !== true) {
            $headers['X-Frame-Options'] = 'DENY';
        }

        $headers['Referrer-Policy'] = 'same-origin';

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
        $headers['Permissions-Policy'] = 'fullscreen=(self), interest-cohort=()';

        $headers = array_merge($headers, Core::getNoCacheHeaders($currentDateTime));

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
                    $tempTitle = $this->config->config->TitleTable;
                } elseif (Current::$database !== '') {
                    $tempTitle = $this->config->config->TitleDatabase;
                } elseif ($this->config->selectedServer['host'] !== '') {
                    $tempTitle = $this->config->config->TitleServer;
                } else {
                    $tempTitle = $this->config->config->TitleDefault;
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
        $cspAllow = $this->config->config->CSPAllow;

        if (
            $this->config->config->CaptchaLoginPrivateKey !== ''
            && $this->config->config->CaptchaLoginPublicKey !== ''
            && $this->config->config->CaptchaApi !== ''
            && $this->config->config->CaptchaRequestParam !== ''
            && $this->config->config->CaptchaResponseParam !== ''
        ) {
            $captchaUrl = ' ' . $this->config->config->CaptchaCsp . ' ';
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

    private function getVariablesForJavaScript(): string
    {
        $maxInputVars = ini_get('max_input_vars');
        $maxInputVarsValue = $maxInputVars === false || $maxInputVars === '' ? 'false' : (int) $maxInputVars;

        return $this->template->render('javascript/variables', [
            'first_day_of_calendar' => $this->config->config->FirstDayOfCalendar,
            'max_input_vars' => $maxInputVarsValue,
        ]);
    }

    public function setIsTransformationWrapper(bool $isTransformationWrapper): void
    {
        $this->isTransformationWrapper = $isTransformationWrapper;
    }

    public function getConsole(): Console
    {
        return $this->console;
    }

    /**
     * Renders user configured footer
     */
    public static function renderHeader(): string
    {
        return Generator::renderCustom(CUSTOM_HEADER_FILE, 'pma_header');
    }
}
