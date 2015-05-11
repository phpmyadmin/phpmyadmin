<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Used to render the header of PMA's pages
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/Scripts.class.php';
require_once 'libraries/RecentFavoriteTable.class.php';
require_once 'libraries/Menu.class.php';
require_once 'libraries/Console.class.php';
require_once 'libraries/navigation/Navigation.class.php';
require_once 'libraries/url_generating.lib.php';


/**
 * Class used to output the HTTP and HTML headers
 *
 * @package PhpMyAdmin
 */
class PMA_Header
{
    /**
     * PMA_Scripts instance
     *
     * @access private
     * @var PMA_Scripts
     */
    private $_scripts;
    /**
     * PMA_Console instance
     *
     * @access private
     * @var PMA_Console
     */
    private $_console;
    /**
     * PMA_Menu instance
     *
     * @access private
     * @var PMA_Menu
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
     * We can't simply use $GLOBALS['is_ajax_request']
     * here since it may have not been initialised yet.
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
     * Creates a new class instance
     */
    public function __construct()
    {
        $this->_isEnabled = true;
        $this->_isAjax = false;
        $this->_bodyId = '';
        $this->_title  = '';
        $this->_console = new PMA_Console();
        $db = ! empty($GLOBALS['db']) ? $GLOBALS['db'] : '';
        $table = ! empty($GLOBALS['table']) ? $GLOBALS['table'] : '';
        $this->_menu   = new PMA_Menu(
            $GLOBALS['server'],
            $db,
            $table
        );
        $this->_menuEnabled = true;
        $this->_warningsEnabled = true;
        $this->_isPrintView = false;
        $this->_scripts     = new PMA_Scripts();
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
    }

    /**
     * Loads common scripts
     *
     * @return void
     */
    private function _addDefaultScripts()
    {
        // Localised strings
        $params = array('lang' => $GLOBALS['lang']);
        if (isset($GLOBALS['db'])) {
            $params['db'] = $GLOBALS['db'];
        }
        $this->_scripts->addFile('jquery/jquery-1.11.1.min.js');
        $this->_scripts->addFile(
            'whitelist.php' . PMA_URL_getCommon($params), false, true
        );
        $this->_scripts->addFile('sprintf.js');
        $this->_scripts->addFile('ajax.js');
        $this->_scripts->addFile('keyhandler.js');
        $this->_scripts->addFile('jquery/jquery-ui-1.11.2.min.js');
        $this->_scripts->addFile('jquery/jquery.cookie.js');
        $this->_scripts->addFile('jquery/jquery.mousewheel.js');
        $this->_scripts->addFile('jquery/jquery.event.drag-2.2.js');
        $this->_scripts->addFile('jquery/jquery-ui-timepicker-addon.js');
        $this->_scripts->addFile('jquery/jquery.ba-hashchange-1.3.js');
        $this->_scripts->addFile('jquery/jquery.debounce-1.0.5.js');
        $this->_scripts->addFile('menu-resizer.js');

        // Cross-framing protection
        if ($GLOBALS['cfg']['AllowThirdPartyFraming'] === false) {
            $this->_scripts->addFile('cross_framing_protection.js');
        }

        $this->_scripts->addFile('rte.js');
        if ($GLOBALS['cfg']['SendErrorReports'] !== 'never') {
            $this->_scripts->addFile('tracekit/tracekit.js');
            $this->_scripts->addFile('error_report.js');
        }

        // Here would not be a good place to add CodeMirror because
        // the user preferences have not been merged at this point

        $this->_scripts->addFile('messages.php' . PMA_URL_getCommon($params));
        // Append the theme id to this url to invalidate
        // the cache on a theme change. Though this might be
        // unavailable for fatal errors.
        if (isset($_SESSION['PMA_Theme'])) {
            $theme_id = urlencode($_SESSION['PMA_Theme']->getId());
        } else {
            $theme_id = 'default';
        }
        $this->_scripts->addFile(
            'get_image.js.php?theme=' . $theme_id
        );
        $this->_scripts->addFile('doclinks.js');
        $this->_scripts->addFile('functions.js');
        $this->_scripts->addFile('navigation.js');
        $this->_scripts->addFile('indexes.js');
        $this->_scripts->addFile('common.js');
        $this->_scripts->addCode($this->getJsParamsCode());
    }

