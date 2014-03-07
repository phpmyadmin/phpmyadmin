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

require_once 'libraries/Scripts.class.php';

/**
 * Class used to output the footer
 *
 * @package PhpMyAdmin
 */
class PMA_Footer
{
    /**
     * PMA_Scripts instance
     *
     * @access private
     * @var object
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
        $this->_scripts   = new PMA_Scripts();
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
                . $revision .'</a>',
                '<a target="_blank" href="' . $repobranchbase . $branch . '">'
                . $branch . '</a>'
            );
        } else {
            $message .= __('Git information missing!');
        }

        return PMA_Message::notice($message)->getDisplay();
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

            $retval .= '<div id="session_debug">';
            $retval .= count($_SESSION['debug']['queries']) . ' queries executed ';
            $retval .= $sum_exec . ' times in ' . $sum_time . ' seconds';
            $retval .= '<pre>';

            ob_start();
            print_r($_SESSION['debug']);
            $retval .= ob_get_contents();
            ob_end_clean();

            $retval .= '</pre>';
            $retval .= '</div>';
            $_SESSION['debug'] = array();
        }
        return $retval;
    }

    /**
     * Returns the url of the current page
     *
     * @param mixed $encoding See PMA_URL_getCommon()
     *
     * @return string
     */
    public function getSelfUrl($encoding = null)
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
        return basename(PMA_getenv('SCRIPT_NAME')) . PMA_URL_getCommon(
            $params,
            $encoding
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
        if (PMA_Util::showIcons('TabsMode')) {
            $retval .= PMA_Util::getImage(
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
        $retval = '';
        if ($GLOBALS['error_handler']->hasDisplayErrors()) {
            $retval .= '<div class="clearfloat" id="pma_errors">';
            $retval .= $GLOBALS['error_handler']->getDispErrors();
            $retval .= '</div>';
        }
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
     * Turn on minimal display mode
     *
     * @return void
     */
    public function setMinimal()
    {
        $this->_isMinimal = true;
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
                    $header = PMA_Response::getInstance()->getHeader();
                    $scripts = $header->getScripts()->getFiles();
                    $menuHash = $header->getMenu()->getHash();
                    // prime the client-side cache
                    $this->_scripts->addCode(
                        sprintf(
                            'AJAX.cache.primer = {'
                            . ' url: "%s",'
                            . ' scripts: %s,'
                            . ' menuHash: "%s"'
                            . '};',
                            PMA_escapeJsString($url),
                            json_encode($scripts),
                            PMA_escapeJsString($menuHash)
                        )
                    );
                    $url = $this->getSelfUrl();
                    $retval .= $this->_getSelfLink($url);
                }
                $retval .= $this->_getDebugMessage();
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
