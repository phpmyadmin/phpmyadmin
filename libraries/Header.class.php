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
require_once 'libraries/RecentTable.class.php';
require_once 'libraries/Menu.class.php';

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
     * @var object
     */
    private $_scripts;
    /**
     * PMA_Menu instance
     *
     * @access private
     * @var object
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
     *
     * @return new PMA_Header object
     */
    public function __construct()
    {
        $this->_isEnabled = true;
        $this->_isAjax = false;
        $this->_bodyId = '';
        $this->_title  = '';
        $this->_menu   = new PMA_Menu(
            $GLOBALS['server'],
            $GLOBALS['db'],
            $GLOBALS['table']
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
        $this->_scripts->addFile('jquery/jquery-1.6.2.js');
        $this->_scripts->addFile('jquery/jquery-ui-1.8.16.custom.js');
        $this->_scripts->addFile('jquery/jquery.sprintf.js');
        $this->_scripts->addFile('update-location.js');

        $this->_scripts->addFile('jquery/jquery.qtip-1.0.0-rc3.js');
        if ($GLOBALS['cfg']['CodemirrorEnable']) {
            $this->_scripts->addFile('codemirror/lib/codemirror.js');
            $this->_scripts->addFile('codemirror/mode/mysql/mysql.js');
        }
        // Cross-framing protection
        if ($GLOBALS['cfg']['AllowThirdPartyFraming'] === false) {
            $this->_scripts->addFile('cross_framing_protection.js');
        }
        // Localised strings
        $params = array('lang' => $GLOBALS['lang']);
        if (isset($GLOBALS['db'])) {
            $params['db'] = $GLOBALS['db'];
        }
        $this->_scripts->addFile('messages.php' . PMA_generate_common_url($params));
        // Append the theme id to this url to invalidate
        // the cache on a theme change
        $this->_scripts->addFile(
            'get_image.js.php?theme='
            . urlencode($_SESSION['PMA_Theme']->getId())
        );
        $this->_scripts->addFile('functions.js');
        $this->_scripts->addCode(
            PMA_CommonFunctions::getInstance()->getReloadNavigationScript(true)
        );
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
     * we are sevicing an ajax request
     *
     * @param bool $isAjax Whether we are sevicing an ajax request
     *
     * @return void
     */
    public function setAjax($isAjax)
    {
        $this->_isAjax = ($isAjax == true);
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
    public function disableMenu()
    {
        $this->_menuEnabled = false;
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
        $this->disableMenu();
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
                $retval .= $this->_getTitleTag();
                $title = PMA_sanitize(
                    PMA_escapeJsString($this->_getPageTitle()),
                    false,
                    true
                );
                $this->_scripts->addCode(
                    "if (typeof(parent.document) != 'undefined'"
                    . " && typeof(parent.document) != 'unknown'"
                    . " && typeof(parent.document.title) == 'string')"
                    . "{"
                    . "parent.document.title = '$title'"
                    . "}"
                );
                if ($this->_userprefsOfferImport) {
                    $this->_scripts->addFile('config.js');
                }
                $retval .= $this->_scripts->getDisplay();
                $retval .= $this->_getBodyStart();
                // Include possible custom headers
                if (file_exists(CUSTOM_HEADER_FILE)) {
                    ob_start();
                    include CUSTOM_HEADER_FILE;
                    $retval .= ob_get_contents();
                    ob_end_clean();
                }
                // offer to load user preferences from localStorage
                if ($this->_userprefsOfferImport) {
                    include_once './libraries/user_preferences.lib.php';
                    $retval .= PMA_userprefsAutoloadGetHeader();
                }
                // pass configuration for hint tooltip display
                // (to be used by PMA_createqTip in js/functions.js)
                if (! $GLOBALS['cfg']['ShowHint']) {
                    $retval .= '<span id="no_hint" class="hide"></span>';
                }
                $retval .= $this->_getWarnings();
                if ($this->_menuEnabled && $GLOBALS['server'] > 0) {
                    $retval .= $this->_menu->getDisplay();
                }
                $retval .= $this->_addRecentTable(
                    $GLOBALS['db'],
                    $GLOBALS['table']
                );
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
        /**
         * Sends http headers
         */
        $GLOBALS['now'] = gmdate('D, d M Y H:i:s') . ' GMT';
        /* Prevent against ClickJacking by allowing frames only from same origin */
        if (! $GLOBALS['cfg']['AllowThirdPartyFraming'] && ! defined('TESTSUITE')) {
            header(
                'X-Frame-Options: SAMEORIGIN'
            );
            header(
                "X-Content-Security-Policy: allow 'self'; "
                . "options inline-script eval-script; "
                . "frame-ancestors 'self'; img-src 'self' data:; "
                . "script-src 'self' http://www.phpmyadmin.net"
            );
            header(
                "X-WebKit-CSP: allow 'self' http://www.phpmyadmin.net; "
                . "options inline-script eval-script"
            );
        }
        PMA_noCacheHeader();
        if (! defined('IS_TRANSFORMATION_WRAPPER') && ! defined('TESTSUITE')) {
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
        $retval .= "<html lang='$lang' dir='$dir'>";

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
        $common_url = PMA_generate_common_url(array('server' => $GLOBALS['server']));
        $theme_id   = $GLOBALS['PMA_Config']->getThemeUniqueValue();
        $theme_path = $GLOBALS['pmaThemePath'];

        if ($this->_isPrintView) {
            $retval .= '<link rel="stylesheet" type="text/css" href="'
                . $basedir . 'print.css" media="print" />';
        } else {
            $retval .= '<link rel="stylesheet" type="text/css" href="'
                . $basedir . 'phpmyadmin.css.php'
                . $common_url . '&amp;nocache='
                . $theme_id . '" />';
            $retval .= '<link rel="stylesheet" type="text/css" href="'
                . $theme_path . '/jquery/jquery-ui-1.8.16.custom.css" />';
        }

        return $retval;
    }

    /**
     * Returns the TITLE tag
     *
     * @return string the TITLE tag
     */
    private function _getTitleTag()
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
                    PMA_CommonFunctions::getInstance()->expandUserString($temp_title)
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
            // message of "Cookies required" displayed for auth_type http or config
            // note: here, the decoration won't work because without cookies,
            // our standard CSS is not operational
            if (empty($_COOKIE)) {
                $retval .= PMA_Message::notice(
                    __('Cookies must be enabled past this point.')
                )->getDisplay();
            }
            $retval .= "<noscript>";
            $retval .= PMA_message::error(
                __("Javascript must be enabled past this point")
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
        if (strlen($table) && $GLOBALS['cfg']['LeftRecentTable'] > 0) {
            $tmp_result = PMA_RecentTable::getInstance()->add($db, $table);
            if ($tmp_result === true) {
                $retval = '<span class="hide" id="update_recent_tables"></span>';
            } else {
                $error  = $tmp_result;
                $retval = $error->getDisplay();
            }
        }
        return $retval;
    }
}

?>