    /**
     * Returns, as an array, a list of parameters
     * used on the client side
     *
     * @return array
     */
    public function getJsParams()
    {
        $db = ! empty($GLOBALS['db']) ? $GLOBALS['db'] : '';
        $table = ! empty($GLOBALS['table']) ? $GLOBALS['table'] : '';
        $pftext = ! empty($_SESSION['tmpval']['pftext'])
            ? $_SESSION['tmpval']['pftext'] : '';

        // not sure when this happens, but it happens
        if (! isset($GLOBALS['collation_connection'])) {
            $GLOBALS['collation_connection'] = 'utf8_general_ci';
        }

        $params = array(
            'common_query' => PMA_URL_getCommon(array(), 'text'),
            'opendb_url' => $GLOBALS['cfg']['DefaultTabDatabase'],
            'safari_browser' => PMA_USR_BROWSER_AGENT == 'SAFARI' ? 1 : 0,
            'collation_connection' => $GLOBALS['collation_connection'],
            'lang' => $GLOBALS['lang'],
            'server' => $GLOBALS['server'],
            'table' => $table,
            'db'    => $db,
            'token' => $_SESSION[' PMA_token '],
            'text_dir' => $GLOBALS['text_dir'],
            'show_databases_navigation_as_tree'=> $GLOBALS['cfg']['ShowDatabasesNavigationAsTree'],
            'pma_absolute_uri' => $GLOBALS['cfg']['PmaAbsoluteUri'],
            'pma_text_default_tab' => PMA_Util::getTitleForTarget(
                $GLOBALS['cfg']['DefaultTabTable']
            ),
            'pma_text_left_default_tab' => PMA_Util::getTitleForTarget(
                $GLOBALS['cfg']['NavigationTreeDefaultTabTable']
            ),
            'pma_text_left_default_tab2' => PMA_Util::getTitleForTarget(
                $GLOBALS['cfg']['NavigationTreeDefaultTabTable2']
            ),
            'LimitChars' => $GLOBALS['cfg']['LimitChars'],
            'pftext' => $pftext,
            'confirm' => $GLOBALS['cfg']['Confirm'],
            'LoginCookieValidity' => $GLOBALS['cfg']['LoginCookieValidity'],
            'logged_in' => isset($GLOBALS['userlink']) ? true : false
        );
        if (isset($GLOBALS['cfg']['Server'])
            && isset($GLOBALS['cfg']['Server']['auth_type'])
        ) {
            $params['auth_type'] = $GLOBALS['cfg']['Server']['auth_type'];
        }

        return $params;
    }

    /**
     * Returns, as a string, a list of parameters
     * used on the client side
     *
     * @return string
     */
    public function getJsParamsCode()
    {
        $params = $this->getJsParams();
        foreach ($params as $key => $value) {
            $params[$key] = $key . ':"' . PMA_escapeJsString($value) . '"';
        }
        return 'PMA_commonParams.setAll({' . implode(',', $params) . '});';
    }

    /**
     * Disables the rendering of the header
     *
     * @return void
     */
    public function disable()
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
    public function setAjax($isAjax)
    {
        $this->_isAjax = ($isAjax == true);
        $this->_console->setAjax($isAjax);
    }

    /**
     * Returns the PMA_Scripts object
     *
     * @return PMA_Scripts object
     */
    public function getScripts()
    {
        return $this->_scripts;
    }

    /**
     * Returns the PMA_Menu object
     *
     * @return PMA_Menu object
     */
    public function getMenu()
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
    public function setBodyId($id)
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
    public function setTitle($title)
    {
        $this->_title = htmlspecialchars($title);
    }

    /**
     * Disables the display of the top menu
     *
     * @return void
     */
    public function disableMenuAndConsole()
    {
        $this->_menuEnabled = false;
        $this->_console->disable();
    }

    /**
     * Disables the display of the top menu
     *
     * @return void
     */
    public function disableWarnings()
    {
        $this->_warningsEnabled = false;
    }

