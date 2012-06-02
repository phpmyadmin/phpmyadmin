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
     * @var object
     */
    private static $_instance;
    /**
     * PMA_Header instance
     *
     * @access private
     * @var object
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
     * @var object
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
     * Whether there were any errors druing the processing of the request
     * Only used for ajax responses
     *
     * @access private
     * @var bool
     */
    private $_isSuccess;

    /**
     * Cretes a new class instance
     *
     * @return new PMA_Response object
     */
    private function __construct()
    {
        $buffer = PMA_OutputBuffering::getInstance();
        $buffer->start();
        $this->_header = new PMA_Header();
        $this->_HTML   = '';
        $this->_JSON   = array();
        $this->_footer = new PMA_Footer();

        $this->_isSuccess = true;
        $this->_isAjax = false;
        if (isset($_REQUEST['ajax_request']) && $_REQUEST['ajax_request'] == true) {
            $this->_isAjax = true;
        }
        $this->_header->isAjax($this->_isAjax);
        $this->_footer->isAjax($this->_isAjax);
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
     * FIXME
     *
     * @return void
     */
    public function isSuccess($state)
    {
        if ($state) {
            $this->_isSuccess = true;
        } else {
            $this->_isSuccess = false;
        }
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
     * @return object
     */
    public function getHeader()
    {
        return $this->_header;
    }

    /**
     * Returns a PMA_Footer object
     *
     * @return object
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
        if ($value instanceof PMA_Message) {
            $this->_HTML = $value->getDisplay();
        } else {
            $this->_HTML = $value;
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
    public function addJSON($json, $value)
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
        // if it's content was already rendered
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
        if (empty($this->_JSON)) {
            // header('Content-Type: text/html; charset=utf-8');
            echo $this->_getDisplay();
        } else {
            if (! isset($this->_JSON['message'])) {
                $this->_JSON['message'] = $this->_getDisplay();
            }
            $message = $this->_JSON['message'];
            unset($this->_JSON['message']);
            PMA_ajaxResponse($message, $this->_isSuccess, $this->_JSON);
        }
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
        $buffer   = PMA_OutputBuffering::getInstance();
        if (empty($response->_HTML)) {
            $response->_HTML = $buffer->getContents();
        }
        if ($response->_isAjax) {
            $response->_ajaxResponse();
        } else {
            $response->_htmlResponse();
        }
        $buffer->flush();
        exit;
    }
}

?>
