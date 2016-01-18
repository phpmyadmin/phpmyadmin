<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Used to render the footer of PMA's pages
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries;

use Traversable;

/**
 * Class used to output the footer
 *
 * @package PhpMyAdmin
 */
class Footer
{
    /**
     * Scripts instance
     *
     * @access private
     * @var Scripts
     */
    private $_scripts;
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
     * Whether to only close the BODY and HTML tags
     * or also include scripts, errors and links
     *
     * @access private
     * @var bool
     */
    private $_isMinimal;
    /**
     * Whether to display anything
     *
     * @access private
     * @var bool
     */
    private $_isEnabled;

    /**
     * Creates a new class instance
     */
    public function __construct()
    {
        $this->_isEnabled = true;
        $this->_scripts   = new Scripts();
        $this->_isMinimal = false;
    }

    /**
     * Returns the message for demo server to error messages
     *
     * @return string
     */
    private function _getDemoMessage()
    {
        $message = '<a href="/">' . __('phpMyAdmin Demo Server') . '</a>: ';
        if (file_exists('./revision-info.php')) {
            include './revision-info.php';
            $message .= sprintf(
                __('Currently running Git revision %1$s from the %2$s branch.'),
                '<a target="_blank" href="' . $repobase . $fullrevision . '">'
                . $revision . '</a>',
                '<a target="_blank" href="' . $repobranchbase . $branch . '">'
                . $branch . '</a>'
            );
        } else {
            $message .= __('Git information missing!');
        }

        return Message::notice($message)->getDisplay();
    }

    /**
     * Remove recursions and iterator objects from an object
     *
     * @param object|array &$object Object to clean
     * @param array        $stack   Stack used to keep track of recursion,
     *                              need not be passed for the first time
     *
     * @return object Reference passed object
     */
    private static function _removeRecursion(&$object, $stack = array())
    {
        if ((is_object($object) || is_array($object)) && $object) {
            if ($object instanceof Traversable) {
                $object = "***ITERATOR***";
            } else if (!in_array($object, $stack, true)) {
                $stack[] = $object;
                foreach ($object as &$subobject) {
                    self::_removeRecursion($subobject, $stack);
                }
            } else {
                $object = "***RECURSION***";
            }
        }
        return $object;
    }

    /**
     * Renders the debug messages
     *
     * @return string
     */
    public function getDebugMessage()
    {
        $retval = '\'null\'';
        if ($GLOBALS['cfg']['DBG']['sql']
            && empty($_REQUEST['no_debug'])
            && !empty($_SESSION['debug'])
        ) {
            // Remove recursions and iterators from $_SESSION['debug']
            self::_removeRecursion($_SESSION['debug']);

            $retval = JSON_encode($_SESSION['debug']);
            $_SESSION['debug'] = array();
            return json_last_error() ? '\'false\'' : $retval;
        }
        $_SESSION['debug'] = array();
        return $retval;
    }

    /**
     * Returns the url of the current page
     *
     * @param string|null $encode See PMA_URL_getCommon()
     *
     * @return string
     */
    public function getSelfUrl($encode = 'html')
    {
        $db = ! empty($GLOBALS['db']) ? $GLOBALS['db'] : '';
        $table = ! empty($GLOBALS['table']) ? $GLOBALS['table'] : '';
        $target = ! empty($_REQUEST['target']) ? $_REQUEST['target'] : '';
        $params = array(
            'db' => $db,
            'table' => $table,
            'server' => $GLOBALS['server'],
            'target' => $target
        );
        // needed for server privileges tabs
        if (isset($_REQUEST['viewing_mode'])
            && in_array($_REQUEST['viewing_mode'], array('server', 'db', 'table'))
        ) {
            $params['viewing_mode'] = $_REQUEST['viewing_mode'];
        }
        /*
         * @todo    coming from server_privileges.php, here $db is not set,
         *          add the following condition below when that is fixed
         *          && $_REQUEST['checkprivsdb'] == $db
         */
        if (isset($_REQUEST['checkprivsdb'])
        ) {
            $params['checkprivsdb'] = $_REQUEST['checkprivsdb'];
        }
        /*
         * @todo    coming from server_privileges.php, here $table is not set,
         *          add the following condition below when that is fixed
         *          && $_REQUEST['checkprivstable'] == $table
         */
        if (isset($_REQUEST['checkprivstable'])
        ) {
            $params['checkprivstable'] = $_REQUEST['checkprivstable'];
        }
        if (isset($_REQUEST['single_table'])
            && in_array($_REQUEST['single_table'], array(true, false))
        ) {
            $params['single_table'] = $_REQUEST['single_table'];
        }
        return basename(PMA_getenv('SCRIPT_NAME')) . PMA_URL_getCommon(
            $params,
            $encode
        );
    }

