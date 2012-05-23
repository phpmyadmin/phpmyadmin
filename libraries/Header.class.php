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
    private $_userprefs_offer_import;
    public $headerIsSent;

    private function __construct()
    {
        $this->_menu = PMA_Menu::getInstance();
        $this->_scripts = new PMA_Scripts();
        $this->headerIsSent = false;
        // if database storage for user preferences is transient,
        // offer to load exported settings from localStorage
        // (detection will be done in JavaScript)
        $this->_userprefs_offer_import = false;
        if ($GLOBALS['PMA_Config']->get('user_preferences') == 'session'
            && ! isset($_SESSION['userprefs_autoload'])
        ) {
            $this->_userprefs_offer_import = true;
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

    public function display()
    {
        echo $this->getDisplay();
    }

    public function getDisplay()
    {
        $retval = '';
        if (! $this->headerIsSent) {
            $this->headerIsSent = true;
            $this->sendHttpHeaders();
            if ($GLOBALS['is_ajax_request'] === false) {
                $retval .= $this->_getHtmlStart();
                $retval .= $this->_getMetaTags();
                $retval .= $this->_getLinkTags();
                $retval .= $this->_getTitleTag();
                if ($this->_userprefs_offer_import) {
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
                if ($this->_userprefs_offer_import) {
                    include_once './libraries/user_preferences.lib.php';
                    $retval .= PMA_userprefsAutoloadGetHeader();
                }
                // pass configuration for hint tooltip display
                // (to be used by PMA_createqTip in js/functions.js)
                if (! $GLOBALS['cfg']['ShowHint']) {
                    $retval .= '<span id="no_hint" class="hide"></span>';
                }
                $retval .= $this->_getWarnings();
                if (! defined('PMA_DISPLAY_HEADING')) {
                    define('PMA_DISPLAY_HEADING', 1);
                }
                if (PMA_DISPLAY_HEADING && $GLOBALS['server'] > 0) {
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
        $this->headerIsSent = true;
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
        if (! defined('IS_TRANSFORMATION_WRAPPER')) {
            // Define the charset to be used
            header('Content-Type: text/html; charset=utf-8');
        }
        PMA_no_cache_header();
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

        $retval .= '<link rel="stylesheet" type="text/css" href="'
            . $basedir . 'phpmyadmin.css.php'
            . $common_url . '&amp;nocache='
            . $theme_id . '" />';
        $retval .= '<link rel="stylesheet" type="text/css" href="'
            . $basedir . 'print.css" media="print" />';
        $retval .= '<link rel="stylesheet" type="text/css" href="'
            . $theme_path . '/jquery/jquery-ui-1.8.16.custom.css" />';

        return $retval;
    }

    private function _getTitleTag()
    {
        $retval = "<title>";
        if (empty($GLOBALS['page_title'])) {
            $retval .= 'phpMyAdmin';
        } else {
            $retval .= htmlspecialchars($GLOBALS['page_title']);
        }
        $retval .= "</title>";
        return $retval;
    }

    private function _getBodyStart()
    {
        return "</head><body>";
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
