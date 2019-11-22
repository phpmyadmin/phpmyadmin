<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Used to render the footer of PMA's pages
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin;

use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use PhpMyAdmin\Message;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Scripts;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
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
     * @var Relation $relation
     */
    private $relation;

    /**
     * Creates a new class instance
     */
    public function __construct()
    {
        $this->_isEnabled = true;
        $this->_scripts   = new Scripts();
        $this->_isMinimal = false;
        $this->relation = new Relation();
    }

    /**
     * Returns the message for demo server to error messages
     *
     * @return string
     */
    private function _getDemoMessage()
    {
        $message = '<a href="/">' . __('phpMyAdmin Demo Server') . '</a>: ';
        if (@file_exists('./revision-info.php')) {
            include './revision-info.php';
            $message .= sprintf(
                __('Currently running Git revision %1$s from the %2$s branch.'),
                '<a target="_blank" rel="noopener noreferrer" href="' . htmlspecialchars($repobase . $fullrevision) . '">'
                . htmlspecialchars($revision) . '</a>',
                '<a target="_blank" rel="noopener noreferrer" href="' . htmlspecialchars($repobranchbase . $branch) . '">'
                . htmlspecialchars($branch) . '</a>'
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
    private static function _removeRecursion(&$object, array $stack = array())
    {
        if ((is_object($object) || is_array($object)) && $object) {
            if ($object instanceof Traversable) {
                $object = "***ITERATOR***";
            } elseif (!in_array($object, $stack, true)) {
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
     * @return string
     */
    public function getSelfUrl()
    {
        $db = isset($GLOBALS['db']) && strlen($GLOBALS['db']) ? $GLOBALS['db'] : '';
        $table = isset($GLOBALS['table']) && strlen($GLOBALS['table']) ? $GLOBALS['table'] : '';
        $target = isset($_REQUEST['target']) && strlen($_REQUEST['target']) ? $_REQUEST['target'] : '';
        $params = array(
            'db' => $db,
            'table' => $table,
            'server' => $GLOBALS['server'],
            'target' => $target
        );
        // needed for server privileges tabs
        if (isset($_GET['viewing_mode'])
            && in_array($_GET['viewing_mode'], array('server', 'db', 'table'))
        ) {
            $params['viewing_mode'] = $_GET['viewing_mode'];
        }
        /*
         * @todo    coming from server_privileges.php, here $db is not set,
         *          add the following condition below when that is fixed
         *          && $_GET['checkprivsdb'] == $db
         */
        if (isset($_GET['checkprivsdb'])
        ) {
            $params['checkprivsdb'] = $_GET['checkprivsdb'];
        }
        /*
         * @todo    coming from server_privileges.php, here $table is not set,
         *          add the following condition below when that is fixed
         *          && $_REQUEST['checkprivstable'] == $table
         */
        if (isset($_GET['checkprivstable'])
        ) {
            $params['checkprivstable'] = $_GET['checkprivstable'];
        }
        if (isset($_REQUEST['single_table'])
            && in_array($_REQUEST['single_table'], array(true, false))
        ) {
            $params['single_table'] = $_REQUEST['single_table'];
        }
        return basename(Core::getenv('SCRIPT_NAME')) . Url::getCommonRaw($params);
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
        $retval .= '<a href="' . htmlspecialchars($url) . '"'
            . ' title="' . __('Open new phpMyAdmin window') . '" target="_blank" rel="noopener noreferrer">';
        if (Util::showIcons('TabsMode')) {
            $retval .= Util::getImage(
                'window-new',
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
            $retval .= $GLOBALS['error_handler']->getDispErrors();
        }

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
        if (! Core::isValid($_REQUEST['no_history'])
            && empty($GLOBALS['error_message'])
            && ! empty($GLOBALS['sql_query'])
            && isset($GLOBALS['dbi'])
            && $GLOBALS['dbi']->isUserType('logged')
        ) {
            $this->relation->setHistory(
                Core::ifSetOr($GLOBALS['db'], ''),
                Core::ifSetOr($GLOBALS['table'], ''),
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
                if (Core::getenv('SCRIPT_NAME')
                    && empty($_POST)
                    && ! $this->_isAjax
                ) {
                    $url = $this->getSelfUrl();
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
                            Sanitize::escapeJsString($url),
                            json_encode($scripts),
                            Sanitize::escapeJsString($menuHash)
                        )
                    );
                }
                if (Core::getenv('SCRIPT_NAME')
                    && ! $this->_isAjax
                ) {
                    $url = $this->getSelfUrl();
                    $retval .= $this->_getSelfLink($url);
                }
                $this->_scripts->addCode(
                    'var debugSQLInfo = ' . $this->getDebugMessage() . ';'
                );
                $retval .= '<div class="clearfloat" id="pma_errors">';
                $retval .= $this->getErrorMessages();
                $retval .= '</div>';
                $retval .= $this->_scripts->getDisplay();
                if ($GLOBALS['cfg']['DBG']['demo']) {
                    $retval .= '<div id="pma_demo">';
                    $retval .= $this->_getDemoMessage();
                    $retval .= '</div>';
                }

                $retval .= Config::renderFooter();
            }
            if (! $this->_isAjax) {
                $retval .= "</body></html>";
            }
        }

        return $retval;
    }
}
