<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Manages the rendering of pages in PMA
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/OutputBuffering.class.php';
require_once 'libraries/Header.class.php';
require_once 'libraries/Footer.class.php';

/**
 * Singleton class used to manage the rendering of pages in PMA
 *
 * @package PhpMyAdmin
 */
class PMA_Response
{
    /**
     * PMA_Response instance
     *
     * @access private
     * @static
     * @var PMA_Response
     */
    private static $_instance;
    /**
     * PMA_Header instance
     *
     * @access private
     * @var PMA_Header
     */
    private $_header;
    /**
     * HTML data to be used in the response
     *
     * @access private
     * @var string
     */
    private $_HTML;
    /**
     * An array of JSON key-value pairs
     * to be sent back for ajax requests
     *
     * @access private
     * @var array
     */
    private $_JSON;
    /**
     * PMA_Footer instance
     *
     * @access private
     * @var PMA_Footer
     */
    private $_footer;
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
     * Whether we are servicing an ajax request for a page
     * that was fired using the generic page handler in JS.
     *
     * @access private
     * @var bool
     */
    private $_isAjaxPage;
    /**
     * Whether there were any errors druing the processing of the request
     * Only used for ajax responses
     *
     * @access private
     * @var bool
     */
    private $_isSuccess;
    /**
     * Workaround for PHP bug
     *
     * @access private
     * @var bool
     */
    private $_CWD;

    /**
     * Creates a new class instance
     */
    private function __construct()
    {
        if (! defined('TESTSUITE')) {
            $buffer = PMA_OutputBuffering::getInstance();
            $buffer->start();
        }
        $this->_header = new PMA_Header();
        $this->_HTML   = '';
        $this->_JSON   = array();
        $this->_footer = new PMA_Footer();

        $this->_isSuccess  = true;
        $this->_isAjax     = false;
        $this->_isAjaxPage = false;
        if (isset($_REQUEST['ajax_request']) && $_REQUEST['ajax_request'] == true) {
            $this->_isAjax = true;
        }
        if (isset($_REQUEST['ajax_page_request'])
            && $_REQUEST['ajax_page_request'] == true
        ) {
            $this->_isAjaxPage = true;
        }
        $this->_header->setAjax($this->_isAjax);
        $this->_footer->setAjax($this->_isAjax);
        $this->_CWD = getcwd();
    }

    /**
     * Returns the singleton PMA_Response object
     *
     * @return PMA_Response object
     */
    public static function getInstance()
    {
        if (empty(self::$_instance)) {
            self::$_instance = new PMA_Response();
        }
        return self::$_instance;
    }

    /**
     * Set the status of an ajax response,
     * whether it is a success or an error
     *
     * @param bool $state Whether the request was successfully processed
     *
     * @return void
     */
    public function isSuccess($state)
    {
        $this->_isSuccess = ($state == true);
    }

    /**
     * Returns true or false depending on whether
     * we are servicing an ajax request
     *
     * @return bool
     */
    public function isAjax()
    {
        return $this->_isAjax;
    }

    /**
     * Returns the path to the current working directory
     * Necessary to work around a PHP bug where the CWD is
     * reset after the initial script exits
     *
     * @return string
     */
    public function getCWD()
    {
        return $this->_CWD;
    }

    /**
     * Disables the rendering of the header
     * and the footer in responses
     *
     * @return void
     */
    public function disable()
    {
        $this->_header->disable();
        $this->_footer->disable();
    }

    /**
     * Returns a PMA_Header object
     *
     * @return PMA_Header
     */
    public function getHeader()
    {
        return $this->_header;
    }

    /**
     * Returns a PMA_Footer object
     *
     * @return PMA_Footer
     */
    public function getFooter()
    {
        return $this->_footer;
    }

    /**
     * Add HTML code to the response
     *
     * @param string $content A string to be appended to
     *                        the current output buffer
     *
     * @return void
     */
    public function addHTML($content)
    {
        if (is_array($content)) {
            foreach ($content as $msg) {
                $this->addHTML($msg);
            }
        } elseif ($content instanceof PMA_Message) {
            $this->_HTML .= $content->getDisplay();
        } else {
            $this->_HTML .= $content;
        }
    }

