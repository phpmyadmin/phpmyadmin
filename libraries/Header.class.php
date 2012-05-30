<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/ob.lib.php';
require_once 'libraries/Scripts.class.php';
require_once 'libraries/RecentTable.class.php';
require_once 'libraries/Menu.class.php';


// FIXME: this global got lost :(
// here, the function does not exist with this configuration:
// $cfg['ServerDefault'] = 0;
$is_superuser = function_exists('PMA_isSuperuser') && PMA_isSuperuser();


class PMA_Header {
    private static $_instance;
    private $_scripts;
    private $_menu;
    private $_userprefsOfferImport;
    private $_title;
    private $_bodyId;
    private $_menuEnabled;
    private $_isPrintView;
    private $_isAjax;
    public static $headerIsSent;

    private function __construct()
    {
        $this->_isAjax = false;
        if (isset($_REQUEST['ajax_request']) && $_REQUEST['ajax_request'] == true) {
            $this->_isAjax = true;
        }
        $this->_bodyId = '';
        $this->_title = 'phpMyAdmin';
        $this->_menu = new PMA_Menu(
            $GLOBALS['server'],
            $GLOBALS['db'],
            $GLOBALS['table']
        );
        $_isPrintView = false;
        $this->_menuEnabled = true;
        $this->_isPrintView = false;
        $this->_scripts = new PMA_Scripts();
        self::$headerIsSent = false;
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

    public static function getInstance()
    {
        if (empty(self::$_instance)) {
            self::$_instance = new PMA_Header();
        }
        return self::$_instance;
    }

    public function getScripts()
    {
        return $this->_scripts;
    }

    public function setBodyId($id)
    {
        $this->_bodyId = htmlspecialchars($id);
    }

    public function setTitle($title)
    {
        $this->_title = htmlspecialchars($title);
    }

    public function disableMenu()
    {
        $this->_menuEnabled = false;
    }

    public function enablePrintView()
    {
        $this->disableMenu();
        $this->setTitle(__('Print view') . ' - phpMyAdmin ' . PMA_VERSION);
        $this->_isPrintView = true;
    }

    public function display()
    {
        echo $this->getDisplay();
    }

    public function getDisplay()
    {
        $retval = '';
        if (! self::$headerIsSent) {
            $this->sendHttpHeaders();
            if ($this->_isAjax === false) {
                $retval .= $this->_getHtmlStart();
                $retval .= $this->_getMetaTags();
                $retval .= $this->_getLinkTags();
                $retval .= $this->_getTitleTag();
                if ($this->_userprefsOfferImport) {
                    $this->_scripts->addFile('config.js');
                }
                $retval .= $this->_scripts->getDisplay();
                $retval .= $this->_getBodyStart();
                // Include possible custom headers
                if (file_exists(CUSTOM_HEADER_FILE)) {
                    ob_start();
                    include CUSTOM_HEADER_FILE;
                    $retval .= ob_end_clean();
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

    public function sendHttpHeaders()
    {
        /**
         * Starts output buffering work
         */
        PMA_outBufferPre();
        /**
         * Sends http headers
         */
        $GLOBALS['now'] = gmdate('D, d M Y H:i:s') . ' GMT';
        /* Prevent against ClickJacking by allowing frames only from same origin */
        if (! $GLOBALS['cfg']['AllowThirdPartyFraming']) {
            header('X-Frame-Options: SAMEORIGIN');
            header("X-Content-Security-Policy: allow 'self'; options inline-script eval-script; frame-ancestors 'self'; img-src 'self' data:; script-src 'self' http://www.phpmyadmin.net");
            header("X-WebKit-CSP: allow 'self' http://www.phpmyadmin.net; options inline-script eval-script");
        }
        PMA_noCacheHeader();
        if (! defined('IS_TRANSFORMATION_WRAPPER')) {
            // Define the charset to be used
            header('Content-Type: text/html; charset=utf-8');
        }
        self::$headerIsSent = true;
    }

    private function _getHtmlStart()
    {
        $lang = $GLOBALS['available_languages'][$GLOBALS['lang']][1];
        $dir = $GLOBALS['text_dir'];

        $retval  = "<!DOCTYPE HTML>";
        $retval .= "<html lang='$lang' dir='$dir'>";

        return $retval;
    }

    private function _getMetaTags()
    {
        $retval  = '<meta charset="utf-8" />';
        $retval .= '<meta name="robots" content="noindex,nofollow" />';
        return $retval;
    }

    private function _getLinkTags()
    {
        $retval  = '<link rel="icon" href="favicon.ico" type="image/x-icon" />';
        $retval .= '<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />';

        // stylesheets
        $basedir = defined('PMA_PATH_TO_BASEDIR') ? PMA_PATH_TO_BASEDIR : '';
        $common_url = PMA_generate_common_url(array('server' => $GLOBALS['server']));
        $theme_id = $GLOBALS['PMA_Config']->getThemeUniqueValue();
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

    private function _getTitleTag()
    {
        $retval = "<title>";
        if (! empty($GLOBALS['page_title'])) {
            $retval .= htmlspecialchars($GLOBALS['page_title']);
        } else {
            $retval .= $this->_title;
        }
        $retval .= "</title>";
        return $retval;
    }

    private function _getBodyStart()
    {
        $retval = "</head><body";
        if (! empty($this->_bodyId)) {
            $retval .= " id='" . $this->_bodyId . "'";
        }
        $retval .= ">";
        return $retval;
    }

    private function _getWarnings()
    {
        $retval = '';
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
        return $retval;
    }

    /**
     * Add recently used table and reload the navigation.
     *
     * @param string $db Database name where the table is located.
     * @param string $table The table name
     */
    private function _addRecentTable($db, $table)
    {
        $retval = '';
        if (strlen($table) && $GLOBALS['cfg']['LeftRecentTable'] > 0) {
            $tmp_result = PMA_RecentTable::getInstance()->add($db, $table);
            if ($tmp_result === true) {
                $retval = '<span class="hide" id="update_recent_tables"></span>';
            } else {
                $error = $tmp_result;
                $retval = $error->getDisplay();
            }
        }
        return $retval;
    }
}

?>
