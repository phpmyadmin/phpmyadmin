<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Used to render the footer of PMA's pages
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/Footnotes.class.php';

/**
 * Singleton class used to output the footer
 *
 * @package PhpMyAdmin
 */
class PMA_Footer
{
    /**
     * PMA_Footer instance
     *
     * @access private
     * @static
     * @var object
     */
    private static $_instance;

    /**
     * PMA_Footnotes instance
     *
     * @access private
     * @var object
     */
    private $_footnotes;

    /**
     * PMA_Scripts instance
     *
     * @access private
     * @var object
     */
    private $_scripts;

    /**
     * Cretes a new class instance
     *
     * @return new PMA_Footer object
     */
    private function __construct()
    {
        $this->_footnotes = new PMA_Footnotes();
        $this->_scripts   = new PMA_Scripts();
        $this->_addDefaultScripts();
    }

    /**
     * Loads common scripts
     *
     * @return void
     */
    private function _addDefaultScripts()
    {
       // $this->_scripts->addFile();

        if (empty($GLOBALS['error_message'])) {
            $this->_scripts->addCode("
                $(function() {
                // updates current settings
                if (window.parent.setAll) {
                    window.parent.setAll(
                        '" . PMA_escapeJsString($GLOBALS['lang']) . "',
                        '" . PMA_escapeJsString($GLOBALS['collation_connection']) . "',
                        '" . PMA_escapeJsString($GLOBALS['server']) . "',
                        '" . PMA_escapeJsString(PMA_ifSetOr($GLOBALS['db'], '')) . "',
                        '" . PMA_escapeJsString(PMA_ifSetOr($GLOBALS['table'], '')) . "',
                        '" . PMA_escapeJsString($_SESSION[' PMA_token ']) . "'
                    );
                }
                });"
            );
            if (! empty($GLOBALS['reload'])) {
                $this->_scripts->addCode("
                    // refresh navigation frame content
                    if (window.parent.refreshNavigation) {
                        window.parent.refreshNavigation();
                    }
                ");
            } else if (isset($_GET['reload_left_frame'])
                && $_GET['reload_left_frame'] == '1'
            ) {
                // reload left frame (used by user preferences)
                $this->_scripts->addCode("
                    if (window.parent && window.parent.frame_navigation) {
                        window.parent.frame_navigation.location.reload();
                    }
                ");
            }

            // set current db, table and sql query in the querywindow
            $query = '';
            if (strlen($GLOBALS['sql_query']) > $GLOBALS['cfg']['MaxCharactersInDisplayedSQL']) {
                $query = PMA_escapeJsString($GLOBALS['sql_query']);
            }
            $this->_scripts->addCode("
                if (window.parent.reload_querywindow) {
                    window.parent.reload_querywindow(
                        '" . PMA_escapeJsString(PMA_ifSetOr($GLOBALS['db'], '')) . "',
                        '" . PMA_escapeJsString(PMA_ifSetOr($GLOBALS['table'], '')) . "',
                        '" . $query . "'
                    );
                }
            ");

            if (! empty($GLOBALS['focus_querywindow'])) {
                // set focus to the querywindow
                $this->_scripts->addCode("
                    if (parent.querywindow && !parent.querywindow.closed
                        && parent.querywindow.location
                    ) {
                        self.focus();
                    }
                ");
            }
            $this->_scripts->addCode("
                if (window.parent.frame_content) {
                    // reset content frame name, as querywindow needs
                    // to set a unique name before submitting form data,
                    // and navigation frame needs the original name
                    if (typeof(window.parent.frame_content.name) != 'undefined'
                     && window.parent.frame_content.name != 'frame_content') {
                        window.parent.frame_content.name = 'frame_content';
                    }
                    if (typeof(window.parent.frame_content.id) != 'undefined'
                     && window.parent.frame_content.id != 'frame_content') {
                        window.parent.frame_content.id = 'frame_content';
                    }
                    //window.parent.frame_content.setAttribute('name', 'frame_content');
                    //window.parent.frame_content.setAttribute('id', 'frame_content');
                }
            ");
        }
    }

    /**
     * Renders the debug messages
     *
     * @return string
     */
    private function _getDebugMessage()
    {
        $retval = '';
        if (! empty($_SESSION['debug'])) {
            $sum_time = 0;
            $sum_exec = 0;
            foreach ($_SESSION['debug']['queries'] as $query) {
                $sum_time += $query['count'] * $query['time'];
                $sum_exec += $query['count'];
            }

            $retval .= '<div>';
            $retval .= count($_SESSION['debug']['queries']) . ' queries executed ';
            $retval .= $sum_exec . ' times in ' . $sum_time . ' seconds';
            $retval .= '<pre>';

            ob_start();
            print_r($_SESSION['debug']);
            $retval .= ob_end_clean();

            $retval .= '</pre>';
            $retval .= '</div>';
            $_SESSION['debug'] = array();
        }
        return $retval;
    }

    /**
     * Renders the link to open a new page
     *
     * @return string
     */
    private function _getSelfLink($url_params)
    {
        $retval = '';
        $retval .= '<div id="selflink" class="print_ignore">';
        $retval .= '<a href="index.php' . PMA_generate_common_url($url_params) . '"'
            . ' title="' . __('Open new phpMyAdmin window') . '" target="_blank">';
        if ($GLOBALS['cfg']['NavigationBarIconic']) {
            $retval .= PMA_getImage('window-new.png', __('Open new phpMyAdmin window'));
        } else {
            $retval .=  __('Open new phpMyAdmin window');
        }
        $retval .= '</a>';
        $retval .= '</div>';
        return $retval;
    }

    /**
     * Returns the singleton PMA_Footer object
     *
     * @return PMA_Footer object
     */
    public static function getInstance()
    {
        if (empty(self::$_instance)) {
            self::$_instance = new PMA_Footer();
        }
        return self::$_instance;
    }

    /**
     * Returns the PMA_Footnotes object
     *
     * @return PMA_Footnotes object
     */
    public function getFootnotes()
    {
        return $this->_footnotes;
    }

    /**
     * Renders the footer
     *
     * @return string
     */
    public function getDisplay()
    {
        $retval = '';
        if ($GLOBALS['is_ajax_request'] != true) {
            // Link to itself to replicate windows including frameset
            if (! isset($GLOBALS['checked_special'])) {
                $GLOBALS['checked_special'] = false;
            }
            if (PMA_getenv('SCRIPT_NAME') && empty($_POST)
                && ! $GLOBALS['checked_special']
                && ! $GLOBALS['is_ajax_request']
            ) {
                $url_params['target'] = basename(PMA_getenv('SCRIPT_NAME'));
                $this->_scripts->addCode("
                    // Store current location in hash part
                    // of URL to allow direct bookmarking
                    setURLHash('" . PMA_generate_common_url($url_params, 'text', '') . "');
                ");
                $retval .= $this->_getSelfLink($url_params);
            }
            $retval .= $this->_getDebugMessage();
            $retval .= $this->_footnotes->getDisplay();
            $retval .= $this->_scripts->getDisplay();
            // Include possible custom footers
            if (file_exists(CUSTOM_FOOTER_FILE)) {
                ob_start();
                include CUSTOM_FOOTER_FILE;
                $retval .= ob_end_clean();
            }
            $retval .= "</body></html>";
        }
        return $retval;
    }

    /**
     * Renders and displays the footer
     *
     * @return void
     */
    public function display()
    {
        echo $this->getDisplay();
        exit;
    }
}