    /**
     * Add JSON code to the response
     *
     * @param mixed $json  Either a key (string) or an
     *                     array or key-value pairs
     * @param mixed $value Null, if passing an array in $json otherwise
     *                     it's a string value to the key
     *
     * @return void
     */
    public function addJSON($json, $value = null)
    {
        if (is_array($json)) {
            foreach ($json as $key => $value) {
                $this->addJSON($key, $value);
            }
        } else {
            if ($value instanceof PMA_Message) {
                $this->_JSON[$json] = $value->getDisplay();
            } else {
                $this->_JSON[$json] = $value;
            }
        }

    }

    /**
     * Renders the HTML response text
     *
     * @return string
     */
    private function _getDisplay()
    {
        // The header may contain nothing at all,
        // if its content was already rendered
        // and, in this case, the header will be
        // in the content part of the request
        $retval  = $this->_header->getDisplay();
        $retval .= $this->_HTML;
        $retval .= $this->_footer->getDisplay();
        return $retval;
    }

    /**
     * Sends an HTML response to the browser
     *
     * @return void
     */
    private function _htmlResponse()
    {
        echo $this->_getDisplay();
    }

    /**
     * Sends a JSON response to the browser
     *
     * @return void
     */
    private function _ajaxResponse()
    {
        if (! isset($this->_JSON['message'])) {
            $this->_JSON['message'] = $this->_getDisplay();
        } else if ($this->_JSON['message'] instanceof PMA_Message) {
            $this->_JSON['message'] = $this->_JSON['message']->getDisplay();
        }

        if ($this->_isSuccess) {
            $this->_JSON['success'] = true;
        } else {
            $this->_JSON['success'] = false;
            $this->_JSON['error']   = $this->_JSON['message'];
            unset($this->_JSON['message']);
        }

        if ($this->_isAjaxPage && $this->_isSuccess) {
            $this->addJSON('_title', $this->getHeader()->getTitleTag());

            $menuHash = $this->getHeader()->getMenu()->getHash();
            $this->addJSON('_menuHash', $menuHash);
            $hashes = array();
            if (isset($_REQUEST['menuHashes'])) {
                $hashes = explode('-', $_REQUEST['menuHashes']);
            }
            if (! in_array($menuHash, $hashes)) {
                $this->addJSON('_menu', $this->getHeader()->getMenu()->getDisplay());
            }

            $this->addJSON('_scripts', $this->getHeader()->getScripts()->getFiles());
            $this->addJSON('_selflink', $this->getFooter()->getSelfUrl('unencoded'));
            $this->addJSON('_displayMessage', $this->getHeader()->getMessage());
            $errors = $this->_footer->getErrorMessages();
            if (strlen($errors)) {
                $this->addJSON('_errors', $errors);
            }
            if (empty($GLOBALS['error_message'])) {
                // set current db, table and sql query in the querywindow
                $query = '';
                $maxChars = $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'];
                if (isset($GLOBALS['sql_query'])
                    && strlen($GLOBALS['sql_query']) < $maxChars
                ) {
                    $query = PMA_escapeJsString($GLOBALS['sql_query']);
                }
                $this->addJSON(
                    '_reloadQuerywindow',
                    array(
                        'db' => PMA_ifSetOr($GLOBALS['db'], ''),
                        'table' => PMA_ifSetOr($GLOBALS['table'], ''),
                        'sql_query' => $query
                    )
                );
                if (! empty($GLOBALS['focus_querywindow'])) {
                    $this->addJSON('_focusQuerywindow', $query);
                }
                if (! empty($GLOBALS['reload'])) {
                    $this->addJSON('_reloadNavigation', 1);
                }
                $this->addJSON('_params', $this->getHeader()->getJsParams());
            }
        }

        // Set the Content-Type header to JSON so that jQuery parses the
        // response correctly.
        if (! defined('TESTSUITE')) {
            header('Cache-Control: no-cache');
            header('Content-Type: application/json');
        }

        echo json_encode($this->_JSON);
    }

    /**
     * Sends an HTML response to the browser
     *
     * @static
     * @return void
     */
    public static function response()
    {
        $response = PMA_Response::getInstance();
        chdir($response->getCWD());
        $buffer = PMA_OutputBuffering::getInstance();
        if (empty($response->_HTML)) {
            $response->_HTML = $buffer->getContents();
        }
        if ($response->isAjax()) {
            $response->_ajaxResponse();
        } else {
            $response->_htmlResponse();
        }
        $buffer->flush();
        exit;
    }
}

?>