    /**
     * Renders the link to open a new page
     *
     * @param string $url The url of the page
     *
     * @return string
     */
    private function _getSelfLink($url)
    {
        $retval  = '';
        $retval .= '<div id="selflink" class="print_ignore">';
        $retval .= '<a href="' . $url . '"'
            . ' title="' . __('Open new phpMyAdmin window') . '" target="_blank">';
        if (Util::showIcons('TabsMode')) {
            $retval .= Util::getImage(
                'window-new.png',
                __('Open new phpMyAdmin window')
            );
        } else {
            $retval .=  __('Open new phpMyAdmin window');
        }
        $retval .= '</a>';
        $retval .= '</div>';
        return $retval;
    }

    /**
     * Renders the link to open a new page
     *
     * @return string
     */
    public function getErrorMessages()
    {
        $retval = '<div class="clearfloat" id="pma_errors">';
        if ($GLOBALS['error_handler']->hasDisplayErrors()) {
            $retval .= $GLOBALS['error_handler']->getDispErrors();
        }
        $retval .= '</div>';

        /**
         * Report php errors
         */
        $GLOBALS['error_handler']->reportErrors();

        return $retval;
    }

    /**
     * Saves query in history
     *
     * @return void
     */
    private function _setHistory()
    {
        if (! PMA_isValid($_REQUEST['no_history'])
            && empty($GLOBALS['error_message'])
            && ! empty($GLOBALS['sql_query'])
        ) {
            PMA_setHistory(
                PMA_ifSetOr($GLOBALS['db'], ''),
                PMA_ifSetOr($GLOBALS['table'], ''),
                $GLOBALS['cfg']['Server']['user'],
                $GLOBALS['sql_query']
            );
        }
    }

    /**
     * Disables the rendering of the footer
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
        $this->_isAjax = (boolean) $isAjax;
    }

    /**
     * Turn on minimal display mode
     *
     * @return void
     */
    public function setMinimal()
    {
        $this->_isMinimal = true;
    }

    /**
     * Returns the Scripts object
     *
     * @return Scripts object
     */
    public function getScripts()
    {
        return $this->_scripts;
    }

    /**
     * Renders the footer
     *
     * @return string
     */
    public function getDisplay()
    {
        $retval = '';
        $this->_setHistory();
        if ($this->_isEnabled) {
            if (! $this->_isAjax) {
                $retval .= "</div>";
            }
            if (! $this->_isAjax && ! $this->_isMinimal) {
                if (PMA_getenv('SCRIPT_NAME')
                    && empty($_POST)
                    && empty($GLOBALS['checked_special'])
                    && ! $this->_isAjax
                ) {
                    $url = $this->getSelfUrl('unencoded');
                    $header = Response::getInstance()->getHeader();
                    $scripts = $header->getScripts()->getFiles();
                    $menuHash = $header->getMenu()->getHash();
                    // prime the client-side cache
                    $this->_scripts->addCode(
                        sprintf(
                            'if (! (history && history.pushState)) '
                            . 'PMA_MicroHistory.primer = {'
                            . ' url: "%s",'
                            . ' scripts: %s,'
                            . ' menuHash: "%s"'
                            . '};',
                            PMA_escapeJsString($url),
                            json_encode($scripts),
                            PMA_escapeJsString($menuHash)
                        )
                    );
                }
                if (PMA_getenv('SCRIPT_NAME')
                    && ! $this->_isAjax
                ) {
                    $url = $this->getSelfUrl();
                    $retval .= $this->_getSelfLink($url);
                }
                $this->_scripts->addCode(
                    'var debugSQLInfo = ' . $this->getDebugMessage() . ';'
                );
                $retval .= $this->getErrorMessages();
                $retval .= $this->_scripts->getDisplay();
                if ($GLOBALS['cfg']['DBG']['demo']) {
                    $retval .= '<div id="pma_demo">';
                    $retval .= $this->_getDemoMessage();
                    $retval .= '</div>';
                }
                // Include possible custom footers
                if (file_exists(CUSTOM_FOOTER_FILE)) {
                    $retval .= '<div id="pma_footer">';
                    ob_start();
                    include CUSTOM_FOOTER_FILE;
                    $retval .= ob_get_contents();
                    ob_end_clean();
                    $retval .= '</div>';
                }
            }
            if (! $this->_isAjax) {
                $retval .= "</body></html>";
            }
        }

        return $retval;
    }
}