    /**
     * Turns on 'print view' mode
     *
     * @return void
     */
    public function enablePrintView()
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
    public function getDisplay()
    {
        $retval = '';
        if (! $this->_headerIsSent) {
            if (! $this->_isAjax && $this->_isEnabled) {
                $this->sendHttpHeaders();
                $retval .= $this->_getHtmlStart();
                $retval .= $this->_getMetaTags();
                $retval .= $this->_getLinkTags();
                $retval .= $this->getTitleTag();

                // The user preferences have been merged at this point
                // so we can conditionally add CodeMirror
                if ($GLOBALS['cfg']['CodemirrorEnable']) {
                    $this->_scripts->addFile('codemirror/lib/codemirror.js');
                    $this->_scripts->addFile('codemirror/mode/sql/sql.js');
                    $this->_scripts->addFile('codemirror/addon/runmode/runmode.js');
                    $this->_scripts->addFile('codemirror/addon/hint/show-hint.js');
                    $this->_scripts->addFile('codemirror/addon/hint/sql-hint.js');
                }
                $this->_scripts->addFiles($this->_console->getScripts());
                if ($this->_userprefsOfferImport) {
                    $this->_scripts->addFile('config.js');
                }
                $retval .= $this->_scripts->getDisplay();
                $retval .= '<noscript>';
                $retval .= '<style>html{display:block}</style>';
                $retval .= '</noscript>';
                $retval .= $this->_getBodyStart();
                if ($this->_menuEnabled && $GLOBALS['server'] > 0) {
                    $nav = new PMA_Navigation();
                    $retval .= $nav->getDisplay();
                }
                // Include possible custom headers
                if (file_exists(CUSTOM_HEADER_FILE)) {
                    $retval .= '<div id="pma_header">';
                    ob_start();
                    include CUSTOM_HEADER_FILE;
                    $retval .= ob_get_contents();
                    ob_end_clean();
                    $retval .= '</div>';
                }
                // offer to load user preferences from localStorage
                if ($this->_userprefsOfferImport) {
                    include_once './libraries/user_preferences.lib.php';
                    $retval .= PMA_userprefsAutoloadGetHeader();
                }
                // pass configuration for hint tooltip display
                // (to be used by PMA_tooltip() in js/functions.js)
                if (! $GLOBALS['cfg']['ShowHint']) {
                    $retval .= '<span id="no_hint" class="hide"></span>';
                }
                $retval .= $this->_getWarnings();
                if ($this->_menuEnabled && $GLOBALS['server'] > 0) {
                    $retval .= $this->_menu->getDisplay();
                    $retval .= '<span id="lock_page_icon"></span>';
                    $retval .= sprintf(
                        '<a id="goto_pagetop" href="#">%s</a>',
                        PMA_Util::getImage(
                            's_top.png',
                            __('Click on the bar to scroll to top of page')
                        )
                    );
                }
                $retval .= $this->_console->getDisplay();
                $retval .= '<div id="page_content">';
                $retval .= $this->getMessage();
            }
            if ($this->_isEnabled && empty($_REQUEST['recent_table'])) {
                $retval .= $this->_addRecentTable(
                    $GLOBALS['db'],
                    $GLOBALS['table']
                );
            }
        }
        return $retval;
    }

    /**
     * Returns the message to be displayed at the top of
     * the page, including the executed SQL query, if any.
     *
     * @return string
     */
    public function getMessage()
    {
        $retval = '';
        $message = '';
        if (! empty($GLOBALS['message'])) {
            $message = $GLOBALS['message'];
            unset($GLOBALS['message']);
        } else if (! empty($_REQUEST['message'])) {
            $message = $_REQUEST['message'];
        }
        if (! empty($message)) {
            if (isset($GLOBALS['buffer_message'])) {
                $buffer_message = $GLOBALS['buffer_message'];
            }
            $retval .= PMA_Util::getMessage($message);
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
    public function sendHttpHeaders()
    {
        if (defined('TESTSUITE') && ! defined('PMA_TEST_HEADERS')) {
            return;
        }
        if ($GLOBALS['PMA_Config']->isHttps()) {
            $map_tile_urls = '';
        } else {
            $map_tile_urls = ' *.tile.openstreetmap.org *.tile.opencyclemap.org';
        }

        /**
         * Sends http headers
         */
        $GLOBALS['now'] = gmdate('D, d M Y H:i:s') . ' GMT';
        if (!empty($GLOBALS['cfg']['CaptchaLoginPrivateKey'])
            && !empty($GLOBALS['cfg']['CaptchaLoginPublicKey'])
        ) {
            $captcha_url = ' https://apis.google.com https://www.google.com/recaptcha/'
               . ' https://www.gstatic.com/recaptcha/ https://ssl.gstatic.com/ ';
        } else {
            $captcha_url = '';
        }
        /* Prevent against ClickJacking by disabling framing */
        if (! $GLOBALS['cfg']['AllowThirdPartyFraming']) {
            header(
                'X-Frame-Options: DENY'
            );
        }
        header(
            "Content-Security-Policy: default-src 'self' "
            . $captcha_url
            . $GLOBALS['cfg']['CSPAllow'] . ';'
            . "script-src 'self' 'unsafe-inline' 'unsafe-eval' "
            . $captcha_url
            . $GLOBALS['cfg']['CSPAllow'] . ';'
            . ";"
            . "style-src 'self' 'unsafe-inline' "
            . $captcha_url
            . $GLOBALS['cfg']['CSPAllow']
            . ";"
            . "img-src 'self' data: "
            . $GLOBALS['cfg']['CSPAllow']
            . $map_tile_urls
            . $captcha_url
            . ";"
        );
        header(
            "X-Content-Security-Policy: default-src 'self' "
            . $captcha_url
            . $GLOBALS['cfg']['CSPAllow'] . ';'
            . "options inline-script eval-script;"
            . "img-src 'self' data: "
            . $GLOBALS['cfg']['CSPAllow']
            . $map_tile_urls
            . $captcha_url
            . ";"
        );
        header(
            "X-WebKit-CSP: default-src 'self' "
            . $captcha_url
            . $GLOBALS['cfg']['CSPAllow'] . ';'
            . "script-src 'self' "
            . $captcha_url
            . $GLOBALS['cfg']['CSPAllow']
            . " 'unsafe-inline' 'unsafe-eval';"
            . "style-src 'self' 'unsafe-inline' "
            . $captcha_url
            . ';'
            . "img-src 'self' data: "
            . $GLOBALS['cfg']['CSPAllow']
            . $map_tile_urls
            . $captcha_url
            . ";"
        );
        PMA_noCacheHeader();
        if (! defined('IS_TRANSFORMATION_WRAPPER')) {
            // Define the charset to be used
            header('Content-Type: text/html; charset=utf-8');
        }
        $this->_headerIsSent = true;
    }

    /**
     * Returns the DOCTYPE and the start HTML tag
     *
     * @return string DOCTYPE and HTML tags
     */
    private function _getHtmlStart()
    {
        $lang = $GLOBALS['available_languages'][$GLOBALS['lang']][1];
        $dir  = $GLOBALS['text_dir'];

        $retval  = "<!DOCTYPE HTML>";
        $retval .= "<html lang='$lang' dir='$dir' class='";
        $retval .= /*overload*/mb_strtolower(PMA_USR_BROWSER_AGENT) . " ";
        $retval .= /*overload*/mb_strtolower(PMA_USR_BROWSER_AGENT)
            . intval(PMA_USR_BROWSER_VER) . "'>";

        return $retval;
    }

    /**
     * Returns the META tags
     *
     * @return string the META tags
     */
    private function _getMetaTags()
    {
        $retval  = '<meta charset="utf-8" />';
        $retval .= '<meta name="robots" content="noindex,nofollow" />';
        $retval .= '<meta http-equiv="X-UA-Compatible" content="IE=Edge">';
        if (! $GLOBALS['cfg']['AllowThirdPartyFraming']) {
            $retval .= '<style id="cfs-style">html{display: none;}</style>';
        }
        return $retval;
    }

    /**
     * Returns the LINK tags for the favicon and the stylesheets
     *
     * @return string the LINK tags
     */
    private function _getLinkTags()
    {
        $retval = '<link rel="icon" href="favicon.ico" '
            . 'type="image/x-icon" />'
            . '<link rel="shortcut icon" href="favicon.ico" '
            . 'type="image/x-icon" />';
        // stylesheets
        $basedir    = defined('PMA_PATH_TO_BASEDIR') ? PMA_PATH_TO_BASEDIR : '';
        $theme_id   = $GLOBALS['PMA_Config']->getThemeUniqueValue();
        $theme_path = $GLOBALS['pmaThemePath'];

        if ($this->_isPrintView) {
            $retval .= '<link rel="stylesheet" type="text/css" href="'
                . $basedir . 'print.css" />';
        } else {
            // load jQuery's CSS prior to our theme's CSS, to let the theme
            // override jQuery's CSS
            $retval .= '<link rel="stylesheet" type="text/css" href="'
                . $theme_path . '/jquery/jquery-ui-1.11.2.css" />';
            $retval .= '<link rel="stylesheet" type="text/css" href="'
                . $basedir . 'phpmyadmin.css.php?'
                . 'nocache=' . $theme_id . $GLOBALS['text_dir'] . '" />';
        }

        return $retval;
    }

    /**
     * Returns the TITLE tag
     *
     * @return string the TITLE tag
     */
    public function getTitleTag()
    {
        $retval  = "<title>";
        $retval .= $this->_getPageTitle();
        $retval .= "</title>";
        return $retval;
    }

    /**
     * If the page is missing the title, this function
     * will set it to something reasonable
     *
     * @return string
     */
    private function _getPageTitle()
    {
        if (empty($this->_title)) {
            if ($GLOBALS['server'] > 0) {
                if (! empty($GLOBALS['table'])) {
                    $temp_title = $GLOBALS['cfg']['TitleTable'];
                } else if (! empty($GLOBALS['db'])) {
                    $temp_title = $GLOBALS['cfg']['TitleDatabase'];
                } elseif (! empty($GLOBALS['cfg']['Server']['host'])) {
                    $temp_title = $GLOBALS['cfg']['TitleServer'];
                } else {
                    $temp_title = $GLOBALS['cfg']['TitleDefault'];
                }
                $this->_title = htmlspecialchars(
                    PMA_Util::expandUserString($temp_title)
                );
            } else {
                $this->_title = 'phpMyAdmin';
            }
        }
        return $this->_title;
    }

    /**
     * Returns the close tag to the HEAD
     * and the start tag for the BODY
     *
     * @return string HEAD and BODY tags
     */
    private function _getBodyStart()
    {
        $retval = "</head><body";
        if (! empty($this->_bodyId)) {
            $retval .= " id='" . $this->_bodyId . "'";
        }
        $retval .= ">";
        return $retval;
    }

    /**
     * Returns some warnings to be displayed at the top of the page
     *
     * @return string The warnings
     */
    private function _getWarnings()
    {
        $retval = '';
        if ($this->_warningsEnabled) {
            $retval .= "<noscript>";
            $retval .= PMA_message::error(
                __("Javascript must be enabled past this point!")
            )->getDisplay();
            $retval .= "</noscript>";
        }
        return $retval;
    }

    /**
     * Add recently used table and reload the navigation.
     *
     * @param string $db    Database name where the table is located.
     * @param string $table The table name
     *
     * @return string
     */
    private function _addRecentTable($db, $table)
    {
        $retval = '';
        if ($this->_menuEnabled
            && /*overload*/mb_strlen($table)
            && $GLOBALS['cfg']['NumRecentTables'] > 0
        ) {
            $tmp_result = PMA_RecentFavoriteTable::getInstance('recent')
                              ->add($db, $table);
            if ($tmp_result === true) {
                $retval = PMA_RecentFavoriteTable::getHtmlUpdateRecentTables();
            } else {
                $error  = $tmp_result;
                $retval = $error->getDisplay();
            }
        }
        return $retval;
    }
}

?>
